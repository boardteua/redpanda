<?php

namespace App\Chat\SlashCommands\Handlers;

use App\Chat\SlashCommands\Contracts\SlashCommandHandlerContract;
use App\Chat\SlashCommands\SlashCommandContext;
use App\Chat\SlashCommands\SlashCommandOutcome;
use App\Chat\SlashCommands\Support\ModerationSlashCommandHelper;
use Illuminate\Support\Facades\Log;

final class BanSlashCommandHandler implements SlashCommandHandlerContract
{
    public function handle(SlashCommandContext $context, string $commandName, string $args): SlashCommandOutcome
    {
        $deny = ModerationSlashCommandHelper::requireModerator($context->user);
        if ($deny !== null) {
            return $deny;
        }

        if (! $context->user->isChatAdmin()) {
            return SlashCommandOutcome::httpError(
                403,
                'Команда /ban доступна лише адміністраторам чату. Для тимчасового відключення використайте /kick.',
            );
        }

        [$nick, $err] = ModerationSlashCommandHelper::parseNickOnly($args);
        if ($err === 'need_nick') {
            return SlashCommandOutcome::httpError(422, 'Вкажіть нік: /ban нік');
        }
        if ($err === 'extra_tokens') {
            return SlashCommandOutcome::httpError(422, 'Забагато аргументів.');
        }

        $target = ModerationSlashCommandHelper::findUserByNick($nick);
        if ($target === null) {
            return SlashCommandOutcome::httpError(422, 'Користувача з таким ніком не знайдено.');
        }

        if ($target->guest) {
            return SlashCommandOutcome::httpError(422, 'Обліковий запис гостя не блокується командою /ban.');
        }

        $denyAct = ModerationSlashCommandHelper::assertStaffCanAct($context->user, $target);
        if ($denyAct !== null) {
            return $denyAct;
        }

        $target->forceFill(['account_disabled_at' => now()])->save();
        $target->refresh();

        Log::info('chat.slash_command.ban', [
            'actor_id' => (int) $context->user->id,
            'target_user_id' => (int) $target->id,
        ]);

        return SlashCommandOutcome::clientOnlyMessage(
            'Обліковий запис «'.$target->user_name.'» вимкнено (бан).',
            [
                'name' => 'ban',
                'recognized' => true,
            ],
        );
    }
}
