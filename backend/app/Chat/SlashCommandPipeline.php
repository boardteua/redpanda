<?php

namespace App\Chat;

use App\Chat\Slash\SlashCommandLineParser;
use App\Chat\Slash\SlashCommandRegistry;
use App\Chat\Slash\SlashDefaultHandlers;
use App\Chat\Slash\SlashInvocation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Інфраструктура slash-команд (T66): парсер, реєстр, throttle, аудит у логах.
 */
final class SlashCommandPipeline
{
    public function __construct(
        private readonly SlashCommandRegistry $registry,
    ) {}

    public static function buildDefaultRegistry(): SlashCommandRegistry
    {
        $r = new SlashCommandRegistry(SlashDefaultHandlers::unknown(...));
        $r->register('me', SlashDefaultHandlers::me(...));
        $r->register('noop', SlashDefaultHandlers::noop(...));

        return $r;
    }

    /**
     * @return string|null Повідомлення про помилку для 429, або null якщо ліміт не перевищено.
     */
    public function ensureSlashThrottle(User $user): ?string
    {
        if (! RateLimiter::attempt(
            'slash-command:'.$user->id,
            25,
            fn () => true,
            60,
        )) {
            return 'Забагато slash-команд за хвилину. Спробуйте пізніше.';
        }

        return null;
    }

    /**
     * @return array{result: SlashHandlerResult, invocation: SlashInvocation}
     */
    public function dispatchParsed(User $user, Room $room, string $trimmedLine): array
    {
        $parsed = SlashCommandLineParser::parse($trimmedLine);
        if ($parsed === null) {
            throw new \InvalidArgumentException('Expected a line starting with /');
        }

        $invocation = new SlashInvocation($user, $room, $trimmedLine, $parsed);
        $handlerResult = $this->registry->dispatch($invocation);

        Log::info('chat.slash_command', [
            'command' => $parsed->command === '' ? null : $parsed->command,
            'user_id' => $user->id,
            'room_id' => $room->room_id,
            'outcome' => $handlerResult->kind,
        ]);

        return ['result' => $handlerResult, 'invocation' => $invocation];
    }
}
