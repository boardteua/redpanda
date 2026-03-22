<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * Інлайн-приват у стрічці кімнати: лише приватні канали учасників пари (T25).
 */
class RoomInlinePrivatePosted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ChatMessage $message) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $m = $this->message;
        $recipientId = (int) $m->post_target;
        $senderId = (int) $m->user_id;

        return [
            new PrivateChannel('user.'.$senderId),
            new PrivateChannel('user.'.$recipientId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'RoomInlinePrivatePosted';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $m = $this->message;
        $fileId = (int) $m->file;
        $image = null;
        if ($fileId > 0) {
            $image = [
                'id' => $fileId,
                'url' => URL::route('api.v1.chat-images.file', ['image' => $fileId], true),
            ];
        }

        return [
            'post_id' => $m->post_id,
            'post_roomid' => (int) $m->post_roomid,
            'user_id' => (int) $m->user_id,
            'post_date' => (int) $m->post_date,
            'post_time' => $m->post_time,
            'post_user' => $m->post_user,
            'avatar' => $m->avatar,
            'post_message' => $m->post_message,
            'post_color' => $m->post_color,
            'type' => $m->type,
            'client_message_id' => $m->client_message_id,
            'file' => $fileId,
            'image' => $image,
            'recipient_user_id' => (int) $m->post_target,
        ];
    }
}
