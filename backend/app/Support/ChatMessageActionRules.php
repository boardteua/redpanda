<?php

namespace App\Support;

use App\Models\ChatMessage;
use App\Models\ChatSetting;
use App\Models\Room;
use App\Models\User;

/**
 * Єдине джерело правил редагування/видалення повідомлень (T36/T37/T58) — політика та батч-мапа стрічки (T105).
 */
final class ChatMessageActionRules
{
    public static function canUpdate(User $viewer, ChatMessage $message, ?User $author, ?Room $room): bool
    {
        if ($message->post_deleted_at !== null) {
            return false;
        }

        if ($message->type === 'client_only') {
            return false;
        }

        return self::canModifyPublicMessage($viewer, $message, $author, $room);
    }

    public static function canDelete(User $viewer, ChatMessage $message, ?User $author, ?Room $room): bool
    {
        if ($message->type === 'client_only') {
            if ($viewer->guest) {
                return false;
            }

            return (int) $message->user_id === (int) $viewer->id;
        }

        return self::canModifyPublicMessage($viewer, $message, $author, $room);
    }

    private static function canModifyPublicMessage(User $viewer, ChatMessage $message, ?User $author, ?Room $room): bool
    {
        if ($viewer->guest) {
            return false;
        }

        if ($message->type !== 'public') {
            return false;
        }

        if ($viewer->isChatAdmin()) {
            return true;
        }

        if ($viewer->canModerate()) {
            return $author !== null && ! $author->isChatAdmin();
        }

        if (self::isRoomCreatorModeratorForMessage($viewer, $message, $author, $room)) {
            return true;
        }

        if ((int) $message->user_id !== (int) $viewer->id) {
            return false;
        }

        if ($viewer->isVip()) {
            return true;
        }

        $hours = once(fn (): int => ChatSetting::current()->effectiveMessageEditWindowHours());
        $ageSeconds = time() - (int) $message->post_date;

        return $ageSeconds <= $hours * 3600;
    }

    private static function isRoomCreatorModeratorForMessage(
        User $viewer,
        ChatMessage $message,
        ?User $author,
        ?Room $room,
    ): bool {
        if ($room === null || $room->created_by_user_id === null) {
            return false;
        }

        if ((int) $room->created_by_user_id !== (int) $viewer->id) {
            return false;
        }

        if ((int) $message->user_id === (int) $viewer->id) {
            return false;
        }

        if ($author === null) {
            return false;
        }

        if ($author->isChatAdmin() || $author->canModerate()) {
            return false;
        }

        return true;
    }
}
