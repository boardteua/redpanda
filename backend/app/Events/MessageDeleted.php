<?php

namespace App\Events;

use App\Chat\RoomReplyPrefixMentionParser;
use App\Models\ChatMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ChatMessage $message) {}

    /**
     * @return array<int, PresenceChannel>
     */
    public function broadcastOn(): array
    {
        return [new PresenceChannel('room.'.$this->message->post_roomid)];
    }

    public function broadcastAs(): string
    {
        return 'MessageDeleted';
    }

    /**
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
            'post_edited_at' => $m->post_edited_at !== null ? (int) $m->post_edited_at : null,
            'post_deleted_at' => $m->post_deleted_at !== null ? (int) $m->post_deleted_at : null,
            'post_time' => $m->post_time,
            'post_user' => $m->post_user,
            'avatar' => $m->avatar,
            'post_message' => $m->post_message,
            'post_style' => $m->post_style,
            'post_color' => $m->post_color,
            'type' => $m->type,
            'client_message_id' => $m->client_message_id,
            'file' => (int) $m->file,
            'image' => null,
            'mentioned_user_ids' => RoomReplyPrefixMentionParser::mentionedUserIds($m),
        ];
    }
}
