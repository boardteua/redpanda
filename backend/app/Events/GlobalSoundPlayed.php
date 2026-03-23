<?php

namespace App\Events;

use App\Models\Room;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Глобальний сигнал від /gsound (T71). */
class GlobalSoundPlayed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $actorUserId,
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
        return 'GlobalSoundPlayed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'actor_user_id' => $this->actorUserId,
        ];
    }
}
