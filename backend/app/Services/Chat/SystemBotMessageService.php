<?php

namespace App\Services\Chat;

use App\Events\MessagePosted;
use App\Models\ChatMessage;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Внутрішні пости від імені системного бота (без розширення прав звичайних користувачів).
 */
final class SystemBotMessageService
{
    public const KIND_ROOM_JOIN = 'room_join';

    public const KIND_ROOM_WELCOME = 'room_welcome';

    public const KIND_NEW_PUBLIC_ROOM = 'new_public_room';

    public function botUser(): ?User
    {
        $id = config('chat.system_bot_user_id');
        if ($id !== null && $id > 0) {
            $u = User::query()->whereKey($id)->where('is_system_bot', true)->first();

            return $u;
        }

        return User::query()->where('is_system_bot', true)->orderBy('id')->first();
    }

    /**
     * @param  self::KIND_*  $kind
     */
    public function postSystemMessage(
        Room $displayRoom,
        string $kind,
        string $plainMessage,
        ?int $targetRoomId = null,
        ?string $actionLabel = null,
    ): ?ChatMessage {
        $bot = $this->botUser();
        if ($bot === null) {
            return null;
        }

        $now = time();
        $avatarUrl = $bot->resolveAvatarUrl();

        $message = ChatMessage::query()->create([
            'user_id' => $bot->id,
            'post_date' => $now,
            'post_time' => date('H:i', $now),
            'post_user' => $bot->user_name,
            'post_message' => $plainMessage,
            'post_style' => null,
            'post_color' => 'system',
            'post_roomid' => $displayRoom->room_id,
            'type' => 'system',
            'system_kind' => $kind,
            'system_target_room_id' => $targetRoomId,
            'system_action_label' => $actionLabel,
            'post_target' => null,
            'avatar' => $avatarUrl,
            'file' => 0,
            'client_message_id' => (string) Str::uuid(),
            'moderation_flag_at' => null,
        ]);

        Log::info('chat.system_bot.post', [
            'kind' => $kind,
            'post_id' => $message->post_id,
            'display_room_id' => $displayRoom->room_id,
            'target_room_id' => $targetRoomId,
        ]);

        broadcast(new MessagePosted($message));

        return $message;
    }
}
