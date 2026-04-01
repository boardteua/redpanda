<?php

namespace App\Services\Ai\RudaPanda;

use App\Jobs\GenerateRudaPandaRoomReplyJob;
use App\Models\ChatMessage;
use App\Models\ChatSetting;
use App\Models\Room;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

final class RudaPandaRoomResponder
{
    public function __construct(
        private readonly RudaPandaTriggerDetector $triggers,
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
}

