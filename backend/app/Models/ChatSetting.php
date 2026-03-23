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
}
