<?php

namespace App\Chat\SlashCommands\Support;

use App\Chat\SlashCommands\SlashCommandOutcome;
use App\Models\User;

final class ModerationSlashCommandHelper
{
    public static function requireModerator(User $user): ?SlashCommandOutcome
    {
        if ($user->guest || ! $user->canModerate()) {
            return SlashCommandOutcome::httpError(403, 'Ця команда доступна лише модераторам чату.');
        }

        return null;
    }

    /**
     * @return array{0: string, 1: ?int, 2: ?string} nick, optional minutes, error key (need_nick|bad_minutes|extra_tokens)
     */
    public static function parseNickAndOptionalMinutes(string $args): array
    {
        $trimmed = trim($args);
        $parts = preg_split('/\s+/u', $trimmed, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($parts === []) {
            return ['', null, 'need_nick'];
        }

        $nick = $parts[0];
        if (count($parts) === 1) {
            return [$nick, null, null];
        }

        $minStr = $parts[1];
        if (! ctype_digit($minStr)) {
            return ['', null, 'bad_minutes'];
        }

        if (count($parts) > 2) {
            return ['', null, 'extra_tokens'];
        }

        return [$nick, (int) $minStr, null];
    }

    /**
     * @return array{0: string, 1: ?string} nick, error key (need_nick|extra_tokens)
     */
    public static function parseNickOnly(string $args): array
    {
        $trimmed = trim($args);
        $parts = preg_split('/\s+/u', $trimmed, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($parts === []) {
            return ['', 'need_nick'];
        }
        if (count($parts) > 1) {
            return ['', 'extra_tokens'];
        }

        return [$parts[0], null];
    }

    public static function findUserByNick(string $nick): ?User
    {
        if ($nick === '') {
            return null;
        }

        return User::query()
            ->whereRaw('LOWER(user_name) = LOWER(?)', [$nick])
            ->first();
    }

    public static function assertStaffCanAct(User $actor, User $target): ?SlashCommandOutcome
    {
        if ((int) $actor->id === (int) $target->id) {
            return SlashCommandOutcome::httpError(422, 'Неможливо застосувати до власного облікового запису.');
        }

        if (! $target->canReceiveStaffManagementFrom($actor)) {
            return SlashCommandOutcome::httpError(403, 'Недостатньо прав для дії щодо цього користувача.');
        }

        return null;
    }

    public static function outcomeForParseError(?string $key, string $commandHint): ?SlashCommandOutcome
    {
        return match ($key) {
            'need_nick' => SlashCommandOutcome::httpError(422, $commandHint),
            'bad_minutes' => SlashCommandOutcome::httpError(422, 'Другий аргумент має бути цілим числом хвилин (0 — зняти).'),
            'extra_tokens' => SlashCommandOutcome::httpError(422, 'Забагато аргументів.'),
            default => null,
        };
    }
}
