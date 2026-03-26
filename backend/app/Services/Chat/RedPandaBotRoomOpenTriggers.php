<?php

namespace App\Services\Chat;

use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Тригери бота при відкритті стрічки кімнати (перша сторінка GET .../messages).
 */
final class RedPandaBotRoomOpenTriggers
{
    public function __construct(
        private readonly SystemBotMessageService $botMessages,
    ) {}

    public function handle(User $viewer, Room $room): void
    {
        if ($viewer->isSystemBot()) {
            return;
        }

        $bot = $this->botMessages->botUser();
        if ($bot === null) {
            return;
        }

        $welcomeSent = $this->maybeSendWelcome($viewer, $room);
        if (! $welcomeSent) {
            $this->maybeSendJoinAnnouncement($viewer, $room);
        }
    }

    private function maybeSendWelcome(User $viewer, Room $room): bool
    {
        $inserted = (int) DB::table('chat_bot_welcome_sent')->insertOrIgnore([
            'user_id' => $viewer->id,
            'room_id' => $room->room_id,
        ]) > 0;

        if (! $inserted) {
            return false;
        }

        $nick = $this->sanitizeDisplayFragment($viewer->user_name);
        $text = __('chat.bot.welcome', ['nick' => $nick]);
        $this->botMessages->postSystemMessage($room, SystemBotMessageService::KIND_ROOM_WELCOME, $text);

        return true;
    }

    private function maybeSendJoinAnnouncement(User $viewer, Room $room): void
    {
        $seconds = (int) config('chat.bot_join_debounce_seconds', 90);
        $cacheKey = sprintf('rp_bot_join:%d:%d', $viewer->id, $room->room_id);

        if (Cache::has($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, true, now()->addSeconds($seconds));

        $nick = $this->sanitizeDisplayFragment($viewer->user_name);
        $roomName = $this->sanitizeDisplayFragment($room->room_name);
        $text = __('chat.bot.room_join', ['nick' => $nick, 'room' => $roomName]);
        $this->botMessages->postSystemMessage($room, SystemBotMessageService::KIND_ROOM_JOIN, $text);
    }

    private function sanitizeDisplayFragment(?string $value): string
    {
        $v = trim((string) $value);
        if ($v === '') {
            return '—';
        }

        $v = str_replace(["\r", "\n"], ' ', $v);

        return mb_substr($v, 0, 191);
    }
}
