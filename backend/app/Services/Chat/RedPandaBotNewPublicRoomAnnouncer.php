<?php

namespace App\Services\Chat;

use App\Models\Room;

/**
 * Оголошення про нову публічну кімнату в «хабі» (T149; кімната — див. config / QA).
 */
final class RedPandaBotNewPublicRoomAnnouncer
{
    public function __construct(
        private readonly SystemBotMessageService $botMessages,
    ) {}

    public function announce(Room $newRoom): void
    {
        if ((int) $newRoom->access !== Room::ACCESS_PUBLIC) {
            return;
        }

        $announceRoomId = config('chat.bot_announce_room_id');
        if ($announceRoomId === null || $announceRoomId < 1) {
            $announceRoomId = Room::query()
                ->where('access', Room::ACCESS_PUBLIC)
                ->orderBy('room_id')
                ->value('room_id');
        }

        if ($announceRoomId === null) {
            return;
        }

        $announceRoom = Room::query()->whereKey($announceRoomId)->first();
        if ($announceRoom === null) {
            return;
        }

        $name = trim($newRoom->room_name);
        if ($name === '') {
            $name = '#'.$newRoom->room_id;
        }
        $name = mb_substr(str_replace(["\r", "\n"], ' ', $name), 0, 191);

        $text = __('chat.bot.new_public_room', ['name' => $name]);
        $label = __('chat.bot.action_go_to_room');

        $this->botMessages->postSystemMessage(
            $announceRoom,
            SystemBotMessageService::KIND_NEW_PUBLIC_ROOM,
            $text,
            (int) $newRoom->room_id,
            $label,
        );
    }
}
