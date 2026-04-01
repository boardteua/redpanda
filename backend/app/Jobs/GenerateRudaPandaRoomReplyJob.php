<?php

namespace App\Jobs;

use App\Models\ChatMessage;
use App\Models\ChatSetting;
use App\Models\Room;
use App\Models\User;
use App\Services\Ai\Gemini\GeminiClient;
use App\Services\Ai\RudaPanda\RoomMemoryService;
use App\Services\Ai\RudaPanda\RudaPandaModelRouter;
use App\Services\Ai\RudaPanda\RudaPandaRoomReplyScheduler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class GenerateRudaPandaRoomReplyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 6;

    public int $timeout = 30;

    public function backoff(): array
    {
        return [2, 4, 8, 16, 32, 64];
    }

    public function __construct(
        public int $roomId,
        public int $triggerPostId,
        public int $triggerUserId,
        public string $triggerText,
        public string $idempotencyKey,
    ) {}

    public function handle(
        GeminiClient $gemini,
        RudaPandaModelRouter $router,
        RoomMemoryService $memory,
        RudaPandaRoomReplyScheduler $scheduler,
    ): void {
        $room = Room::query()->whereKey($this->roomId)->first();
        if ($room === null) {
            return;
        }

        $settings = ChatSetting::current();
        if (! $settings->ai_llm_enabled || ! ($room->ai_bot_enabled ?? true)) {
            return;
        }

        $user = User::query()->whereKey($this->triggerUserId)->first();
        if ($user === null || $user->isSystemBot()) {
            return;
        }

        $topic = $this->topicHash($this->triggerText);
        $stateKey = 'ruda-panda:clarify:room:'.$room->room_id;
        /** @var array{topic:string,count:int}|null $state */
        $state = Cache::get($stateKey);
        $count = 0;
        if (is_array($state) && ($state['topic'] ?? null) === $topic) {
            $count = (int) ($state['count'] ?? 0);
        }

        $maxClarifications = 2; // K (T182)

        // Keep memory bounded (T178).
        $memory->rollupSummary($room);
        $ctx = $memory->buildContext($room, maxMessages: 30);

        $persona = trim((string) ($settings->ai_bot_persona_prompt ?? ''));
        if ($persona === '') {
            $persona = ChatSetting::defaultPersonaPromptFromConfig();
        }

        $system = $this->buildSystemInstruction($persona, $count, $maxClarifications);

        $contents = [];
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $system]],
        ];

        if (is_string($ctx['summary'] ?? null) && trim((string) $ctx['summary']) !== '') {
            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => 'Коротке зведення контексту кімнати: '.trim((string) $ctx['summary'])]],
            ];
        }

        foreach (($ctx['messages'] ?? []) as $m) {
            if (! is_array($m)) {
                continue;
            }
            $u = trim((string) ($m['user'] ?? ''));
            $t = trim((string) ($m['text'] ?? ''));
            if ($t === '') {
                continue;
            }
            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => ($u === '' ? 'user' : $u).': '.$t]],
            ];
        }

        // Ensure trigger message is present even if outside of context window.
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => ($user->user_name ?: 'user').': '.trim((string) $this->triggerText)]],
        ];

        $route = $router->routeForTriggerWithRoleFlags($this->triggerText, guest: (bool) $user->guest, vip: (bool) $user->isVip());

        $payload = [
            'contents' => $contents,
        ];
        $payload = $route->mergeIntoPayload($payload);

        try {
            $resp = $gemini->generateContent($payload, $route->modelId);
        } catch (RequestException $e) {
            if ($gemini->isResourceExhausted429($e)) {
                throw $e; // allow retries/backoff
            }
            throw $e;
        }

        $text = $this->extractCandidateText($resp);
        if ($text === null) {
            return;
        }

        $text = $this->normalizeReplyText($text);
        if ($text === '') {
            return;
        }

        $isClarification = $this->isClarificationReply($text);

        if ($isClarification && $count >= $maxClarifications) {
            // Avoid clarification loops: best-effort, no questions.
            $text = 'Спробую відповісти на основі того, що є: можеш додати трохи деталей — я уточню й підлаштуюсь.';
            $isClarification = false;
        }

        if ($isClarification) {
            Cache::put($stateKey, ['topic' => $topic, 'count' => $count + 1], now()->addHours(6));
        } else {
            Cache::forget($stateKey);
        }

        $ok = $scheduler->schedule($room, $text, $this->idempotencyKey);
        if (! $ok) {
            return;
        }

        Log::channel('structured')->info('ruda-panda reply generated', [
            'room_id' => $room->room_id,
            'trigger_post_id' => $this->triggerPostId,
            'model' => $route->modelId,
            'clarification' => $isClarification,
        ]);
    }

    private function buildSystemInstruction(string $persona, int $clarifyCount, int $maxClarifications): string
    {
        $persona = trim($persona);

        return trim(implode("\n", array_filter([
            $persona,
            '',
            'Правила формату (MVP): 1 короткий абзац, без списків/markdown, без зайвих переносів рядків.',
            'Якщо запит неоднозначний або не вистачає даних: постав РІВНО 1 уточнююче питання і нічого більше. Почни з префікса "Перепрошую, " або "непонів,  " або "Ась?, " .',
            'Не став уточнюючих питань більше '.$maxClarifications.' раз(и) підряд на одну тему. Зараз лічильник уточнень: '.$clarifyCount.'.',
            'Коли лічильник >= '.$maxClarifications.': дай найкращу коротку відповідь з явними припущеннями, БЕЗ питань.',
        ])));
    }

    /**
     * @param  array<string, mixed>  $resp
     */
    private function extractCandidateText(array $resp): ?string
    {
        $parts = $resp['candidates'][0]['content']['parts'] ?? null;
        if (! is_array($parts)) {
            return null;
        }
        foreach ($parts as $part) {
            if (is_array($part) && isset($part['text']) && is_string($part['text'])) {
                $t = trim($part['text']);
                if ($t !== '') {
                    return $t;
                }
            }
        }

        return null;
    }

    private function normalizeReplyText(string $text): string
    {
        $t = preg_replace('/\s+/u', ' ', trim($text)) ?? '';
        $t = str_replace(['“', '”', '„'], '"', $t);

        return trim($t);
    }

    private function isClarificationReply(string $text): bool
    {
        $t = mb_strtolower(trim($text));
        if (str_starts_with($t, 'уточнення:')) {
            return true;
        }

        return false;
    }

    private function topicHash(string $text): string
    {
        $t = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $text) ?? ''));
        $t = mb_substr($t, 0, 240);

        return hash('sha1', $t);
    }
}

