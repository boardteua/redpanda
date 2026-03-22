<?php

namespace App\Policies;

use App\Models\ChatMessage;
use App\Models\Room;
use App\Models\User;

class ChatMessagePolicy
{
    public function update(User $user, ChatMessage $message): bool
    {
        if ($message->post_deleted_at !== null) {
            return false;
        }

        return $this->canModifyPublicMessage($user, $message);
    }

    public function delete(User $user, ChatMessage $message): bool
    {
        return $this->canModifyPublicMessage($user, $message);
    }

    /**
     * Хто може змінювати або видаляти публічний рядок (видалення дозволено й для вже soft-deleted — ідемпотентний DELETE).
     */
    private function canModifyPublicMessage(User $user, ChatMessage $message): bool
    {
        if ($user->guest) {
            return false;
        }

        if ($message->type !== 'public') {
            return false;
        }

        if ($user->isChatAdmin()) {
            return true;
        }

        if ($user->canModerate()) {
            $author = User::query()->find($message->user_id);

            return $author !== null && ! $author->isChatAdmin();
        }

        if ($this->isRoomCreatorModeratorForMessage($user, $message)) {
            return true;
        }

        if ((int) $message->user_id !== (int) $user->id) {
            return false;
        }

        if ($user->isVip()) {
            return true;
        }

        $hours = max(1, (int) config('chat.message_edit_window_hours', 24));
        $ageSeconds = time() - (int) $message->post_date;

        return $ageSeconds <= $hours * 3600;
    }

    /**
     * Творець кімнати (**T58**): редагування/видалення чужих публічних повідомлень лише в своїй кімнаті;
     * без дій щодо постів глобального персоналу модерації/адміна.
     */
    private function isRoomCreatorModeratorForMessage(User $user, ChatMessage $message): bool
    {
        /** @var Room|null $room */
        $room = $message->room;

        if ($room === null || $room->created_by_user_id === null) {
            return false;
        }

        if ((int) $room->created_by_user_id !== (int) $user->id) {
            return false;
        }

        if ((int) $message->user_id === (int) $user->id) {
            return false;
        }

        $author = User::query()->find($message->user_id);

        if ($author === null) {
            return false;
        }

        if ($author->isChatAdmin() || $author->canModerate()) {
            return false;
        }

        return true;
    }
}
