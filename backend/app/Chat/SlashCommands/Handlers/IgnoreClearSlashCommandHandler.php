<?php

namespace App\Chat\SlashCommands\Handlers;

use App\Chat\SlashCommands\Contracts\SlashCommandHandlerContract;
use App\Chat\SlashCommands\SlashCommandContext;
use App\Chat\SlashCommands\SlashCommandOutcome;
use App\Models\UserIgnore;

final class IgnoreClearSlashCommandHandler implements SlashCommandHandlerContract
{
    public function handle(SlashCommandContext $context, string $commandName, string $args): SlashCommandOutcome
    {
        if ($context->user->guest) {
            return SlashCommandOutcome::httpError(403, 'Команда /ignoreclear доступна лише зареєстрованим користувачам.');
        }

        $n = UserIgnore::query()->where('user_id', $context->user->id)->delete();
        $line = $n > 0
            ? 'Список ігнору очищено (записів: '.$n.').'
            : 'Список ігнору вже був порожній.';

        return SlashCommandOutcome::clientOnlyMessage($line, [
            'name' => 'ignoreclear',
            'recognized' => true,
        ]);
    }
}
