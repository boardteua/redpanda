<?php

namespace App\Services\Push;

use App\Models\Room;
use App\Models\User;
use App\Models\UserIgnore;
use App\Models\UserWebPushPrivatePeerMute;
use App\Models\UserWebPushRoomMute;

class WebPushPreferenceEvaluator
{
    public function shouldDeliverRoomWebPush(?User $user, Room $room): bool
    {
        if ($user === null || ! $user->web_push_master_enabled) {
            return false;
        }

        return ! UserWebPushRoomMute::query()
            ->where('user_id', $user->id)
            ->where('room_id', $room->room_id)
            ->exists();
    }

    public function shouldDeliverRoomWebPushFromAuthor(?User $recipient, Room $room, int $authorId): bool
    {
        if (! $this->shouldDeliverRoomWebPush($recipient, $room)) {
            return false;
        }

        if ($recipient === null || $recipient->guest) {
            return false;
        }

        return ! $this->isIgnoring($recipient, $authorId);
    }

    public function shouldDeliverPrivateWebPush(User $recipient, int $senderId): bool
    {
        if (! $recipient->web_push_master_enabled) {
            return false;
        }

        if ($this->isIgnoring($recipient, $senderId)) {
            return false;
        }

        return ! UserWebPushPrivatePeerMute::query()
            ->where('user_id', $recipient->id)
            ->where('peer_user_id', $senderId)
            ->exists();
    }

    private function isIgnoring(User $recipient, int $authorId): bool
    {
        return UserIgnore::query()
            ->where('user_id', (int) $recipient->id)
            ->where('ignored_user_id', $authorId)
            ->exists();
    }
}
