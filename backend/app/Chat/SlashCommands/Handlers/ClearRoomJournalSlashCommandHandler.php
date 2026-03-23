<?php

namespace App\Chat\SlashCommands\Handlers;

use App\Chat\SlashCommands\Contracts\SlashCommandHandlerContract;
use App\Chat\SlashCommands\SlashCommandContext;
use App\Chat\SlashCommands\SlashCommandOutcome;
use App\Events\RoomJournalCleared;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class ClearRoomJournalSlashCommandHandler implements SlashCommandHandlerContract
{
    public function handle(SlashCommandContext $context, string $commandName, string $args): SlashCommandOutcome
    {
        if ($args !== '' && trim($args) !== '') {
            return SlashCommandOutcome::httpError(
                422,
                'Команда /clear не приймає аргументів.',
            );
        }

        if (! Gate::forUser($context->user)->allows('updateDetails', $context->room)) {
            return SlashCommandOutcome::httpError(
                403,
                'Недостатньо прав для очищення журналу кімнати.',
            );
        }

        $now = time();
        $roomId = (int) $context->room->room_id;

        DB::transaction(function () use ($roomId, $now): void {
            ChatMessage::query()
                ->where('post_roomid', $roomId)
                ->where('type', 'public')
                ->whereNull('post_deleted_at')
                ->update([
                    'post_deleted_at' => $now,
                    'post_message' => '',
                    'file' => 0,
                    'post_style' => null,
                ]);
        });

        broadcast(new RoomJournalCleared($roomId, (int) $context->user->id));

        return SlashCommandOutcome::clientOnlyMessage('Журнал кімнати очищено.', [
            'name' => 'clear',
            'recognized' => true,
        ]);
    }
}
