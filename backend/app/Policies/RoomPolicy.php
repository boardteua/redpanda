<?php

namespace App\Policies;

use App\Models\Room;
use App\Models\User;

class RoomPolicy
{
    /**
     * `access === 0` — будь-який авторизований користувач (у т. ч. гість).
     * `access > 0` — лише зареєстрований обліковий запис.
     */
    public function interact(User $user, Room $room): bool
    {
        if ((int) $room->access === 0) {
            return true;
        }

        return ! $user->guest;
    }
}
