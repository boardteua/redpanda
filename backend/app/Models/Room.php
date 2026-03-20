<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $table = 'rooms';

    protected $primaryKey = 'room_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'room_name',
        'topic',
        'access',
    ];

    /**
     * @return HasMany<ChatMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'post_roomid', 'room_id');
    }

    public function getRouteKeyName(): string
    {
        return 'room_id';
    }
}
