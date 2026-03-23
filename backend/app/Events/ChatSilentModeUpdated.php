<?php

namespace App\Events;

use App\Models\Room;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatSilentModeUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly bool $silentMode,
    ) {}

    /**
     * @return array<int, PresenceChannel>
     */
    public function broadcastOn(): array
    {
        return Room::query()
            ->orderBy('room_id')
            ->pluck('room_id')
            ->map(static fn (int $id) => new PresenceChannel('room.'.$id))
            ->all();
    }

    public function broadcastAs(): string
    {
        return 'ChatSilentModeUpdated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'silent_mode' => $this->silentMode,
        ];
    }
}
