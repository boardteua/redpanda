<?php

namespace App\Services\Chat;

use App\Models\Friendship;
use App\Models\PrivateMessage;
use App\Models\Room;
use App\Models\User;

/**
 * Хто може отримати {@see UserResource} цілі через GET profile-card у контексті кімнати.
 *
 * Окрім доступу {@see RoomPolicy::interact} до кімнати, переглядач має довести «зв’язок» з ціллю:
 * та сама особа, персонал модерації, недавній heartbeat присутності в цій кімнаті (T48),
 * прийнята дружба або наявність приватного листування.
 */
final class RoomUserProfileCardAuthorizer
{
    public static function allows(User $viewer, Room $room, User $target): bool
    {
        if ($viewer->guest) {
            return false;
        }

        if ((int) $viewer->id === (int) $target->id) {
            return true;
        }

        if ($viewer->canModerate()) {
            return true;
        }

        $roomId = (int) $room->room_id;
        $targetId = (int) $target->id;
        $viewerId = (int) $viewer->id;

        if (RoomPresenceStatusCache::get($roomId, $targetId) !== null) {
            return true;
        }

        if (self::hasAcceptedFriendship($viewerId, $targetId)) {
            return true;
        }

        if (self::hasPrivateConversation($viewerId, $targetId)) {
            return true;
        }

        return false;
    }

    private static function hasAcceptedFriendship(int $a, int $b): bool
    {
        return Friendship::query()
            ->where('status', Friendship::STATUS_ACCEPTED)
            ->where(function ($q) use ($a, $b): void {
                $q->where(function ($x) use ($a, $b): void {
                    $x->where('requester_id', $a)->where('addressee_id', $b);
                })->orWhere(function ($x) use ($a, $b): void {
                    $x->where('requester_id', $b)->where('addressee_id', $a);
                });
            })
            ->exists();
    }

    private static function hasPrivateConversation(int $a, int $b): bool
    {
        return PrivateMessage::query()
            ->where(function ($q) use ($a, $b): void {
                $q->where(function ($x) use ($a, $b): void {
                    $x->where('sender_id', $a)->where('recipient_id', $b);
                })->orWhere(function ($x) use ($a, $b): void {
                    $x->where('sender_id', $b)->where('recipient_id', $a);
                });
            })
            ->exists();
    }
}
