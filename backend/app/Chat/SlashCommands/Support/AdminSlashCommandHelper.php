<?php

namespace App\Chat\SlashCommands\Support;

use App\Chat\SlashCommands\SlashCommandOutcome;
use App\Models\User;

final class AdminSlashCommandHelper
{
    public static function requireChatAdmin(User $user): ?SlashCommandOutcome
    {
        if ($user->guest || ! $user->isChatAdmin()) {
            return SlashCommandOutcome::httpError(403, 'Ця команда доступна лише адміністраторам чату.');
        }

        return null;
    }
}
