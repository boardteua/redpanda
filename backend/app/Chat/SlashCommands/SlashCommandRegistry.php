<?php

namespace App\Chat\SlashCommands;

use App\Chat\SlashCommands\Contracts\SlashCommandHandlerContract;

final class SlashCommandRegistry
{
    /** @var array<string, SlashCommandDefinition> */
    private array $definitions = [];

    private int $registrationOrder = 0;

    /**
     * @param  callable(SlashCommandContext, string, string): SlashCommandOutcome|SlashCommandHandlerContract  $handler
     * @param  array{description?: string, roles?: list<string>, client_only?: bool, priority?: int}  $meta
     */
    public function register(string $name, callable|SlashCommandHandlerContract $handler, array $meta = []): void
    {
        $normalized = strtolower($name);
        $contractHandler = is_callable($handler) ? new CallableSlashCommandHandler($handler) : $handler;

        $this->registrationOrder++;
        $definition = new SlashCommandDefinition(
            name: $normalized,
            handler: $contractHandler,
            meta: $meta,
            registrationOrder: $this->registrationOrder,
        );

        $existing = $this->definitions[$normalized] ?? null;
        if ($existing === null) {
            $this->definitions[$normalized] = $definition;

            return;
        }

        $existingPriority = (int) ($existing->meta['priority'] ?? 0);
        $newPriority = (int) ($definition->meta['priority'] ?? 0);

        if ($newPriority > $existingPriority) {
            $this->definitions[$normalized] = $definition;

            return;
        }

        if ($newPriority === $existingPriority && $definition->registrationOrder > $existing->registrationOrder) {
            $this->definitions[$normalized] = $definition;
        }
    }

    public function getDefinition(string $name): ?SlashCommandDefinition
    {
        return $this->definitions[strtolower($name)] ?? null;
    }

    public function get(string $name): ?SlashCommandHandlerContract
    {
        return $this->getDefinition($name)?->handler;
    }
}
