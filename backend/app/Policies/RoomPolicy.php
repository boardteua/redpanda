<?php

namespace App\Policies;

use App\Models\Room;
use App\Models\User;

class RoomPolicy
{
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
