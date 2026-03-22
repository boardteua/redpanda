<?php

namespace App\Services\Chat;

use App\Models\ChatMessage;
use App\Models\ChatSetting;
use App\Models\Room;
use App\Models\User;

/**
 * Лічба публічних повідомлень користувача для порогу створення кімнати (**T44** / **T51**).
 */
final class UserPublicMessageCountService
{
    /**
     * Повідомлення типу `public`, не видалені; область — за `ChatSetting`.
     */
    public function countEligiblePublicMessages(User $user, ?ChatSetting $settings = null): int
    {
        $settings ??= ChatSetting::current();

        $q = ChatMessage::query()
            ->where('user_id', $user->id)
            ->where('type', 'public')
            ->whereNull('post_deleted_at');

        if ($settings->public_message_count_scope === ChatSetting::SCOPE_DEFAULT_ROOM_ONLY) {
            $roomId = $settings->message_count_room_id;
            if ($roomId === null) {
                return 0;
            }

            $q->where('post_roomid', $roomId);
        } else {
            $publicRoomIds = Room::query()
                ->where('access', Room::ACCESS_PUBLIC)
                ->pluck('room_id');

            $q->whereIn('post_roomid', $publicRoomIds);
        }

        return (int) $q->count();
    }
}
