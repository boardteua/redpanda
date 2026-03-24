<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Каталог назв UI-тем оформлення чату (legacy `theme` у DATABASE-SCHEMA); керування через slash **T72**.
 */
class ChatTheme extends Model
{
    protected $table = 'chat_themes';

    protected $fillable = [
        'name',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }
}
