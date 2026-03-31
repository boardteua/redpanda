<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BanEvasionEvent extends Model
{
    protected $fillable = [
        'user_id',
        'ip',
        'action',
        'path',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];
}

