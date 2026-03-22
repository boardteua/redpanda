<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivateMessageReadState extends Model
{
    protected $fillable = [
        'user_id',
        'peer_id',
        'last_read_incoming_message_id',
    ];

    protected function casts(): array
    {
        return [
            'last_read_incoming_message_id' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function peer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'peer_id');
    }
}
