<?php

namespace App\Chat\SlashCommands;

use App\Chat\SlashCommands\Contracts\SlashCommandHandlerContract;

final readonly class CallableSlashCommandHandler implements SlashCommandHandlerContract
{
    /**
     * @param  callable(SlashCommandContext, string, string): SlashCommandOutcome  $callable
     */
    public function __construct(callable $callable)
    {
        $this->callable = \Closure::fromCallable($callable);
    }

    /**
     * @var \Closure(SlashCommandContext, string, string): SlashCommandOutcome
     */
    private \Closure $callable;

    public function handle(SlashCommandContext $context, string $commandName, string $args): SlashCommandOutcome
    {
        return ($this->callable)($context, $commandName, $args);
    }
}

