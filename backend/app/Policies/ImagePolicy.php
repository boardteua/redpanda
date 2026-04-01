<?php

namespace App\Policies;

use App\Models\ChatMessage;
use App\Models\Image;
use App\Models\PrivateMessage;
use App\Models\Room;
use App\Models\User;

class ImagePolicy
{
    public function view(User $user, Image $image): bool
    {
        if ((int) $image->user_id === (int) $user->id) {
            return true;
        }

        if (User::query()->where('avatar_image_id', $image->id)->exists()) {
            return true;
        }

        /** Вкладення в приваті: перегляд мають відправник і отримувач (T195). */
        if (PrivateMessage::query()
            ->where('image_id', $image->id)
            ->where(function ($q) use ($user): void {
                $q->where('sender_id', $user->id)
                    ->orWhere('recipient_id', $user->id);
            })
            ->exists()) {
            return true;
        }

        $roomIds = ChatMessage::query()
            ->where('file', $image->id)
            ->distinct()
            ->pluck('post_roomid');

        if ($roomIds->isEmpty()) {
            return false;
        }

        $rooms = Room::query()->whereIn('room_id', $roomIds->all())->get();

        foreach ($rooms as $room) {
            if ($user->can('interact', $room)) {
                return true;
            }
        }

        return false;
    }
}
