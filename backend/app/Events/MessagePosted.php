<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessagePosted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ChatMessage $message) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('room.'.$this->message->post_roomid)];
    }

    public function broadcastAs(): string
    {
        return 'MessagePosted';
    }

    /**
     * Мінімальний payload для клієнта (дедуп по post_id / client_message_id).
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $m = $this->message;

        return [
            'post_id' => $m->post_id,
            'post_roomid' => (int) $m->post_roomid,
            'user_id' => (int) $m->user_id,
            'post_date' => (int) $m->post_date,
            'post_time' => $m->post_time,
            'post_user' => $m->post_user,
            'post_message' => $m->post_message,
            'post_color' => $m->post_color,
            'type' => $m->type,
            'client_message_id' => $m->client_message_id,
        ];
    }
}
