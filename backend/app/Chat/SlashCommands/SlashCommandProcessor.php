<?php

namespace App\Chat\SlashCommands;

use App\Chat\SlashCommands\Handlers\UnknownSlashCommandHandler;
use App\Models\ChatSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

final class SlashCommandProcessor
{
    public function __construct(
        private readonly SlashCommandRegistry $registry,
        private readonly UnknownSlashCommandHandler $unknownHandler,
    ) {}

    public function process(string $rawMessage, SlashCommandContext $context): SlashCommandOutcome
    {
        $parsed = SlashCommandParser::tryParseCommand($rawMessage);
        if ($parsed === null) {
            return SlashCommandOutcome::passThrough($rawMessage);
        }

        $settings = ChatSetting::current();
        $maxAttempts = max(1, min(65535, (int) $settings->slash_command_max_per_window));
        $decaySeconds = max(10, min(86400, (int) $settings->slash_command_window_seconds));

        $rateKey = 'slash-command:'.$context->user->id;
        $allowed = RateLimiter::attempt($rateKey, $maxAttempts, static fn (): bool => true, $decaySeconds);
        if (! $allowed) {
            $retryAfter = RateLimiter::availableIn($rateKey);

            return SlashCommandOutcome::httpError(
                429,
                'Забагато команд, що починаються з /. Спробуйте через '.$retryAfter.' с.',
            );
        }

        $name = $parsed['name'];
        $args = $parsed['args'];
        $definition = $this->registry->getDefinition($name);
        $handler = $definition?->handler ?? $this->unknownHandler;
        $outcome = $handler->handle($context, $name, $args);

        Log::info('chat.slash_command', [
            'command' => $name,
            'handler' => $handler === $this->unknownHandler ? 'unknown' : 'registered',
            'user_id' => (int) $context->user->id,
            'room_id' => (int) $context->room->room_id,
            'client_only' => $outcome->mode === SlashCommandOutcome::MODE_CLIENT_ONLY,
        ]);

        return $outcome;
    }
}
