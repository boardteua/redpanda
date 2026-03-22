<?php

namespace App\Models;

use App\Chat\ChatRole;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * `vip` і `user_rank` не в fillable — лише довірені шляхи (сидери, майбутній адмін-API через `forceFill` / явні присвоєння після `chat-admin`).
 */
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
            'vip' => 'boolean',
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
        if ($this->guest) {
            return false;
        }

        return (int) $this->user_rank >= self::RANK_MODERATOR;
    }

    public function isChatAdmin(): bool
    {
        if ($this->guest) {
            return false;
        }

        return (int) $this->user_rank >= self::RANK_ADMIN;
    }

    public function isVip(): bool
    {
        return ! $this->guest && (bool) $this->vip;
    }

    /**
     * Доступ до кімнат з access === VIP (або вище): VIP або персонал модерації.
     */
    public function canAccessVipRooms(): bool
    {
        return $this->isVip() || $this->canModerate();
    }

    public function resolveChatRole(): ChatRole
    {
        if ($this->guest) {
            return ChatRole::Guest;
        }
        if ($this->isChatAdmin()) {
            return ChatRole::Admin;
        }
        if ($this->canModerate()) {
            return ChatRole::Moderator;
        }
        if ($this->isVip()) {
            return ChatRole::Vip;
        }

        return ChatRole::User;
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
