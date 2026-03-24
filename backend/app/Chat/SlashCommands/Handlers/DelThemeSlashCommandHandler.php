<?php

namespace App\Chat\SlashCommands\Handlers;

use App\Chat\SlashCommands\Contracts\SlashCommandHandlerContract;
use App\Chat\SlashCommands\SlashCommandContext;
use App\Chat\SlashCommands\SlashCommandOutcome;
use App\Chat\SlashCommands\Support\AdminSlashCommandHelper;
use App\Models\ChatTheme;

final class DelThemeSlashCommandHandler implements SlashCommandHandlerContract
{
    public function handle(SlashCommandContext $context, string $commandName, string $args): SlashCommandOutcome
    {
        $deny = AdminSlashCommandHelper::requireChatAdmin($context->user);
        if ($deny !== null) {
            return $deny;
        }

        $name = trim($args);
        if ($name === '') {
            return SlashCommandOutcome::httpError(422, 'Вкажіть назву: /deltheme Назва теми');
        }

        $row = ChatTheme::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
        if ($row === null) {
            return SlashCommandOutcome::httpError(422, 'Теми з такою назвою немає в каталозі.');
        }

        $removed = $row->name;
        $row->delete();

        $display = $context->displayUserName;

        return SlashCommandOutcome::publicRoomMessage(
            '*'.$display.' прибрав тему оформлення: '.$removed.'*',
            [
                'name' => 'deltheme',
                'recognized' => true,
            ],
        );
    }
}
