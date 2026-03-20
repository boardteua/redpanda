<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Завантажені зображення для публічного чату (таблиця `images`).
 *
 * @property int $id
 * @property int $user_id
 * @property string $user_name
 * @property string $disk_path
 * @property string $file_name
 * @property string $mime
 * @property int $size_bytes
 * @property int $date_sent
 */
class Image extends Model
{
    public $timestamps = false;

    protected $table = 'images';

    protected $fillable = [
        'user_id',
        'user_name',
        'disk_path',
        'file_name',
        'mime',
        'size_bytes',
        'date_sent',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'size_bytes' => 'integer',
            'date_sent' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
