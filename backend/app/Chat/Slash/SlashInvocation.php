<?php

namespace App\Chat\Slash;

use App\Models\Room;
use App\Models\User;

final readonly class SlashInvocation
{
    public function __construct(
        public User $user,
        public Room $room,
        public string $trimmedRawLine,
        public SlashParsedCommand $parsed,
    ) {}
}
