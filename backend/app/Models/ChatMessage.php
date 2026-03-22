<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $table = 'chat';

    protected $primaryKey = 'post_id';

    public $incrementing = true;

    protected $keyType = 'int';

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'post_style' => 'array',
        ];
    }

    protected $fillable = [
        'user_id',
        'post_date',
        'post_edited_at',
        'post_time',
        'post_user',
        'post_message',
        'post_style',
        'post_color',
        'post_roomid',
        'type',
        'post_target',
        'avatar',
        'file',
        'client_message_id',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Room, $this>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'post_roomid', 'room_id');
    }

    /**
     * Вкладене зображення (`chat.file` = `images.id`, 0 — немає).
     *
     * @return BelongsTo<Image, $this>
     */
    public function attachedImage(): BelongsTo
    {
        return $this->belongsTo(Image::class, 'file');
    }
}
