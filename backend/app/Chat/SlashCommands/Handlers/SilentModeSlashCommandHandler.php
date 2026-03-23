<?php

namespace App\Chat\SlashCommands\Handlers;

use App\Chat\SlashCommands\Contracts\SlashCommandHandlerContract;
use App\Chat\SlashCommands\SlashCommandContext;
use App\Chat\SlashCommands\SlashCommandOutcome;
use App\Chat\SlashCommands\Support\AdminSlashCommandHelper;
use App\Events\ChatSilentModeUpdated;
use App\Models\ChatSetting;
use Illuminate\Support\Facades\Log;

/** /silent On|Off — глобальне приглушення звуків чату (T71). */
final class SilentModeSlashCommandHandler implements SlashCommandHandlerContract
{
    public function handle(SlashCommandContext $context, string $commandName, string $args): SlashCommandOutcome
    {
        $deny = AdminSlashCommandHelper::requireChatAdmin($context->user);
        if ($deny !== null) {
            return $deny;
        }

        $t = strtolower(trim($args));
        if ($t === '') {
            return SlashCommandOutcome::httpError(422, 'Вкажіть: /silent On або /silent Off');
        }

        $on = match ($t) {
            'on', '1', 'true', 'yes', 'увімк', 'увімкнути' => true,
            'off', '0', 'false', 'no', 'вимк', 'вимкнути' => false,
            default => null,
        };

        if ($on === null) {
            return SlashCommandOutcome::httpError(422, 'Очікується On або Off (наприклад /silent On).');
        }

        $row = ChatSetting::current();
        $row->silent_mode = $on;
        $row->save();

        broadcast(new ChatSilentModeUpdated($on));

        Log::info('chat.slash_command.silent_mode', [
            'actor_id' => (int) $context->user->id,
            'silent_mode' => $on,
        ]);

        $label = $on ? 'увімкнено' : 'вимкнено';

        return SlashCommandOutcome::clientOnlyMessage('Беззвучний режим чату '.$label.'.', [
            'name' => 'silent',
            'recognized' => true,
            'reload_chat_settings' => true,
        ]);
    }
}
