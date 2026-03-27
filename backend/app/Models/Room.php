<?php

namespace App\Models;

use App\Services\Chat\RoomSlugService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
    /** Сегмент URL/API, з якого виконано binding (для `slug_redirect` у JSON). */
    public ?string $slugBindingSource = null;

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
        'slug',
        'topic',
        'access',
        'created_by_user_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (Room $room): void {
            if (! filled($room->slug)) {
                $room->slug = app(RoomSlugService::class)->proposeUniqueSlugFromName((string) $room->room_name, null);
            }
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

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

    /**
     * `rooms/{room}`: спочатку поточний **slug**, потім **історія** слагів, далі числовий **room_id** (**T153**).
     *
     * @param  mixed  $value
     */
    public function resolveRouteBinding($value, $field = null): ?static
    {
        if ($field !== null) {
            return parent::resolveRouteBinding($value, $field);
        }

        $raw = strtolower((string) $value);

        $room = static::query()->where('slug', $raw)->first();
        if ($room !== null) {
            $room->slugBindingSource = (string) $value;

            return $room;
        }

        $history = RoomSlugHistory::query()->where('slug', $raw)->first();
        if ($history !== null) {
            $room = static::query()->where('room_id', $history->room_id)->first();
            if ($room !== null) {
                $room->slugBindingSource = (string) $value;
            }

            return $room;
        }

        if (ctype_digit($raw)) {
            return static::query()->where('room_id', (int) $raw)->first();
        }

        return null;
    }
}
