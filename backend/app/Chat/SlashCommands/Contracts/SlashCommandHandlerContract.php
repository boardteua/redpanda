<?php

namespace App\Chat\SlashCommands\Contracts;

use App\Chat\SlashCommands\SlashCommandContext;
use App\Chat\SlashCommands\SlashCommandOutcome;

interface SlashCommandHandlerContract
{
    public function handle(SlashCommandContext $context, string $commandName, string $args): SlashCommandOutcome;
}
