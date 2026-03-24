<?php

namespace App\Chat\SlashCommands\Handlers;

use App\Chat\SlashCommands\Contracts\SlashCommandHandlerContract;
use App\Chat\SlashCommands\SlashCommandContext;
use App\Chat\SlashCommands\SlashCommandOutcome;
use App\Chat\SlashCommands\Support\AdminSlashCommandHelper;
use App\Models\ChatTheme;
use Illuminate\Database\QueryException;

final class AddThemeSlashCommandHandler implements SlashCommandHandlerContract
{
    private const MAX_NAME = 64;

    public function handle(SlashCommandContext $context, string $commandName, string $args): SlashCommandOutcome
    {
        $deny = AdminSlashCommandHelper::requireChatAdmin($context->user);
        if ($deny !== null) {
            return $deny;
        }

        $name = trim($args);
        if ($name === '') {
            return SlashCommandOutcome::httpError(422, 'Вкажіть назву: /addtheme Назва теми');
        }

        if (mb_strlen($name) > self::MAX_NAME) {
            return SlashCommandOutcome::httpError(
                422,
                'Назва теми не може бути довшою за '.self::MAX_NAME.' символів.',
            );
        }

        if (ChatTheme::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->exists()) {
            return SlashCommandOutcome::httpError(422, 'Така тема вже є в каталозі.');
        }

        $nextOrder = (int) ChatTheme::query()->max('sort_order');
        try {
            ChatTheme::query()->create([
                'name' => $name,
                'sort_order' => $nextOrder + 1,
            ]);
        } catch (QueryException) {
            return SlashCommandOutcome::httpError(422, 'Така тема вже є в каталозі.');
        }

        $display = $context->displayUserName;

        return SlashCommandOutcome::publicRoomMessage(
            '*'.$display.' додав тему оформлення: '.$name.'*',
            [
                'name' => 'addtheme',
                'recognized' => true,
            ],
        );
    }
}
