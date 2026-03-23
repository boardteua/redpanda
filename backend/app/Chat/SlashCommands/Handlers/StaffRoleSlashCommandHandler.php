<?php

namespace App\Chat\SlashCommands\Handlers;

use App\Chat\SlashCommands\Contracts\SlashCommandHandlerContract;
use App\Chat\SlashCommands\SlashCommandContext;
use App\Chat\SlashCommands\SlashCommandOutcome;
use App\Chat\SlashCommands\Support\AdminSlashCommandHelper;
use App\Chat\SlashCommands\Support\ModerationSlashCommandHelper;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * /setuser, /setmod, /setvip, /setadmin — зміна ролі/статусу (узгоджено з StaffUserController).
 */
final class StaffRoleSlashCommandHandler implements SlashCommandHandlerContract
{
    public function __construct(
        private readonly string $operation,
    ) {}

    public function handle(SlashCommandContext $context, string $commandName, string $args): SlashCommandOutcome
    {
        $deny = AdminSlashCommandHelper::requireChatAdmin($context->user);
        if ($deny !== null) {
            return $deny;
        }

        [$nick, $err] = ModerationSlashCommandHelper::parseNickOnly($args);
        if ($err === 'need_nick') {
            return SlashCommandOutcome::httpError(422, 'Вкажіть нік: /'.$commandName.' нік');
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

        $actor = $context->user;

        $applyErr = match ($this->operation) {
            'setuser' => $this->applySetUser($target),
            'setmod' => $this->applySetMod($target),
            'setvip' => $this->applySetVip($target),
            'setadmin' => $this->applySetAdmin($actor, $target),
        };
        if ($applyErr !== null) {
            return $applyErr;
        }

        $target->refresh();

        Log::info('chat.slash_command.staff_role', [
            'command' => $commandName,
            'actor_id' => (int) $actor->id,
            'target_user_id' => (int) $target->id,
            'op' => $this->operation,
        ]);

        $label = match ($this->operation) {
            'setuser' => 'звичайним користувачем',
            'setmod' => 'модератором',
            'setvip' => 'VIP',
            'setadmin' => 'адміністратором',
            default => '',
        };

        return SlashCommandOutcome::clientOnlyMessage(
            'Користувача «'.$target->user_name.'» оновлено: '.$label.'.',
            [
                'name' => $commandName,
                'recognized' => true,
            ],
        );
    }

    private function applySetUser(User $target): ?SlashCommandOutcome
    {
        if ($target->guest) {
            return SlashCommandOutcome::httpError(422, 'Гостя не переводять у «звичайного користувача» цією командою.');
        }
        $target->forceFill([
            'user_rank' => User::RANK_USER,
            'vip' => false,
        ])->save();

        return null;
    }

    private function applySetMod(User $target): ?SlashCommandOutcome
    {
        if ($target->guest) {
            return SlashCommandOutcome::httpError(422, 'Гостя не призначають модератором.');
        }
        $target->forceFill([
            'user_rank' => User::RANK_MODERATOR,
            'vip' => false,
        ])->save();

        return null;
    }

    private function applySetVip(User $target): ?SlashCommandOutcome
    {
        if ($target->guest) {
            return SlashCommandOutcome::httpError(422, 'VIP недоступно для гостя.');
        }
        $target->forceFill(['vip' => true])->save();

        return null;
    }

    private function applySetAdmin(User $actor, User $target): ?SlashCommandOutcome
    {
        if ($target->guest) {
            return SlashCommandOutcome::httpError(422, 'Гостя не призначають адміністратором.');
        }
        $newRank = User::RANK_ADMIN;
        if ($newRank > (int) $actor->user_rank) {
            return SlashCommandOutcome::httpError(403, 'Неможливо призначити роль вищу за власну.');
        }
        $target->forceFill([
            'user_rank' => $newRank,
            'vip' => false,
        ])->save();

        return null;
    }
}
