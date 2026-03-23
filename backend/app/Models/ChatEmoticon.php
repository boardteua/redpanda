<?php

namespace App\Models;

use Database\Factories\ChatEmoticonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Каталог смайлів (GIF/PNG/WebP) у /emoticon/ — T63.
 *
 * @property int $id
 * @property string $code
 * @property string $display_name
 * @property string $file_name
 * @property int $sort_order
 * @property bool $is_active
 * @property string|null $keywords
 */
class ChatEmoticon extends Model
{
    /** @use HasFactory<ChatEmoticonFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'display_name',
        'file_name',
        'sort_order',
        'is_active',
        'keywords',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function setCodeAttribute(string $value): void
    {
        $this->attributes['code'] = strtolower(trim($value));
    }
}
