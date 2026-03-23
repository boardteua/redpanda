<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Публічний журнал кімнати очищено (slash /clear); клієнти прибирають public-пости зі стрічки.
 */
class RoomJournalCleared implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $roomId,
        public int $clearedByUserId,
    ) {}

    /**
     * @return array<int, PresenceChannel>
     */
    public function broadcastOn(): array
    {
        return [new PresenceChannel('room.'.$this->roomId)];
    }

    public function broadcastAs(): string
    {
        return 'RoomJournalCleared';
    }

    /**
     * @return array<string, int>
     */
    public function broadcastWith(): array
    {
        return [
            'room_id' => $this->roomId,
            'cleared_by_user_id' => $this->clearedByUserId,
        ];
    }
}
