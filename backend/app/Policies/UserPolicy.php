<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Повноцінний обліковий запис (не анонімний гість) — для майбутніх обмежень API/UI.
     */
    public function actAsRegisteredMember(User $authUser, User $targetUser): bool
    {
        return $authUser->is($targetUser) && ! $authUser->guest;
    }
}
