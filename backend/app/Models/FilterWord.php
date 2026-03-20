<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FilterWord extends Model
{
    protected $fillable = [
        'word',
    ];

    protected static function booted(): void
    {
        static::saving(function (FilterWord $word): void {
            $word->word = mb_strtolower(trim($word->word));
        });
    }
}
