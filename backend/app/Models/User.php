<?php

namespace App\Models;

use App\Chat\ChatRole;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\URL;
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
    'profile_occupation_hidden',
    'profile_about',
    'profile_about_hidden',
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
    use CanResetPassword, HasApiTokens, HasFactory, Notifiable;

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
            'is_system_bot' => 'boolean',
            'vip' => 'boolean',
            'user_rank' => 'integer',
            'mute_until' => 'integer',
            'kick_until' => 'integer',
            'profile_age' => 'integer',
            'profile_country_hidden' => 'boolean',
            'profile_region_hidden' => 'boolean',
            'profile_age_hidden' => 'boolean',
            'profile_sex_hidden' => 'boolean',
            'profile_occupation_hidden' => 'boolean',
            'profile_about_hidden' => 'boolean',
            'social_links' => 'array',
            'notification_sound_prefs' => 'array',
            'chat_history_prefs' => 'array',
            'account_disabled_at' => 'datetime',
            'chat_upload_disabled' => 'boolean',
            'presence_invisible' => 'boolean',
            'web_push_master_enabled' => 'boolean',
            'legacy_imported_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<UserWebPushRoomMute, $this>
     */
    public function webPushRoomMutes(): HasMany
    {
        return $this->hasMany(UserWebPushRoomMute::class, 'user_id');
    }

    /**
     * @return HasMany<UserWebPushPrivatePeerMute, $this>
     */
    public function webPushPrivatePeerMutes(): HasMany
    {
        return $this->hasMany(UserWebPushPrivatePeerMute::class, 'user_id');
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

    /**
     * Звичайні користувачі (не персонал модерації) не можуть додавати в ігнор модераторів/адмінів.
     */
    public function mayIgnoreChatUser(User $target): bool
    {
        if ($target->canModerate() && ! $this->canModerate()) {
            return false;
        }

        return true;
    }

    public function isChatAdmin(): bool
    {
        if ($this->guest) {
            return false;
        }

        return (int) $this->user_rank >= self::RANK_ADMIN;
    }

    public function isSystemBot(): bool
    {
        return (bool) $this->is_system_bot;
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
     * Абсолютний HTTPS-friendly URL для іконки Web Push (без Sanctum — ОС завантажує без cookies).
     */
    public function signedPublicAvatarUrlForPush(): ?string
    {
        if ($this->avatar_image_id === null) {
            return null;
        }

        $ttl = max(3600, (int) config('services.web_push.public_avatar_url_ttl_seconds', 604800));

        return URL::temporarySignedRoute(
            'api.v1.public-user-avatar',
            now()->addSeconds($ttl),
            ['user' => $this->id],
        );
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

    /**
     * @return array{room_history_chunk_size: int, private_history_chunk_size: int}
     */
    public static function defaultChatHistoryPrefs(): array
    {
        return [
            'room_history_chunk_size' => 20,
            'private_history_chunk_size' => 5,
        ];
    }
}
