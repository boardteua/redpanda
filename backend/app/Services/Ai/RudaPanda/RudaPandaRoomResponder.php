<?php

namespace App\Services\Ai\RudaPanda;

use App\Jobs\GenerateRudaPandaVipImageJob;
use App\Jobs\GenerateRudaPandaRoomReplyJob;
use App\Jobs\PostRudaPandaRoomReplyJob;
use App\Models\ChatMessage;
use App\Models\ChatSetting;
use App\Models\Room;
use App\Models\User;
use App\Services\Ai\Gemini\GeminiClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

final class RudaPandaRoomResponder
{
    public function __construct(
        private readonly RudaPandaTriggerDetector $triggers,
        private readonly RudaPandaModelRouter $router,
        private readonly GeminiClient $gemini,
    ) {}

    public function maybeDispatchForMessage(ChatMessage $message, Room $room): void
    {
        $settings = ChatSetting::current();
        if (! $settings->ai_llm_enabled || ! ($room->ai_bot_enabled ?? true)) {
            return;
        }

        // Only react to regular public messages (not bot/system/private).
        if ($message->type !== 'public') {
            return;
        }
        if ((string) $message->post_color === 'system') {
            return;
        }

        $text = (string) $message->post_message;
        if (! $this->triggers->shouldRespond($text)) {
            if (! $this->shouldRespondAsFollowupWithoutMention($room, $message, $text)) {
                return;
            }
        }

        $intent = $this->router->classifyIntent($text);
        if ($intent === RudaPandaIntent::Image) {
            $this->dispatchImageIntent($room, $message, $text);
            return;
        }

        // Cheap pre-limit to avoid queue bloat; real anti-flood is in scheduler (T179).
        $key = 'ruda-panda:gen:room:'.$room->room_id;
        $ok = RateLimiter::attempt($key, maxAttempts: 6, callback: static fn (): bool => true, decaySeconds: 60);
        if (! $ok) {
            return;
        }

        $idempotencyKey = (string) Str::uuid();

        GenerateRudaPandaRoomReplyJob::dispatch(
            roomId: (int) $room->room_id,
            triggerPostId: (int) $message->post_id,
            triggerUserId: (int) $message->user_id,
            triggerText: $text,
            idempotencyKey: $idempotencyKey,
        );
    }

    private function dispatchImageIntent(Room $room, ChatMessage $message, string $text): void
    {
        $user = User::query()->whereKey((int) $message->user_id)->first();
        $isAllowed = $user !== null && ! $user->guest && ($user->isVip() || $user->canModerate());

        $idempotencyKey = (string) ($message->client_message_id ?: Str::uuid());
        $prompt = $this->normalizeImagePrompt($text);

        if (! $isAllowed) {
            $deny = 'Я картинки тільки своїм малюю. Наний на ВІП і спробуй ще раз.';

            PostRudaPandaRoomReplyJob::dispatch(
                roomId: (int) $room->room_id,
                replyText: $deny,
                idempotencyKey: 'img-deny:'.$idempotencyKey,
            );

            return;
        }

        GenerateRudaPandaVipImageJob::dispatch(
            roomId: (int) $room->room_id,
            triggerUserId: (int) $message->user_id,
            prompt: $prompt,
            idempotencyKey: 'img:'.$idempotencyKey,
        );
    }

    private function normalizeImagePrompt(string $text): string
    {
        $t = trim((string) preg_replace('/\s+/u', ' ', $text));
        $t = preg_replace('/^(руда\s+панда|панда)\b[,:!\.\s-]*/ui', '', $t) ?? $t;

        return trim($t);
    }

    private function shouldRespondAsFollowupWithoutMention(Room $room, ChatMessage $message, string $text): bool
    {
        // T182: after bot asked a clarification, allow a short follow-up from the same user without re-mention.
        $stateKey = 'ruda-panda:clarify:room:'.$room->room_id;
        /** @var array<string, mixed>|null $state */
        $state = Cache::get($stateKey);
        if (is_array($state)) {
            $awaitingUserId = (int) ($state['awaiting_user_id'] ?? 0);
            $awaitingUntil = (int) ($state['awaiting_until'] ?? 0);
            if ($awaitingUserId > 0 && $awaitingUntil >= time() && (int) $message->user_id === $awaitingUserId) {
                return true;
            }
        }

        // Also allow a follow-up to a recent bot question, gated by a tiny yes/no LLM check.
        /** @var array<string, mixed>|null $lastBot */
        $lastBot = Cache::get('ruda-panda:last-bot:room:'.$room->room_id);
        if (! is_array($lastBot)) {
            return false;
        }

        $ts = (int) ($lastBot['ts'] ?? 0);
        $botText = trim((string) ($lastBot['text'] ?? ''));
        if ($ts <= 0 || $botText === '' || ! str_ends_with($botText, '?')) {
            return false;
        }

        // 10 minutes window to avoid accidental triggers.
        if ($ts < (time() - 10 * 60)) {
            return false;
        }

        $prompt = trim((string) preg_replace('/\s+/u', ' ', $text));
        if ($prompt === '') {
            return false;
        }

        $payload = [
            'contents' => [[
                'role' => 'user',
                'parts' => [[
                    'text' => "Ти модератор-класифікатор. Відповідай РІВНО одним словом: YES або NO.\n".
                        "Чи є це повідомлення логічною відповіддю/уточненням на попереднє питання бота?\n\n".
                        "Питання бота: {$botText}\n".
                        "Повідомлення користувача: {$prompt}\n",
                ]],
            ]],
        ];

        $route = $this->router->routeForTriggerWithRoleFlags($prompt, guest: false, vip: false);

        try {
            $resp = $this->gemini->generateContent($payload, $route->modelId);
        } catch (\Throwable) {
            return false;
        }

        $ans = mb_strtoupper(trim((string) ($resp['candidates'][0]['content']['parts'][0]['text'] ?? '')));

        return $ans === 'YES';
    }
}

