<?php

namespace App\Support;

final class IdempotencyKey
{
    /**
     * Deterministic UUID-like value suitable for `chat.client_message_id`.
     *
     * Not cryptographically secret; do not use as auth token.
     */
    public static function toClientMessageId(string $scope, string $key): string
    {
        $hex = sha1($scope.':'.$key);

        // UUID v5-ish: tweak version/variant bits for a stable, valid-looking UUID.
        $timeHi = hexdec(substr($hex, 12, 4));
        $timeHi = ($timeHi & 0x0fff) | 0x5000;

        $clockSeq = hexdec(substr($hex, 16, 4));
        $clockSeq = ($clockSeq & 0x3fff) | 0x8000;

        return sprintf(
            '%s-%s-%04x-%04x-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            $timeHi,
            $clockSeq,
            substr($hex, 20, 12),
        );
    }
}

