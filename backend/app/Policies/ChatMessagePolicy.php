<?php

namespace App\Policies;

use App\Models\ChatMessage;
use App\Models\User;

class ChatMessagePolicy
{
    public function update(User $user, ChatMessage $message): bool
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
}
