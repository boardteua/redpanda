<?php

namespace App\Chat\Slash;

/**
 * Реєстр обробників slash-команд; невідома команда — fallback.
 */
final class SlashCommandRegistry
{
    /** @var array<string, callable(SlashInvocation): SlashHandlerResult> */
    private array $handlers = [];

    /** @var callable(SlashInvocation): SlashHandlerResult */
    private $fallback;

    /**
     * @param  callable(SlashInvocation): SlashHandlerResult  $fallback
     */
    public function __construct(callable $fallback)
    {
        $this->fallback = $fallback;
    }

    public function register(string $command, callable $handler): void
    {
        $this->handlers[strtolower($command)] = $handler;
    }

    public function dispatch(SlashInvocation $invocation): SlashHandlerResult
    {
        $name = $invocation->parsed->command;
        if ($name !== '' && isset($this->handlers[$name])) {
            return ($this->handlers[$name])($invocation);
        }

        return ($this->fallback)($invocation);
    }
}
