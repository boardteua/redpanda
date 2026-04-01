<?php

namespace App\Services\Chat;

use App\Chat\RoomInlinePrivateParser;
use App\Chat\SlashCommands\SlashCommandContext;
use App\Chat\SlashCommands\SlashCommandOutcome;
use App\Chat\SlashCommands\SlashCommandParser;
use App\Chat\SlashCommands\SlashCommandProcessor;
use App\Events\MessagePosted;
use App\Events\PrivateMessageCreated;
use App\Events\RoomInlinePrivatePosted;
use App\Events\RoomTopicUpdated;
use App\Http\Requests\Chat\StoreChatMessageRequest;
use App\Http\Resources\ChatMessageResource;
use App\Jobs\SendWebPushForPrivateMessage;
use App\Jobs\SendWebPushForRoomMessage;
use App\Models\Room;
use App\Models\User;
use App\Services\Ai\RudaPanda\RudaPandaRoomResponder;
use App\Services\Moderation\ChatAutomoderationService;
use App\Services\Moderation\ContentWordFilter;
use App\Services\PrivateMessageGate;
use App\Support\ChatMessageBodyStyle;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class RoomMessagePostOrchestrator
{
    public function __construct(
        private readonly SlashCommandProcessor $slashProcessor,
        private readonly ContentWordFilter $wordFilter,
        private readonly ChatAutomoderationService $automod,
        private readonly RudaPandaRoomResponder $rudaPandaRoomResponder,
        private readonly RoomChatMessageCreator $roomChatMessageCreator,
        private readonly RoomClientMessageIdempotency $roomClientMessageIdempotency,
    ) {}

    public function handle(StoreChatMessageRequest $request, Room $room): JsonResponse
    {
        $user = $request->user();
        $clientId = (string) $request->validated('client_message_id');

        $duplicateResponse = $this->roomClientMessageIdempotency->findDuplicateResponse($user, $room, $clientId);
        if ($duplicateResponse !== null) {
            return $duplicateResponse;
        }

        $validated = $request->validated();
        $stylePayload = isset($validated['style']) && is_array($validated['style']) ? $validated['style'] : null;
        $postStyle = ChatMessageBodyStyle::fromValidated($stylePayload);
        $raw = (string) ($validated['message'] ?? '');
        $fileRef = $request->filled('image_id') ? (int) $request->input('image_id') : 0;

        $inline = RoomInlinePrivateParser::tryParse($raw);
        if ($inline !== null) {
            return $this->handleInlinePrivate($user, $room, $inline, $postStyle, $clientId, $fileRef);
        }

        if ($fileRef !== 0 && SlashCommandParser::looksLikeSlashCommand($raw)) {
            return response()->json([
                'message' => 'Зображення не підтримуються разом із командами, що починаються з /.',
            ], 422);
        }

        $slashOutcome = $this->slashProcessor->process(
            $raw,
            new SlashCommandContext($user, $room, (string) $user->user_name, $clientId),
        );

        if ($slashOutcome->mode === SlashCommandOutcome::MODE_HTTP_ERROR) {
            return response()->json([
                'message' => (string) $slashOutcome->httpMessage,
            ], (int) $slashOutcome->httpStatus);
        }

        if ($slashOutcome->mode === SlashCommandOutcome::MODE_CLIENT_ONLY) {
            return $this->handleClientOnly($user, $room, $slashOutcome, $clientId);
        }

        return $this->handlePublicMessage($user, $room, $slashOutcome, $postStyle, $clientId, $fileRef);
    }

    /**
     * @param  array{nick:string, body:string}  $inline
     * @param  array<string, mixed>|null  $postStyle
     */
    private function handleInlinePrivate(
        User $user,
        Room $room,
        array $inline,
        ?array $postStyle,
        string $clientId,
        int $fileRef,
    ): JsonResponse {
        if ($fileRef !== 0) {
            return response()->json([
                'message' => 'Зображення не підтримуються для інлайн-привату /msg.',
            ], 422);
        }

        $peer = User::query()
            ->whereRaw('LOWER(user_name) = LOWER(?)', [$inline['nick']])
            ->first();

        if ($peer === null) {
            return response()->json([
                'message' => 'Користувача з таким ніком не знайдено.',
            ], 422);
        }

        if ((int) $peer->id === (int) $user->id) {
            return response()->json(['message' => 'Неможливо написати собі.'], 422);
        }

        if (PrivateMessageGate::isBlocked($user, $peer)) {
            return response()->json(['message' => 'Надсилання заблоковано (ігнор).'], 403);
        }

        $body = $this->wordFilter->filter($inline['body']);

        try {
            $created = $this->roomChatMessageCreator->createInlinePrivate(
                user: $user,
                peer: $peer,
                room: $room,
                message: $body,
                clientId: $clientId,
                postStyle: $postStyle,
            );
        } catch (QueryException $e) {
            return $this->roomClientMessageIdempotency->resolveAfterDuplicateKey(
                exception: $e,
                user: $user,
                room: $room,
                clientId: $clientId,
                checkPrivateMessageConflict: true,
            );
        }

        $message = $created['message'];
        $privateRow = $created['private'];

        broadcast(new PrivateMessageCreated($privateRow));
        broadcast(new RoomInlinePrivatePosted($message));
        SendWebPushForPrivateMessage::dispatch((int) $privateRow->id)->afterCommit();

        return ChatMessageResource::make($message)
            ->additional([
                'meta' => [
                    'duplicate' => false,
                    'slash' => ['name' => 'msg', 'recognized' => true],
                ],
            ])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    private function handleClientOnly(
        User $user,
        Room $room,
        SlashCommandOutcome $slashOutcome,
        string $clientId,
    ): JsonResponse {
        try {
            $message = $this->roomChatMessageCreator->createClientOnly(
                user: $user,
                room: $room,
                message: $slashOutcome->text,
                clientId: $clientId,
            );
        } catch (QueryException $e) {
            return $this->roomClientMessageIdempotency->resolveAfterDuplicateKey(
                exception: $e,
                user: $user,
                room: $room,
                clientId: $clientId,
            );
        }

        return ChatMessageResource::make($message)
            ->additional([
                'meta' => [
                    'duplicate' => false,
                    'slash' => $slashOutcome->slashMeta,
                ],
            ])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * @param  array<string, mixed>|null  $postStyle
     */
    private function handlePublicMessage(
        User $user,
        Room $room,
        SlashCommandOutcome $slashOutcome,
        ?array $postStyle,
        string $clientId,
        int $fileRef,
    ): JsonResponse {
        $effective = $slashOutcome->text;

        if ($slashOutcome->persistedPublicMessage !== null) {
            $message = $slashOutcome->persistedPublicMessage;
        } else {
            $mod = $this->automod->applyToPublicMessage($effective, $user);
            if (! $mod['ok']) {
                return response()->json(['message' => $mod['message']], 422);
            }
            $effective = $mod['text'];

            if (($slashOutcome->slashMeta['topic_apply'] ?? false) === true) {
                $topicVal = $slashOutcome->slashMeta['topic_value'] ?? null;
                $room->topic = ($topicVal === null || $topicVal === '') ? null : (string) $topicVal;
                $room->save();
                broadcast(new RoomTopicUpdated((int) $room->room_id, $room->topic));
            }

            try {
                $message = $this->roomChatMessageCreator->createPublic(
                    user: $user,
                    room: $room,
                    message: $effective,
                    postStyle: $postStyle,
                    fileRef: $fileRef,
                    clientId: $clientId,
                    moderationFlagAt: $mod['flag'] ? time() : null,
                );
            } catch (QueryException $e) {
                return $this->roomClientMessageIdempotency->resolveAfterDuplicateKey(
                    exception: $e,
                    user: $user,
                    room: $room,
                    clientId: $clientId,
                );
            }

            broadcast(new MessagePosted($message))->toOthers();
            SendWebPushForRoomMessage::dispatch((int) $message->post_id)->afterCommit();
        }

        $rudaPandaDebug = null;
        if ($this->shouldExposeRudaPandaLlmDebug()) {
            $rudaPandaDebug = [];
        }
        $this->rudaPandaRoomResponder->maybeDispatchForMessage($message, $room, $rudaPandaDebug);

        $meta = [
            'duplicate' => false,
            'slash' => $slashOutcome->slashMeta,
        ];
        if (is_array($rudaPandaDebug)) {
            $meta['ruda_panda_llm_debug'] = $rudaPandaDebug;
        }

        return ChatMessageResource::make($message)
            ->additional([
                'meta' => $meta,
            ])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    private function shouldExposeRudaPandaLlmDebug(): bool
    {
        if (! config('chat.ruda_panda_llm_debug_console')) {
            return false;
        }

        return app()->environment('local') || (bool) config('app.debug', false);
    }
}
