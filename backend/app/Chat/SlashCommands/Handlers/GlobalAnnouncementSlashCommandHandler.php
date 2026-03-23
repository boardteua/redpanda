<?php

namespace App\Chat\SlashCommands\Handlers;

use App\Chat\SlashCommands\Contracts\SlashCommandHandlerContract;
use App\Chat\SlashCommands\SlashCommandContext;
use App\Chat\SlashCommands\SlashCommandOutcome;
use App\Chat\SlashCommands\Support\AdminSlashCommandHelper;
use App\Events\MessagePosted;
use App\Models\ChatMessage;
use App\Models\Room;
use App\Services\Moderation\ChatAutomoderationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

final class GlobalAnnouncementSlashCommandHandler implements SlashCommandHandlerContract
{
    public function __construct(
        private readonly ChatAutomoderationService $automod,
    ) {}

    public function handle(SlashCommandContext $context, string $commandName, string $args): SlashCommandOutcome
    {
        $deny = AdminSlashCommandHelper::requireChatAdmin($context->user);
        if ($deny !== null) {
            return $deny;
        }

        $text = trim($args);
        if ($text === '') {
            return SlashCommandOutcome::httpError(422, 'Вкажіть текст: /global повідомлення');
        }

        $uid = (int) $context->user->id;
        $allowed = RateLimiter::attempt(
            'slash-global:'.$uid,
            5,
            static fn (): bool => true,
            3600,
        );
        if (! $allowed) {
            $retry = RateLimiter::availableIn('slash-global:'.$uid);

            return SlashCommandOutcome::httpError(
                429,
                'Забагато глобальних повідомлень за годину. Спробуйте через '.$retry.' с.',
            );
        }

        $user = $context->user;
        $mod = $this->automod->applyToPublicMessage($text, $user);
        if (! $mod['ok']) {
            return SlashCommandOutcome::httpError(422, (string) $mod['message']);
        }

        $effective = (string) $mod['text'];
        $now = time();
        $avatarUrl = $user->resolveAvatarUrl();
        $postColor = $user->resolveChatRole()->postColorClass();
        $flagAt = $mod['flag'] ? $now : null;

        $roomIds = Room::query()->orderBy('room_id')->pluck('room_id')->all();
        if ($roomIds === []) {
            return SlashCommandOutcome::clientOnlyMessage('Немає кімнат для глобального повідомлення.', [
                'name' => 'global',
                'recognized' => true,
            ]);
        }

        $currentRoomId = (int) $context->room->room_id;
        $currentMessage = null;

        $messages = [];

        $requestClientId = $context->clientMessageId;

        DB::transaction(function () use (
            $user,
            $effective,
            $now,
            $avatarUrl,
            $postColor,
            $flagAt,
            $roomIds,
            $currentRoomId,
            $requestClientId,
            &$messages,
        ): void {
            foreach ($roomIds as $rid) {
                $rid = (int) $rid;
                $cid = ($rid === $currentRoomId && $requestClientId !== null && $requestClientId !== '')
                    ? $requestClientId
                    : (string) Str::uuid();
                $messages[] = ChatMessage::query()->create([
                    'user_id' => $user->id,
                    'post_date' => $now,
                    'post_time' => date('H:i', $now),
                    'post_user' => $user->user_name,
                    'post_message' => $effective,
                    'post_style' => ['global' => true],
                    'post_color' => $postColor,
                    'post_roomid' => $rid,
                    'type' => 'public',
                    'post_target' => null,
                    'avatar' => $avatarUrl,
                    'file' => 0,
                    'client_message_id' => $cid,
                    'moderation_flag_at' => $flagAt,
                ]);
            }
        });

        foreach ($messages as $msg) {
            broadcast(new MessagePosted($msg))->toOthers();
            if ((int) $msg->post_roomid === $currentRoomId) {
                $currentMessage = $msg;
            }
        }

        Log::info('chat.slash_command.global', [
            'actor_id' => $uid,
            'rooms' => count($messages),
        ]);

        if ($currentMessage === null) {
            return SlashCommandOutcome::clientOnlyMessage(
                'Глобальне повідомлення надіслано в '.count($messages).' кімнат(и).',
                [
                    'name' => 'global',
                    'recognized' => true,
                ],
            );
        }

        return SlashCommandOutcome::publicRoomMessage($effective, [
            'name' => 'global',
            'recognized' => true,
        ], $currentMessage->fresh());
    }
}
