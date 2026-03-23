<?php

namespace App\Chat\SlashCommands;

/**
 * Розбір рядка на ім’я команди (після `/`, до першого пробілу) та аргументи (решта).
 * Порожнє ім’я після `/` (наприклад лише `/`) — не команда, рядок лишається звичайним текстом.
 */
final class SlashCommandParser
{
    /**
     * @return array{name: string, args: string}|null null — не slash-команда або порожнє ім’я
     */
    public static function tryParseCommand(string $rawMessage): ?array
    {
        $trimmed = ltrim($rawMessage);
        if ($trimmed === '' || $trimmed[0] !== '/') {
            return null;
        }

        $withoutSlash = substr($trimmed, 1);
        $space = strpos($withoutSlash, ' ');
        $name = $space === false
            ? strtolower($withoutSlash)
            : strtolower(substr($withoutSlash, 0, $space));
        $args = $space === false ? '' : ltrim(substr($withoutSlash, $space + 1));

        if ($name === '') {
            return null;
        }

        return ['name' => $name, 'args' => $args];
    }

    public static function looksLikeSlashCommand(string $rawMessage): bool
    {
        return self::tryParseCommand($rawMessage) !== null;
    }
}
