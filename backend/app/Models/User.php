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
#[Fillable([
    'user_name',
    'email',
    'password',
    'guest',
    'profile_country',
    'profile_region',
    'profile_age',
    'profile_sex',
    'profile_country_hidden',
    'profile_region_hidden',
    'profile_age_hidden',
    'profile_sex_hidden',
    'profile_occupation',
    'profile_about',
    'social_links',
    'notification_sound_prefs',
])]
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
            'profile_age' => 'integer',
            'profile_country_hidden' => 'boolean',
            'profile_region_hidden' => 'boolean',
            'profile_age_hidden' => 'boolean',
            'profile_sex_hidden' => 'boolean',
            'social_links' => 'array',
            'notification_sound_prefs' => 'array',
            'account_disabled_at' => 'datetime',
            'chat_upload_disabled' => 'boolean',
            'presence_invisible' => 'boolean',
        ];
    }

    public function isChatUploadDisabled(): bool
    {
        return (bool) $this->chat_upload_disabled;
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

    /**
     * Чи може персонал модерації (mute/kick, staff UI) застосовувати дії до цього користувача.
     */
    public function canReceiveStaffManagementFrom(User $actor): bool
    {
        if ($actor->guest || ! $actor->canModerate()) {
            return false;
        }
        if ((int) $this->id === (int) $actor->id) {
            return false;
        }

        return (int) $this->user_rank < (int) $actor->user_rank;
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

    /**
     * @return array{facebook: string, instagram: string, telegram: string, twitter: string, youtube: string, tiktok: string, discord: string, website: string}
     */
    public static function defaultSocialLinkKeys(): array
    {
        return [
            'facebook' => '',
            'instagram' => '',
            'telegram' => '',
            'twitter' => '',
            'youtube' => '',
            'tiktok' => '',
            'discord' => '',
            'website' => '',
        ];
    }

    /**
     * @return array{public_messages: bool, mentions: bool, private: bool, volume_percent: int}
     */
    public static function defaultNotificationSoundPrefs(): array
    {
        return [
            'public_messages' => true,
            'mentions' => true,
            'private' => true,
            'volume_percent' => 80,
        ];
    }
}
