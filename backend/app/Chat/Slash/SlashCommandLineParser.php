<?php

namespace App\Chat\Slash;

/**
 * Парсер рядка композера після ltrim (узгоджено з RoomInlinePrivateParser).
 */
final class SlashCommandLineParser
{
    /**
     * @return SlashParsedCommand|null null якщо рядок не починається з «/»
     */
    public static function parse(string $trimmedLine): ?SlashParsedCommand
    {
        if ($trimmedLine === '' || $trimmedLine[0] !== '/') {
            return null;
        }

        $rest = substr($trimmedLine, 1);
        $space = strpos($rest, ' ');
        $command = $space === false ? $rest : substr($rest, 0, $space);
        $command = strtolower(trim($command));
        $args = $space === false ? '' : ltrim(substr($rest, $space + 1));

        return new SlashParsedCommand($command, $args);
    }
}
