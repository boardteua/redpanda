<?php

namespace App\Chat\SlashCommands\Handlers;

use App\Chat\SlashCommands\Contracts\SlashCommandHandlerContract;
use App\Chat\SlashCommands\SlashCommandContext;
use App\Chat\SlashCommands\SlashCommandOutcome;
use App\Chat\SlashCommands\Support\AdminSlashCommandHelper;
use Illuminate\Support\Facades\Log;

/** /invisible та /visible — приховати/показати себе в списку присутності (T71). */
final class InvisibleVisibilitySlashCommandHandler implements SlashCommandHandlerContract
{
    public function handle(SlashCommandContext $context, string $commandName, string $args): SlashCommandOutcome
    {
        $deny = AdminSlashCommandHelper::requireChatAdmin($context->user);
        if ($deny !== null) {
            return $deny;
        }

        $trimArgs = trim($args);
        if ($trimArgs !== '') {
            return SlashCommandOutcome::httpError(422, 'Команда /'.$commandName.' не приймає аргументів.');
        }

        $on = $commandName === 'invisible';
        $user = $context->user;
        $user->forceFill(['presence_invisible' => $on])->save();

        Log::info('chat.slash_command.presence_invisible', [
            'command' => $commandName,
            'user_id' => (int) $user->id,
            'presence_invisible' => $on,
        ]);

        $msg = $on
            ? 'Невидимість увімкнено. Перепідключіть кімнату (Echo), щоб зникнути зі списку «Онлайн».'
            : 'Невидимість вимкнено.';

        return SlashCommandOutcome::clientOnlyMessage($msg, [
            'name' => $commandName,
            'recognized' => true,
            'reconnect_echo' => true,
        ]);
    }
}
