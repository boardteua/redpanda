<?php

use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Gate;

Broadcast::channel('room.{roomId}', function (User $user, int|string $roomId) {
    $room = Room::query()->find((int) $roomId);

    if ($room === null) {
        return false;
    }

    return Gate::forUser($user)->allows('interact', $room);
});

Broadcast::channel('user.{id}', function (User $user, int|string $id) {
    return (int) $user->id === (int) $id;
});
