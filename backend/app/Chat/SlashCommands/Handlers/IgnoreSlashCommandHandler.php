<?php

namespace App\Chat\SlashCommands\Handlers;

use App\Chat\SlashCommands\Contracts\SlashCommandHandlerContract;
use App\Chat\SlashCommands\SlashCommandContext;
use App\Chat\SlashCommands\SlashCommandOutcome;
use App\Models\User;
use App\Models\UserIgnore;

final class IgnoreSlashCommandHandler implements SlashCommandHandlerContract
{
    public function handle(SlashCommandContext $context, string $commandName, string $args): SlashCommandOutcome
    {
        if ($context->user->guest) {
            return SlashCommandOutcome::httpError(403, 'Команда /ignore доступна лише зареєстрованим користувачам.');
        }

        $trimmed = trim($args);
        if ($trimmed === '') {
            return SlashCommandOutcome::httpError(422, 'Вкажіть нік: /ignore нік');
        }

        $nick = preg_split('/\s+/u', $trimmed, 2)[0] ?? '';
        if ($nick === '') {
            return SlashCommandOutcome::httpError(422, 'Вкажіть нік: /ignore нік');
        }

        $target = User::query()
            ->whereRaw('LOWER(user_name) = LOWER(?)', [$nick])
            ->first();

        if ($target === null) {
            return SlashCommandOutcome::httpError(422, 'Користувача з таким ніком не знайдено.');
        }

        if ((int) $target->id === (int) $context->user->id) {
            return SlashCommandOutcome::httpError(422, 'Неможливо ігнорувати себе.');
        }

        UserIgnore::query()->firstOrCreate([
            'user_id' => $context->user->id,
            'ignored_user_id' => $target->id,
        ]);

        return SlashCommandOutcome::clientOnlyMessage(
            'Користувача «'.$target->user_name.'» додано до ігнору.',
            [
                'name' => 'ignore',
                'recognized' => true,
            ],
        );
    }
}
