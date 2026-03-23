<?php

namespace App\Chat\SlashCommands\Handlers;

use App\Chat\SlashCommands\Contracts\SlashCommandHandlerContract;
use App\Chat\SlashCommands\SlashCommandContext;
use App\Chat\SlashCommands\SlashCommandOutcome;
use App\Chat\SlashCommands\Support\ModerationSlashCommandHelper;
use App\Services\Moderation\ModerationService;

final class KickSlashCommandHandler implements SlashCommandHandlerContract
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
        $pe = ModerationSlashCommandHelper::outcomeForParseError($err, 'Вкажіть нік: /kick нік [хвилини]');
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
            $minutes = (int) config('chat.slash_mod_default_kick_minutes', 60);
        } elseif ($minOpt === 0) {
            $minutes = null;
        } else {
            $minutes = $minOpt;
        }

        if ($minutes !== null && ($minutes < 1 || $minutes > 525600)) {
            return SlashCommandOutcome::httpError(422, 'Хвилини відключення мають бути від 1 до 525600 або 0 для зняття.');
        }

        $this->moderation->kickUser($target, $minutes);
        $target->refresh();

        $msg = $minutes === null
            ? 'Відключення знято для «'.$target->user_name.'».'
            : 'Користувач «'.$target->user_name.'» тимчасово відключено на '.$minutes.' хв.';

        return SlashCommandOutcome::clientOnlyMessage($msg, [
            'name' => 'kick',
            'recognized' => true,
        ]);
    }
}
