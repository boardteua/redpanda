<?php

namespace App\Chat\SlashCommands\Handlers;

use App\Chat\SlashCommands\Contracts\SlashCommandHandlerContract;
use App\Chat\SlashCommands\SlashCommandContext;
use App\Chat\SlashCommands\SlashCommandOutcome;
use App\Chat\SlashCommands\Support\ModerationSlashCommandHelper;
use App\Services\Moderation\ModerationService;

final class MuteSlashCommandHandler implements SlashCommandHandlerContract
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

        [$nick, $minOpt, $err] = ModerationSlashCommandHelper::parseNickAndOptionalMinutes($args);
        $pe = ModerationSlashCommandHelper::outcomeForParseError($err, 'Вкажіть нік: /mute нік [хвилини]');
        if ($pe !== null) {
            return $pe;
        }

        $target = ModerationSlashCommandHelper::findUserByNick($nick);
        if ($target === null) {
            return SlashCommandOutcome::httpError(422, 'Користувача з таким ніком не знайдено.');
        }

        $denyAct = ModerationSlashCommandHelper::assertStaffCanAct($context->user, $target);
        if ($denyAct !== null) {
            return $denyAct;
        }

        if ($minOpt === null) {
            $minutes = (int) config('chat.slash_mod_default_mute_minutes', 30);
        } elseif ($minOpt === 0) {
            $minutes = null;
        } else {
            $minutes = $minOpt;
        }

        if ($minutes !== null && ($minutes < 1 || $minutes > 525600)) {
            return SlashCommandOutcome::httpError(422, 'Хвилини мута мають бути від 1 до 525600 або 0 для зняття.');
        }

        $this->moderation->muteUser($target, $minutes);
        $target->refresh();

        $msg = $minutes === null
            ? 'Мут знято для «'.$target->user_name.'».'
            : 'Користувачу «'.$target->user_name.'» видано мут на '.$minutes.' хв.';

        return SlashCommandOutcome::clientOnlyMessage($msg, [
            'name' => 'mute',
            'recognized' => true,
        ]);
    }
}
