<?php

namespace App\Services\Ai\RudaPanda;

use App\Jobs\PostRudaPandaRoomReplyJob;
use App\Models\ChatSetting;
use App\Models\Room;
use Illuminate\Support\Facades\RateLimiter;

final class RudaPandaRoomReplyScheduler
{
    public function __construct(
        private readonly RudaPandaReplyFormatter $formatter,
    ) {}

    /**
     * Schedule a bot reply with delay+jitter and anti-flood.
     *
     * Returns true if a job was dispatched, false if throttled or empty after formatting.
     */
    public function schedule(Room $room, string $rawReplyText, string $idempotencyKey): bool
    {
        $settings = ChatSetting::current();

        if (! $settings->ai_llm_enabled || ! ($room->ai_bot_enabled ?? true)) {
            return false;
        }

        $maxChars = max(80, min(2000, (int) ($settings->ai_bot_max_reply_chars ?: 500)));
        $text = $this->formatter->format($rawReplyText, $maxChars);
        if ($text === '') {
            return false;
        }

        $roomLimit = max(1, min(1000, (int) ($settings->ai_bot_room_max_replies_per_window ?: 3)));
        $roomWindow = max(5, min(86400, (int) ($settings->ai_bot_room_window_seconds ?: 300)));
        $globalLimit = max(1, min(10000, (int) ($settings->ai_bot_global_max_replies_per_window ?: 30)));
        $globalWindow = max(5, min(86400, (int) ($settings->ai_bot_global_window_seconds ?: 300)));

        $roomKey = 'ruda-panda:room:'.$room->room_id;
        $globalKey = 'ruda-panda:global';

        $okRoom = RateLimiter::attempt($roomKey, $roomLimit, static fn (): bool => true, $roomWindow);
        if (! $okRoom) {
            return false;
        }

        $okGlobal = RateLimiter::attempt($globalKey, $globalLimit, static fn (): bool => true, $globalWindow);
        if (! $okGlobal) {
            return false;
        }

        $minMs = max(0, min(60000, (int) ($settings->ai_bot_reply_delay_min_ms ?: 1200)));
        $maxMs = max($minMs, min(120000, (int) ($settings->ai_bot_reply_delay_max_ms ?: 3000)));
        $delayMs = $minMs === $maxMs ? $minMs : random_int($minMs, $maxMs);

        PostRudaPandaRoomReplyJob::dispatch(
            roomId: (int) $room->room_id,
            replyText: $text,
            idempotencyKey: $idempotencyKey,
        )->delay(now()->addMilliseconds($delayMs));

        return true;
    }
}

