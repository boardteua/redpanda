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

    if (! Gate::forUser($user)->allows('interact', $room)) {
        return false;
    }

    $role = $user->resolveChatRole();

    /** @var array<string, mixed> Presence payload for Echo.join / Pusher presence (id обов’язковий). */
    return [
        'id' => $user->id,
        'user_name' => $user->user_name,
        'guest' => (bool) $user->guest,
        'avatar_url' => $user->resolveAvatarUrl() ?? '',
        'chat_role' => $role->value,
        'badge_color' => $role->badgeColor(),
    ];
});

Broadcast::channel('user.{id}', function (User $user, int|string $id) {
    return (int) $user->id === (int) $id;
});
