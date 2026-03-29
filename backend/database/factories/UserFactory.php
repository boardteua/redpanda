<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_name' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'guest' => false,
            'web_push_master_enabled' => true,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function guest(): static
    {
        return $this->state(fn (array $attributes) => [
            'guest' => true,
            'email' => null,
            'password' => null,
            'email_verified_at' => null,
        ]);
    }

    public function moderator(): static
    {
        return $this->afterCreating(function (User $user): void {
            $user->forceFill(['user_rank' => User::RANK_MODERATOR])->save();
        });
    }

    public function admin(): static
    {
        return $this->afterCreating(function (User $user): void {
            $user->forceFill(['user_rank' => User::RANK_ADMIN])->save();
        });
    }

    public function vip(): static
    {
        return $this->afterCreating(function (User $user): void {
            $user->forceFill(['vip' => true])->save();
        });
    }

    /**
     * Системний користувач для повідомлень бота «Руда панда» (T149).
     */
    public function systemChatBot(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_name' => 'Руда панда',
            'email' => 'system-bot-'.Str::lower(Str::random(12)).'@redpanda.test',
        ])->afterCreating(function (User $user): void {
            $user->forceFill(['is_system_bot' => true])->save();
        });
    }
}
