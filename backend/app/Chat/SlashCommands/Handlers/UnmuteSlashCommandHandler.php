<?php

namespace App\Chat\SlashCommands\Handlers;

use App\Chat\SlashCommands\Contracts\SlashCommandHandlerContract;
use App\Chat\SlashCommands\SlashCommandContext;
use App\Chat\SlashCommands\SlashCommandOutcome;
use App\Chat\SlashCommands\Support\ModerationSlashCommandHelper;
use App\Services\Moderation\ModerationService;

final class UnmuteSlashCommandHandler implements SlashCommandHandlerContract
{
    public function __construct(
        private readonly ModerationService $moderation,
    ) {}

    public function handle(SlashCommandContext $context, string $commandName, string $args): SlashCommandOutcome
    {
        $deny = ModerationSlashCommandHelper::requireModerator($context->user);
        if ($deny !== null) {
            return $deny;
        }

        [$nick, $err] = ModerationSlashCommandHelper::parseNickOnly($args);
        if ($err === 'need_nick') {
            return SlashCommandOutcome::httpError(422, 'Вкажіть нік: /unmute нік');
        }
        if ($err === 'extra_tokens') {
            return SlashCommandOutcome::httpError(422, 'Забагато аргументів.');
        }

        $target = ModerationSlashCommandHelper::findUserByNick($nick);
        if ($target === null) {
            return SlashCommandOutcome::httpError(422, 'Користувача з таким ніком не знайдено.');
        }

        $denyAct = ModerationSlashCommandHelper::assertStaffCanAct($context->user, $target);
        if ($denyAct !== null) {
            return $denyAct;
        }

        $this->moderation->muteUser($target, null);
        $target->refresh();

        return SlashCommandOutcome::clientOnlyMessage(
            'Мут знято для «'.$target->user_name.'».',
            [
                'name' => 'unmute',
                'recognized' => true,
            ],
        );
    }
}
