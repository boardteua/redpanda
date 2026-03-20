<?php

namespace App\Policies;

use App\Models\ChatMessage;
use App\Models\Image;
use App\Models\Room;
use App\Models\User;

class ImagePolicy
{
    public function view(User $user, Image $image): bool
    {
        if ((int) $image->user_id === (int) $user->id) {
            return true;
        }

        $roomIds = ChatMessage::query()
            ->where('file', $image->id)
            ->distinct()
            ->pluck('post_roomid');

        foreach ($roomIds as $roomId) {
            $room = Room::query()->where('room_id', $roomId)->first();
            if ($room !== null && $user->can('interact', $room)) {
                return true;
            }
        }

        return false;
    }
}
