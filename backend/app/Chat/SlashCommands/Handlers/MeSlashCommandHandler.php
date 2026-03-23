<?php

namespace App\Chat\SlashCommands\Handlers;

use App\Chat\SlashCommands\Contracts\SlashCommandHandlerContract;
use App\Chat\SlashCommands\SlashCommandContext;
use App\Chat\SlashCommands\SlashCommandOutcome;

final class MeSlashCommandHandler implements SlashCommandHandlerContract
{
    public function handle(SlashCommandContext $context, string $commandName, string $args): SlashCommandOutcome
    {
        $body = $args === '' ? '' : $args;
        $display = $context->displayUserName;
        $formatted = '*'.$display.($body === '' ? '' : ' '.$body).'*';

        return SlashCommandOutcome::publicRoomMessage($formatted, [
            'name' => 'me',
            'recognized' => true,
        ]);
    }
}
