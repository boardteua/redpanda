<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['user_name', 'email', 'password', 'guest'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    public const RANK_USER = 0;

    public const RANK_MODERATOR = 1;

    public const RANK_ADMIN = 2;

    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'guest' => 'boolean',
            'user_rank' => 'integer',
            'mute_until' => 'integer',
            'kick_until' => 'integer',
        ];
    }

    public function isMutedAt(?int $now = null): bool
    {
        $now ??= time();
        $until = $this->mute_until;

        return $until !== null && $until > $now;
    }

    public function isKickedAt(?int $now = null): bool
    {
        $now ??= time();
        $until = $this->kick_until;

        return $until !== null && $until > $now;
    }

    public function canModerate(): bool
    {
        return (int) $this->user_rank >= self::RANK_MODERATOR;
    }

    /**
     * @return BelongsTo<Image, $this>
     */
    public function avatarImage(): BelongsTo
    {
        return $this->belongsTo(Image::class, 'avatar_image_id');
    }

    public function resolveAvatarUrl(): ?string
    {
        if ($this->avatar_image_id === null) {
            return null;
        }

        return route('api.v1.chat-images.file', ['image' => $this->avatar_image_id], true);
    }
}
