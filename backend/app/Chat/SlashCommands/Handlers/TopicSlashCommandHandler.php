<?php

namespace App\Chat\SlashCommands\Handlers;

use App\Chat\SlashCommands\Contracts\SlashCommandHandlerContract;
use App\Chat\SlashCommands\SlashCommandContext;
use App\Chat\SlashCommands\SlashCommandOutcome;
use Illuminate\Support\Facades\Gate;

final class TopicSlashCommandHandler implements SlashCommandHandlerContract
{
    private const MAX_TOPIC = 2000;

    public function handle(SlashCommandContext $context, string $commandName, string $args): SlashCommandOutcome
    {
        if (! Gate::forUser($context->user)->allows('updateDetails', $context->room)) {
            return SlashCommandOutcome::httpError(
                403,
                'Недостатньо прав для зміни теми кімнати.',
            );
        }

        $trimmed = trim($args);
        $topicValue = $trimmed === '' ? null : $trimmed;

        if ($topicValue !== null && mb_strlen($topicValue) > self::MAX_TOPIC) {
            return SlashCommandOutcome::httpError(
                422,
                'Тема кімнати не може бути довшою за '.self::MAX_TOPIC.' символів.',
            );
        }

        $display = $context->displayUserName;
        $formatted = $topicValue === null
            ? '*'.$display.' скинув тему кімнати.*'
            : '*'.$display.' змінив тему кімнати: '.$topicValue.'*';

        return SlashCommandOutcome::publicRoomMessage($formatted, [
            'name' => 'topic',
            'recognized' => true,
            'topic_apply' => true,
            'topic_value' => $topicValue,
        ]);
    }
}
