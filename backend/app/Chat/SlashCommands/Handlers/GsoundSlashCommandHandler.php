<?php

namespace App\Chat\SlashCommands\Handlers;

use App\Chat\SlashCommands\Contracts\SlashCommandHandlerContract;
use App\Chat\SlashCommands\SlashCommandContext;
use App\Chat\SlashCommands\SlashCommandOutcome;
use App\Chat\SlashCommands\Support\AdminSlashCommandHelper;
use App\Events\GlobalSoundPlayed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

final class GsoundSlashCommandHandler implements SlashCommandHandlerContract
{
    public function handle(SlashCommandContext $context, string $commandName, string $args): SlashCommandOutcome
    {
        $deny = AdminSlashCommandHelper::requireChatAdmin($context->user);
        if ($deny !== null) {
            return $deny;
        }

        if (trim($args) !== '') {
            return SlashCommandOutcome::httpError(422, 'Команда /gsound не приймає аргументів.');
        }

        $uid = (int) $context->user->id;
        $allowed = RateLimiter::attempt(
            'slash-gsound:'.$uid,
            15,
            static fn (): bool => true,
            3600,
        );
        if (! $allowed) {
            $retry = RateLimiter::availableIn('slash-gsound:'.$uid);

            return SlashCommandOutcome::httpError(
                429,
                'Забагато /gsound за годину. Спробуйте через '.$retry.' с.',
            );
        }

        broadcast(new GlobalSoundPlayed($uid));

        Log::info('chat.slash_command.gsound', [
            'actor_id' => $uid,
        ]);

        return SlashCommandOutcome::clientOnlyMessage('Глобальний звуковий сигнал надіслано.', [
            'name' => 'gsound',
            'recognized' => true,
        ]);
    }
}
