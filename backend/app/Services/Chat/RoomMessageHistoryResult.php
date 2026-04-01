<?php

namespace App\Services\Chat;

use Illuminate\Support\Collection;

final class RoomMessageHistoryResult
{
    /**
     * @param  Collection<int, \App\Models\ChatMessage>  $messages
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly Collection $messages,
        public readonly array $meta,
    ) {}
}
