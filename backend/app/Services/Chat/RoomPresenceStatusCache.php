<?php

namespace App\Services\Chat;

use Illuminate\Support\Facades\Cache;

/**
 * Кеш статусів присутності по кімнаті та користувачу (T48).
 */
class RoomPresenceStatusCache
{
    public const STATUSES = ['online', 'away', 'inactive'];

    public static function cacheKey(int $roomId, int $userId): string
    {
        return 'rp:room_presence:'.$roomId.':'.$userId;
    }

    public static function ttlSeconds(): int
    {
        $t = (int) config('chat.presence_status_ttl_seconds', 120);

        return max(30, min($t, 600));
    }

    public static function get(int $roomId, int $userId): ?string
    {
        $v = Cache::get(self::cacheKey($roomId, $userId));

        return is_string($v) && in_array($v, self::STATUSES, true) ? $v : null;
    }

    /**
     * @param  array<int, int>  $userIds
     * @return array<string, string> userId string => status
     */
    public static function getMany(int $roomId, array $userIds): array
    {
        $out = [];
        foreach ($userIds as $uid) {
            $uid = (int) $uid;
            if ($uid < 1) {
                continue;
            }
            $s = self::get($roomId, $uid);
            if ($s !== null) {
                $out[(string) $uid] = $s;
            }
        }

        return $out;
    }

    /**
     * Записує статус і повертає попереднє значення (для уникнення зайвого broadcast).
     */
    public static function put(int $roomId, int $userId, string $status): ?string
    {
        $key = self::cacheKey($roomId, $userId);
        $prev = Cache::get($key);
        $prevStr = is_string($prev) && in_array($prev, self::STATUSES, true) ? $prev : null;
        Cache::put($key, $status, self::ttlSeconds());

        return $prevStr;
    }
}
