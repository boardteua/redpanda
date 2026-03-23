<?php

namespace App\Chat\SlashCommands\Handlers;

use App\Chat\SlashCommands\ChatManualHelp;
use App\Chat\SlashCommands\Contracts\SlashCommandHandlerContract;
use App\Chat\SlashCommands\SlashCommandContext;
use App\Chat\SlashCommands\SlashCommandOutcome;

final class ManualSlashCommandHandler implements SlashCommandHandlerContract
{
    public function handle(SlashCommandContext $context, string $commandName, string $args): SlashCommandOutcome
    {
        return SlashCommandOutcome::clientOnlyMessage(ChatManualHelp::text(), [
            'name' => 'manual',
            'recognized' => true,
        ]);
    }
}
