<?php

namespace App\Services\Ai\RudaPanda;

use App\Jobs\GenerateRudaPandaVipImageJob;
use App\Jobs\GenerateRudaPandaRoomReplyJob;
use App\Jobs\PostRudaPandaRoomReplyJob;
use App\Models\ChatMessage;
use App\Models\ChatSetting;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

final class RudaPandaRoomResponder
{
    public function __construct(
        private readonly RudaPandaTriggerDetector $triggers,
        private readonly RudaPandaModelRouter $router,
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
            return;
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
            $deny = 'Генерація зображень доступна лише VIP або staff. Якщо потрібно — оформи VIP і спробуй ще раз.';

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
        $t = preg_replace('/^\/img\b\s*/ui', '', $t) ?? $t;
        $t = preg_replace('/^(руда\s+панда|панда)\b[,:!\.\s-]*/ui', '', $t) ?? $t;

        return trim($t);
    }
}

