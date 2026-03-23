<?php

namespace App\Chat\SlashCommands\Handlers;

use App\Chat\SlashCommands\Contracts\SlashCommandHandlerContract;
use App\Chat\SlashCommands\SlashCommandContext;
use App\Chat\SlashCommands\SlashCommandOutcome;
use App\Chat\SlashCommands\Support\ModerationSlashCommandHelper;

/**
 * /upon — дозволити завантаження зображень у чаті; /upoff — заборонити (поле users.chat_upload_disabled).
 */
final class ChatUploadGatingSlashCommandHandler implements SlashCommandHandlerContract
{
    public function __construct(
        private readonly bool $enableUpload,
    ) {}

    public function handle(SlashCommandContext $context, string $commandName, string $args): SlashCommandOutcome
    {
        $deny = ModerationSlashCommandHelper::requireModerator($context->user);
        if ($deny !== null) {
            return $deny;
        }

        [$nick, $err] = ModerationSlashCommandHelper::parseNickOnly($args);
        if ($err === 'need_nick') {
            $hint = $this->enableUpload ? 'Вкажіть нік: /upon нік' : 'Вкажіть нік: /upoff нік';

            return SlashCommandOutcome::httpError(422, $hint);
        }
        if ($err === 'extra_tokens') {
            return SlashCommandOutcome::httpError(422, 'Забагато аргументів.');
        }

        $target = ModerationSlashCommandHelper::findUserByNick($nick);
        if ($target === null) {
            return SlashCommandOutcome::httpError(422, 'Користувача з таким ніком не знайдено.');
        }

        if ($target->guest) {
            return SlashCommandOutcome::httpError(422, 'Для гостя ця команда недоступна.');
        }

        $denyAct = ModerationSlashCommandHelper::assertStaffCanAct($context->user, $target);
        if ($denyAct !== null) {
            return $denyAct;
        }

        $disabled = ! $this->enableUpload;
        $target->forceFill(['chat_upload_disabled' => $disabled])->save();
        $target->refresh();

        $msg = $this->enableUpload
            ? 'Завантаження зображень увімкнено для «'.$target->user_name.'».'
            : 'Завантаження зображень вимкнено для «'.$target->user_name.'».';

        return SlashCommandOutcome::clientOnlyMessage($msg, [
            'name' => $commandName,
            'recognized' => true,
        ]);
    }
}
