<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Обидва учасники треду отримують подію на своєму каналі user.{id} (T68).
 */
class PrivateThreadCleared implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $clearedByUserId,
        public int $peerOneId,
        public int $peerTwoId,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.'.$this->peerOneId),
            new PrivateChannel('user.'.$this->peerTwoId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'PrivateThreadCleared';
    }

    /**
     * @return array<string, int>
     */
    public function broadcastWith(): array
    {
        return [
            'cleared_by_user_id' => $this->clearedByUserId,
            'peer_one_id' => $this->peerOneId,
            'peer_two_id' => $this->peerTwoId,
        ];
    }
}
