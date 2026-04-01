<?php

namespace App\Services\Ai\RudaPanda;

use App\Models\ChatMessage;
use App\Models\Room;
use App\Services\Ai\Gemini\GeminiClient;
use App\Services\Ai\Gemini\GeminiResponseParser;
use Illuminate\Support\Facades\Cache;

final class RudaPandaFollowupEvaluator
{
    public function __construct(
        private readonly RudaPandaModelRouter $router,
        private readonly GeminiClient $gemini,
        private readonly GeminiResponseParser $geminiResponseParser,
    ) {}

    /**
     * @param  array<string, mixed>  $meta
     */
    public function shouldRespond(Room $room, ChatMessage $message, string $text, array &$meta): bool
    {
        $meta = [
            'path' => null,
            'decision' => 'NO',
            'reason' => null,
            'bot_question_preview' => null,
            'user_message_preview' => null,
            'model' => null,
            'llm_raw' => null,
            'normalized' => null,
            'error' => null,
        ];

        $stateKey = 'ruda-panda:clarify:room:'.$room->room_id;
        /** @var array<string, mixed>|null $state */
        $state = Cache::get($stateKey);
        if (is_array($state)) {
            $awaitingUserId = (int) ($state['awaiting_user_id'] ?? 0);
            $awaitingUntil = (int) ($state['awaiting_until'] ?? 0);
            if ($awaitingUserId > 0 && $awaitingUntil >= time() && (int) $message->user_id === $awaitingUserId) {
                $meta['path'] = 'clarification_await';
                $meta['decision'] = 'YES';
                $meta['reason'] = 'same_user_in_clarification_window';

                return true;
            }
        }

        /** @var array<string, mixed>|null $lastBot */
        $lastBot = Cache::get('ruda-panda:last-bot:room:'.$room->room_id);
        if (! is_array($lastBot)) {
            $meta['path'] = 'last_bot_llm';
            $meta['reason'] = 'no_last_bot_cache';

            return false;
        }

        $ts = (int) ($lastBot['ts'] ?? 0);
        $botText = trim((string) ($lastBot['text'] ?? ''));
        if ($ts <= 0 || $botText === '' || ! str_ends_with($botText, '?')) {
            $meta['path'] = 'last_bot_llm';
            $meta['reason'] = 'last_bot_not_a_question';

            return false;
        }

        if ($ts < (time() - 10 * 60)) {
            $meta['path'] = 'last_bot_llm';
            $meta['reason'] = 'last_bot_stale';

            return false;
        }

        $prompt = trim((string) preg_replace('/\s+/u', ' ', $text));
        if ($prompt === '') {
            $meta['path'] = 'last_bot_llm';
            $meta['reason'] = 'empty_user_message';

            return false;
        }

        $meta['path'] = 'last_bot_llm';
        $meta['bot_question_preview'] = mb_substr($botText, 0, 240);
        $meta['user_message_preview'] = mb_substr($prompt, 0, 240);

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
        $meta['model'] = $route->modelId;

        try {
            $response = $this->gemini->generateContent($payload, $route->modelId);
        } catch (\Throwable $e) {
            $meta['error'] = $e->getMessage();

            return false;
        }

        $raw = $this->geminiResponseParser->firstCandidateText($response);
        $meta['llm_raw'] = $raw;
        $answer = mb_strtoupper(trim($raw));
        $meta['normalized'] = $answer;
        $yes = $answer === 'YES';
        $meta['decision'] = $yes ? 'YES' : 'NO';
        if (! $yes && $answer !== '' && $answer !== 'NO') {
            $meta['reason'] = 'unparsed_llm_output';
        }

        return $yes;
    }
}
