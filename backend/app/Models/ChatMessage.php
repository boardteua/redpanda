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

    protected $fillable = [
        'user_id',
        'post_date',
        'post_time',
        'post_user',
        'post_message',
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
}
