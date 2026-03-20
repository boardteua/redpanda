<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BannedIp extends Model
{
    protected $fillable = [
        'ip',
    ];

    public static function cacheKeyFor(string $ip): string
    {
        return 'moderation.banned_ip:'.hash('sha256', $ip);
    }
}
