<?php

namespace App\Chat\SlashCommands\Handlers;

use App\Chat\SlashCommands\Contracts\SlashCommandHandlerContract;
use App\Chat\SlashCommands\SlashCommandContext;
use App\Chat\SlashCommands\SlashCommandOutcome;
use App\Events\PresenceStatusUpdated;
use App\Services\Chat\RoomPresenceStatusCache;

final class AwaySlashCommandHandler implements SlashCommandHandlerContract
{
    public function handle(SlashCommandContext $context, string $commandName, string $args): SlashCommandOutcome
    {
        $roomId = (int) $context->room->room_id;
        $uid = (int) $context->user->id;
        $current = RoomPresenceStatusCache::get($roomId, $uid);
        $next = ($current === 'away') ? 'online' : 'away';

        $prev = RoomPresenceStatusCache::put($roomId, $uid, $next);
        if ($prev !== $next) {
            broadcast(new PresenceStatusUpdated($roomId, $uid, $next));
        }

        $line = $next === 'away'
            ? 'Статус у кімнаті: відійшов.'
            : 'Статус у кімнаті: онлайн.';

        return SlashCommandOutcome::clientOnlyMessage($line, [
            'name' => 'away',
            'recognized' => true,
        ]);
    }
}
