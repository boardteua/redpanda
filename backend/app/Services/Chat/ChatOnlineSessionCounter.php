<?php

namespace App\Services\Chat;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Приблизна кількість користувачів з активною сесією (T75 / T77 — лічильник на вітальні).
 */
final class ChatOnlineSessionCounter
{
    public static function recentDistinctUserCount(): int
    {
        if (Config::get('session.driver') !== 'database') {
            return 0;
        }

        if (! Schema::hasTable('sessions')) {
            return 0;
        }

        $window = (int) Config::get('chat.landing_online_recent_session_seconds', 300);
        $window = max(60, min($window, 3600));
        $since = time() - $window;

        $n = DB::table('sessions')
            ->where('last_activity', '>=', $since)
            ->whereNotNull('user_id')
            ->selectRaw('COUNT(DISTINCT user_id) as c')
            ->value('c');

        return (int) $n;
    }
}
