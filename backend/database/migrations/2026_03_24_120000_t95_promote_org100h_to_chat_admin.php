<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * T95 — one-off (production): existing user `org100h` → chat administrator (`user_rank` = 2).
 * Idempotent. Applied automatically on deploy when `php artisan migrate --force` runs (docker/deploy.sh).
 *
 * @see \App\Models\User::RANK_ADMIN
 */
return new class extends Migration
{
    private const USER_NAME = 'org100h';

    private const RANK_ADMIN = 2;

    public function up(): void
    {
        DB::table('users')
            ->where('user_name', self::USER_NAME)
            ->where('guest', false)
            ->update([
                'user_rank' => self::RANK_ADMIN,
                'updated_at' => now(),
            ]);
    }

    /**
     * Best-effort: strips admin only if still at admin rank (does not restore prior moderator rank).
     */
    public function down(): void
    {
        DB::table('users')
            ->where('user_name', self::USER_NAME)
            ->where('guest', false)
            ->where('user_rank', self::RANK_ADMIN)
            ->update([
                'user_rank' => 0,
                'updated_at' => now(),
            ]);
    }
};
