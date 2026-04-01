<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Локальний/тестовий акаунт чат-адміна (нік як у legacy board.te.ua).
 * Пароль лише з ORG100H_SEED_PASSWORD у .env (не комітити значення).
 */
class Org100hAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            return;
        }

        $password = env('ORG100H_SEED_PASSWORD');
        if ($password === null || $password === '') {
            $this->command?->warn('Org100hAdminSeeder: пропущено — задай ORG100H_SEED_PASSWORD у .env (секрет не в коді).');

            return;
        }

        $user = User::query()->updateOrCreate(
            ['user_name' => 'org100h'],
            [
                'email' => 'org100h@redpanda.local',
                'password' => $password,
                'guest' => false,
                'email_verified_at' => now(),
            ],
        );

        $user->forceFill(['user_rank' => User::RANK_ADMIN])->save();
    }
}
