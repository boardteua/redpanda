<?php

namespace App\Chat\Slash;

/**
 * Розбір першого токена після «/» і решти рядка (аргументи).
 */
final readonly class SlashParsedCommand
{
    public function __construct(
        public string $command,
        public string $args,
    ) {}
}
