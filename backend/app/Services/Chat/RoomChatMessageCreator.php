<?php

namespace App\Services\Chat;

use App\Models\ChatMessage;
use App\Models\PrivateMessage;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class RoomChatMessageCreator
{
    /**
     * @param  array<string, mixed>|null  $postStyle
     */
    public function createPublic(
        User $user,
        Room $room,
        string $message,
        ?array $postStyle,
        int $fileRef,
        string $clientId,
        ?int $moderationFlagAt = null,
    ): ChatMessage {
        $now = time();

        return ChatMessage::query()->create([
            'user_id' => $user->id,
            'post_date' => $now,
            'post_time' => date('H:i', $now),
            'post_user' => $user->user_name,
            'post_message' => $message,
            'post_style' => $postStyle,
            'post_color' => $user->resolveChatRole()->postColorClass(),
            'post_roomid' => $room->room_id,
            'type' => 'public',
            'post_target' => null,
            'avatar' => $user->resolveAvatarUrl(),
            'file' => $fileRef,
            'client_message_id' => $clientId,
            'moderation_flag_at' => $moderationFlagAt,
        ]);
    }

    public function createClientOnly(
        User $user,
        Room $room,
        string $message,
        string $clientId,
    ): ChatMessage {
        $now = time();

        return ChatMessage::query()->create([
            'user_id' => $user->id,
            'post_date' => $now,
            'post_time' => date('H:i', $now),
            'post_user' => $user->user_name,
            'post_message' => $message,
            'post_style' => null,
            'post_color' => $user->resolveChatRole()->postColorClass(),
            'post_roomid' => $room->room_id,
            'type' => 'client_only',
            'post_target' => null,
            'avatar' => $user->resolveAvatarUrl(),
            'file' => 0,
            'client_message_id' => $clientId,
            'moderation_flag_at' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $postStyle
     * @return array{message: ChatMessage, private: PrivateMessage}
     */
    public function createInlinePrivate(
        User $user,
        User $peer,
        Room $room,
        string $message,
        string $clientId,
        ?array $postStyle,
    ): array {
        $now = time();
        $avatarUrl = $user->resolveAvatarUrl();
        $chatMessage = null;
        $privateMessage = null;

        DB::transaction(function () use ($user, $peer, $room, $message, $clientId, $postStyle, $now, $avatarUrl, &$chatMessage, &$privateMessage): void {
            $chatMessage = ChatMessage::query()->create([
                'user_id' => $user->id,
                'post_date' => $now,
                'post_time' => date('H:i', $now),
                'post_user' => $user->user_name,
                'post_message' => $message,
                'post_style' => $postStyle,
                'post_color' => $user->resolveChatRole()->postColorClass(),
                'post_roomid' => $room->room_id,
                'type' => 'inline_private',
                'post_target' => (string) $peer->id,
                'avatar' => $avatarUrl,
                'file' => 0,
                'client_message_id' => $clientId,
            ]);

            $privateMessage = PrivateMessage::query()->create([
                'sender_id' => $user->id,
                'recipient_id' => $peer->id,
                'body' => $message,
                'sent_at' => $now,
                'sent_time' => date('H:i', $now),
                'client_message_id' => $clientId,
            ]);
        });

        /** @var ChatMessage $chatMessage */
        /** @var PrivateMessage $privateMessage */
        return ['message' => $chatMessage, 'private' => $privateMessage];
    }
}
