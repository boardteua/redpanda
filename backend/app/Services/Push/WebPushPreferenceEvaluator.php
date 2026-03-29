<?php

namespace App\Services\Push;

use App\Models\Room;
use App\Models\User;
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

    public function shouldDeliverPrivateWebPush(User $recipient, int $senderId): bool
    {
        if (! $recipient->web_push_master_enabled) {
            return false;
        }

        return ! UserWebPushPrivatePeerMute::query()
            ->where('user_id', $recipient->id)
            ->where('peer_user_id', $senderId)
            ->exists();
    }
}
