<?php

namespace App\Events;

use App\Models\PrivateMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PrivateMessageCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public PrivateMessage $message) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.'.$this->message->recipient_id)];
    }

    public function broadcastAs(): string
    {
        return 'PrivateMessagePosted';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $this->message->loadMissing('sender');
        $m = $this->message;

        return [
            'id' => $m->id,
            'sender_id' => (int) $m->sender_id,
            'recipient_id' => (int) $m->recipient_id,
            'body' => $m->body,
            'sent_at' => (int) $m->sent_at,
            'sent_time' => $m->sent_time,
            'sender_user_name' => $m->sender->user_name,
            'client_message_id' => $m->client_message_id,
        ];
    }
}
