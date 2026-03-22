<?php

namespace App\Support;

use App\Models\User;

/**
 * Ліміти хвилини для throttle middleware (узгоджено з T21).
 * Винесено для тестів і єдиного місця зміни порогів.
 */
final class ChatThrottleRules
{
    public static function postsPerMinute(User $user): int
    {
        return match (true) {
            $user->guest => 15,
            $user->canModerate() => 90,
            $user->isVip() => 60,
            default => 30,
        };
    }

    /**
     * Гості формально отримують нижчий ліміт (маршрут усе одно повертає 403 до обробки файлу).
     */
    public static function imageUploadsPerMinute(User $user): int
    {
        return match (true) {
            $user->guest => 5,
            $user->isVip() || $user->canModerate() => 30,
            default => 20,
        };
    }
}
