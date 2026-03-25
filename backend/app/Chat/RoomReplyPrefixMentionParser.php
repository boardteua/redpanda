<?php

namespace App\Chat;

use App\Models\ChatMessage;
use App\Models\User;

/**
 * Згадка ніка у загальному чаті за легасі-префіксом «нік > …» (T25, T123).
 */
final class RoomReplyPrefixMentionParser
{
    /**
     * @return array<int>
     */
    public static function mentionedUserIds(ChatMessage $message): array
    {
        if ($message->type !== 'public') {
            return [];
        }

        $raw = $message->post_message;
        if ($raw === null || $raw === '') {
            return [];
        }

        if (! preg_match('/^\s*(.+?)\s*>\s+/us', $raw, $m)) {
            return [];
        }

        $nick = trim($m[1]);
        if ($nick === '') {
            return [];
        }

        $id = User::query()
            ->whereRaw('LOWER(user_name) = LOWER(?)', [$nick])
            ->value('id');

        if ($id === null) {
            return [];
        }

        return [(int) $id];
    }
}
