<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserIgnore;

final class PrivateMessageGate
{
    public static function isBlocked(User $a, User $b): bool
    {
        return UserIgnore::query()
            ->where(function ($q) use ($a, $b) {
                $q->where(function ($q2) use ($a, $b) {
                    $q2->where('user_id', $a->id)->where('ignored_user_id', $b->id);
                })->orWhere(function ($q2) use ($a, $b) {
                    $q2->where('user_id', $b->id)->where('ignored_user_id', $a->id);
                });
            })
            ->exists();
    }
}
