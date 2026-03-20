<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserIgnore extends Model
{
    protected $table = 'user_ignores';

    protected $fillable = [
        'user_id',
        'ignored_user_id',
    ];

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
    public function ignoredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ignored_user_id');
    }
}
