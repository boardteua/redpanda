<?php

namespace App\Chat\SlashCommands;

use App\Models\Room;
use App\Models\User;

final readonly class SlashCommandContext
{
    public function __construct(
        public User $user,
        public Room $room,
        public string $displayUserName,
        /** Для /global: idempotency у поточній кімнаті (client_message_id з POST). */
        public ?string $clientMessageId = null,
    ) {}
}
