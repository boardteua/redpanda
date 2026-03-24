<?php

namespace App\Support;

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Батч обчислення can_edit / can_delete для стрічки та архіву (T105).
 *
 * @param  Collection<int, ChatMessage>  $messages
 * @return array<int, array{can_edit: bool, can_delete: bool}> keyed by post_id
 */
final class ChatMessageListAbilityMap
{
    public static function forMessages(User $viewer, Collection $messages): array
    {
        if ($messages->isEmpty()) {
            return [];
        }

        $messages->loadMissing('room');

        $authorIds = $messages->pluck('user_id')->unique()->filter()->map(fn ($id) => (int) $id)->values()->all();
        $authors = $authorIds === []
            ? collect()
            : User::query()->whereIn('id', $authorIds)->get()->keyBy('id');

        $out = [];
        foreach ($messages as $message) {
            /** @var ChatMessage $message */
            $author = $authors->get((int) $message->user_id);
            $pid = (int) $message->post_id;
            $out[$pid] = [
                'can_edit' => ChatMessageActionRules::canUpdate($viewer, $message, $author, $message->room),
                'can_delete' => $message->post_deleted_at === null
                    && ChatMessageActionRules::canDelete($viewer, $message, $author, $message->room),
            ];
        }

        return $out;
    }
}
