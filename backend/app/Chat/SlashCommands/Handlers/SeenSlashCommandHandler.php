<?php

namespace App\Chat\SlashCommands\Handlers;

use App\Chat\SlashCommands\Contracts\SlashCommandHandlerContract;
use App\Chat\SlashCommands\SlashCommandContext;
use App\Chat\SlashCommands\SlashCommandOutcome;
use App\Models\ChatMessage;
use App\Models\User;
use App\Services\Chat\RoomPresenceStatusCache;

final class SeenSlashCommandHandler implements SlashCommandHandlerContract
{
    public function handle(SlashCommandContext $context, string $commandName, string $args): SlashCommandOutcome
    {
        $trimmed = trim($args);
        if ($trimmed === '') {
            return SlashCommandOutcome::httpError(422, 'Вкажіть нік: /seen нік');
        }

        $nick = preg_split('/\s+/u', $trimmed, 2)[0] ?? '';
        if ($nick === '') {
            return SlashCommandOutcome::httpError(422, 'Вкажіть нік: /seen нік');
        }

        $target = User::query()
            ->whereRaw('LOWER(user_name) = LOWER(?)', [$nick])
            ->first();

        if ($target === null) {
            return SlashCommandOutcome::httpError(422, 'Користувача з таким ніком не знайдено.');
        }

        $roomId = (int) $context->room->room_id;

        $last = ChatMessage::query()
            ->where('post_roomid', $roomId)
            ->where('user_id', $target->id)
            ->whereNull('post_deleted_at')
            ->whereIn('type', ['public', 'inline_private'])
            ->orderByDesc('post_id')
            ->first();

        $presence = RoomPresenceStatusCache::get($roomId, (int) $target->id);
        $presenceLabel = match ($presence) {
            'online' => 'онлайн',
            'away' => 'відійшов',
            'inactive' => 'неактивний',
            default => null,
        };

        $lines = ['Користувач «'.$target->user_name.'»:'];
        if ($last !== null) {
            $ts = (int) $last->post_date;
            $lines[] = 'Останнє повідомлення в цій кімнаті: '.date('d.m.Y H:i', $ts).' ('.$last->post_time.').';
        } else {
            $lines[] = 'У цій кімнаті ще не було повідомлень від цього користувача.';
        }

        if ($presenceLabel !== null) {
            $lines[] = 'Статус у списку присутності: '.$presenceLabel.'.';
        } else {
            $lines[] = 'Статус присутності: дані відсутні або застарілі (Echo / heartbeat).';
        }

        return SlashCommandOutcome::clientOnlyMessage(implode("\n", $lines), [
            'name' => 'seen',
            'recognized' => true,
        ]);
    }
}
