<?php

namespace App\Policies;

use App\Models\ChatMessage;
use App\Models\User;
use App\Support\ChatMessageActionRules;

class ChatMessagePolicy
{
    public function update(User $user, ChatMessage $message): bool
    {
        $message->loadMissing('room');
        $author = User::query()->find($message->user_id);

        return ChatMessageActionRules::canUpdate($user, $message, $author, $message->room);
    }

    public function delete(User $user, ChatMessage $message): bool
    {
        $message->loadMissing('room');
        $author = User::query()->find($message->user_id);

        return ChatMessageActionRules::canDelete($user, $message, $author, $message->room);
    }
}
