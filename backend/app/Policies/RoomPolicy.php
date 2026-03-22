<?php

namespace App\Policies;

use App\Models\ChatSetting;
use App\Models\Room;
use App\Models\User;
use App\Services\Chat\UserPublicMessageCountService;

class RoomPolicy
{
    /**
     * Створення нової кімнати (**T44**): гість — ні; staff/VIP — так; зареєстрований — якщо лічильник публічних повідомлень > N з {@see ChatSetting}.
     */
    public function create(User $user): bool
    {
        if ($user->guest) {
            return false;
        }

        if ($user->canModerate() || $user->isVip()) {
            return true;
        }

        $settings = ChatSetting::current();
        $n = (int) $settings->room_create_min_public_messages;
        $count = app(UserPublicMessageCountService::class)->countEligiblePublicMessages($user, $settings);

        return $count > $n;
    }

    /**
     * `access === 0` — будь-який авторизований користувач (у т. ч. гість).
     * `access === 1` — лише зареєстрований обліковий запис.
     * `access >= 2` (VIP-кімната) — зареєстрований з VIP або персонал модерації.
     */
    public function interact(User $user, Room $room): bool
    {
        $access = (int) $room->access;

        if ($access === Room::ACCESS_PUBLIC) {
            return true;
        }

        if ($user->guest) {
            return false;
        }

        if ($access >= Room::ACCESS_VIP) {
            return $user->canAccessVipRooms();
        }

        return true;
    }
}
