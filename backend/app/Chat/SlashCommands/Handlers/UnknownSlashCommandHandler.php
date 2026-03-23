<?php

namespace App\Chat\SlashCommands\Handlers;

use App\Chat\SlashCommands\Contracts\SlashCommandHandlerContract;
use App\Chat\SlashCommands\SlashCommandContext;
use App\Chat\SlashCommands\SlashCommandOutcome;

final class UnknownSlashCommandHandler implements SlashCommandHandlerContract
{
    public function handle(SlashCommandContext $context, string $commandName, string $args): SlashCommandOutcome
    {
        $line = 'Невідома команда: /'.$commandName;
        if ($args !== '') {
            $line .= ' '.$args;
        }

        return SlashCommandOutcome::clientOnlyMessage($line, [
            'name' => $commandName,
            'recognized' => false,
        ]);
    }
}
