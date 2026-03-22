<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Кімнати чату.
 *
 * `access`: публічна ({@see self::ACCESS_PUBLIC}), лише зареєстровані ({@see self::ACCESS_REGISTERED}).
 * Усі значення `>= {@see self::ACCESS_VIP}` наразі мають однакові правила (VIP-зона) у {@see RoomPolicy::interact}
 * та у фільтрі списку кімнат. Нові рівні (наприклад лише staff) потребуватимуть окремих констант і оновлення policy.
 */
class Room extends Model
{
    public const ACCESS_PUBLIC = 0;

    /** Лише зареєстровані (не гість). */
    public const ACCESS_REGISTERED = 1;

    /** VIP або модератор/адмін. */
    public const ACCESS_VIP = 2;

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
