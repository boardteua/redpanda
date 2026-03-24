<?php

namespace App\Services\LegacyBoardImport;

use Illuminate\Support\Collection;
use stdClass;

/**
 * T113: імпорт лише тих legacy users, хто має ≥1 публічний рядок у таблиці chat.
 */
final class LegacyImportUserSelection
{
    /**
     * @param  Collection<int, stdClass>  $legacyUserRows  рядки з колонкою user_id
     * @param  list<int|string>  $distinctChatUserIds  user_id з legacy.chat (DISTINCT)
     * @return array{0: Collection<int, stdClass>, 1: int} відфільтровані рядки та кількість пропущених (без публічних постів)
     */
    public static function usersHavingPublicChatPosts(Collection $legacyUserRows, array $distinctChatUserIds): array
    {
        $chatSet = [];
        foreach ($distinctChatUserIds as $uid) {
            $chatSet[(int) $uid] = true;
        }

        $filtered = $legacyUserRows->filter(function (stdClass $u) use ($chatSet): bool {
            return isset($chatSet[(int) $u->user_id]);
        });

        $skipped = $legacyUserRows->count() - $filtered->count();

        return [$filtered->values(), $skipped];
    }
}
