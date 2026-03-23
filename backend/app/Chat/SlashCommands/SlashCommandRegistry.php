<?php

namespace App\Chat\SlashCommands;

use App\Chat\SlashCommands\Contracts\SlashCommandHandlerContract;

final class SlashCommandRegistry
{
    /** @var array<string, SlashCommandHandlerContract> */
    private array $handlers = [];

    public function register(string $name, SlashCommandHandlerContract $handler): void
    {
        $this->handlers[strtolower($name)] = $handler;
    }

    public function get(string $name): ?SlashCommandHandlerContract
    {
        return $this->handlers[strtolower($name)] ?? null;
    }
}
