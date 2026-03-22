<?php

namespace App\Chat;

/**
 * Парсинг інлайн-привату `/msg nickname …` у полі загального чату (T25).
 */
final class RoomInlinePrivateParser
{
    /**
     * @return array{nick: string, body: string}|null
     */
    public static function tryParse(string $raw): ?array
    {
        $trimmed = ltrim($raw);
        if (! preg_match('/^\/msg\s+(\S+)(?:\s+(.*))?$/ius', $trimmed, $m)) {
            return null;
        }

        $body = isset($m[2]) ? ltrim($m[2], " \t") : '';

        return [
            'nick' => $m[1],
            'body' => $body,
        ];
    }
}
