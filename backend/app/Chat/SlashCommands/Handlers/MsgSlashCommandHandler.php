<?php

namespace App\Chat\SlashCommands\Handlers;

use App\Chat\SlashCommands\Contracts\SlashCommandHandlerContract;
use App\Chat\SlashCommands\SlashCommandContext;
use App\Chat\SlashCommands\SlashCommandOutcome;

/**
 * Рядок /msg без ніка не потрапляє в RoomInlinePrivateParser — підказка замість «невідома команда».
 */
final class MsgSlashCommandHandler implements SlashCommandHandlerContract
{
    public function handle(SlashCommandContext $context, string $commandName, string $args): SlashCommandOutcome
    {
        return SlashCommandOutcome::httpError(
            422,
            'Використання: /msg нік текст повідомлення.',
        );
    }
}
