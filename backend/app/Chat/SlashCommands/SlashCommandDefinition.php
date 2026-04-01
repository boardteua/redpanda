<?php

namespace App\Chat\SlashCommands;

use App\Chat\SlashCommands\Contracts\SlashCommandHandlerContract;

final readonly class SlashCommandDefinition
{
    /**
     * @param  array{description?: string, roles?: list<string>, client_only?: bool, priority?: int}  $meta
     */
    public function __construct(
        public string $name,
        public SlashCommandHandlerContract $handler,
        public array $meta = [],
        /**
         * Monotonic registration order used as deterministic tie-breaker.
         */
        public int $registrationOrder = 0,
    ) {}
}

