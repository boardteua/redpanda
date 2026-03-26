<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SystemBotUserSeeder extends Seeder
{
    public function run(): void
    {
        if (User::query()->where('is_system_bot', true)->exists()) {
            return;
        }

        $user = User::query()->create([
            'user_name' => 'Руда панда',
            'email' => 'system-bot@redpanda.invalid',
            'password' => Hash::make(Str::random(64)),
            'guest' => false,
        ]);

        $user->forceFill(['is_system_bot' => true])->save();
    }
}
