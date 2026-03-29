<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserWebPushPrivatePeerMute extends Model
{
    protected $fillable = [
        'user_id',
        'peer_user_id',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function peer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'peer_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
