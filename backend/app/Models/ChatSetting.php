<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Глобальні параметри чату (один рядок). Для **T44** — поріг N і область лічби публічних повідомлень.
 */
class ChatSetting extends Model
{
    public const SCOPE_ALL_PUBLIC_ROOMS = 'all_public_rooms';

    /** Лічба лише в одній кімнаті (публічні повідомлення; приват не враховуються у T44). */
    public const SCOPE_DEFAULT_ROOM_ONLY = 'default_room_only';

    protected $table = 'chat_settings';

    protected $fillable = [
        'room_create_min_public_messages',
        'public_message_count_scope',
        'message_count_room_id',
        'slash_command_max_per_window',
        'slash_command_window_seconds',
        'mod_slash_default_mute_minutes',
        'mod_slash_default_kick_minutes',
        'silent_mode',
        'landing_settings',
        'registration_flags',
        'sound_on_every_post',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'room_create_min_public_messages' => 'integer',
            'message_count_room_id' => 'integer',
            'slash_command_max_per_window' => 'integer',
            'slash_command_window_seconds' => 'integer',
            'mod_slash_default_mute_minutes' => 'integer',
            'mod_slash_default_kick_minutes' => 'integer',
            'silent_mode' => 'boolean',
            'landing_settings' => 'array',
            'registration_flags' => 'array',
            'sound_on_every_post' => 'boolean',
        ];
    }

    public static function current(): self
    {
        /** @var self $row */
        $row = static::query()->firstOrFail();

        return $row;
    }

    /**
     * @return BelongsTo<Room, $this>
     */
    public function messageCountRoom(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'message_count_room_id', 'room_id');
    }

    /**
     * @return array<int, string>
     */
    public static function scopeValues(): array
    {
        return [self::SCOPE_ALL_PUBLIC_ROOMS, self::SCOPE_DEFAULT_ROOM_ONLY];
    }

    public static function isAcceptableLandingLinkUrl(string $value): bool
    {
        if ($value === '') {
            return true;
        }
        if (str_starts_with($value, '/') && ! str_starts_with($value, '//')) {
            return strlen($value) <= 500 && ! preg_match('/\s/u', $value);
        }

        return (bool) filter_var($value, FILTER_VALIDATE_URL)
            && preg_match('#^https?://#i', $value) === 1;
    }

    /**
     * @param  array<string, mixed>|null  $raw
     * @return array{page_title: ?string, tagline: ?string, news_title: string, news_body: string, links: list<array{label: string, url: string}>}
     */
    public static function normalizeLandingSettings(?array $raw): array
    {
        $out = [
            'page_title' => null,
            'tagline' => null,
            'news_title' => '',
            'news_body' => '',
            'links' => [],
        ];
        if (! is_array($raw)) {
            return $out;
        }
        foreach (['page_title' => 120, 'tagline' => 200, 'news_title' => 200] as $key => $max) {
            $v = $raw[$key] ?? null;
            if (! is_string($v)) {
                continue;
            }
            $t = trim($v);
            if ($t === '') {
                if ($key === 'page_title' || $key === 'tagline') {
                    $out[$key] = null;
                } else {
                    $out[$key] = '';
                }

                continue;
            }
            $out[$key] = $key === 'page_title' || $key === 'tagline'
                ? mb_substr($t, 0, $max)
                : mb_substr($t, 0, $max);
        }
        $body = $raw['news_body'] ?? '';
        if (is_string($body)) {
            $out['news_body'] = mb_substr(trim($body), 0, 8000);
        }
        $links = $raw['links'] ?? [];
        if (is_array($links)) {
            foreach ($links as $link) {
                if (! is_array($link)) {
                    continue;
                }
                $label = isset($link['label']) && is_string($link['label']) ? trim($link['label']) : '';
                $url = isset($link['url']) && is_string($link['url']) ? trim($link['url']) : '';
                if ($label === '' && $url === '') {
                    continue;
                }
                if ($url !== '' && ! self::isAcceptableLandingLinkUrl($url)) {
                    continue;
                }
                $out['links'][] = [
                    'label' => mb_substr($label, 0, 100),
                    'url' => mb_substr($url, 0, 500),
                ];
                if (count($out['links']) >= 8) {
                    break;
                }
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>|null  $raw
     * @return array{registration_open: bool, min_age: ?int, show_social_login_buttons: bool}
     */
    public static function normalizeRegistrationFlags(?array $raw): array
    {
        $out = [
            'registration_open' => true,
            'min_age' => null,
            'show_social_login_buttons' => false,
        ];
        if (! is_array($raw)) {
            return $out;
        }
        if (array_key_exists('registration_open', $raw)) {
            $out['registration_open'] = filter_var($raw['registration_open'], FILTER_VALIDATE_BOOLEAN);
        }
        if (array_key_exists('show_social_login_buttons', $raw)) {
            $out['show_social_login_buttons'] = filter_var($raw['show_social_login_buttons'], FILTER_VALIDATE_BOOLEAN);
        }
        if (array_key_exists('min_age', $raw) && $raw['min_age'] !== null && $raw['min_age'] !== '') {
            $n = filter_var($raw['min_age'], FILTER_VALIDATE_INT);

            if ($n !== false && $n >= 0 && $n <= 120) {
                $out['min_age'] = $n;
            }
        }

        return $out;
    }

    /**
     * @return array{page_title: ?string, tagline: ?string, news_title: string, news_body: string, links: list<array{label: string, url: string}>}
     */
    public function resolvedLandingSettings(): array
    {
        return self::normalizeLandingSettings(
            is_array($this->landing_settings) ? $this->landing_settings : null
        );
    }

    /**
     * @return array{registration_open: bool, min_age: ?int, show_social_login_buttons: bool}
     */
    public function resolvedRegistrationFlags(): array
    {
        return self::normalizeRegistrationFlags(
            is_array($this->registration_flags) ? $this->registration_flags : null
        );
    }
}
