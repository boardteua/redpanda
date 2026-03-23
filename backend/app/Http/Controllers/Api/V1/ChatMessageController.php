<?php

namespace App\Http\Controllers\Api\V1;

use App\Chat\RoomInlinePrivateParser;
use App\Chat\SlashCommandPipeline;
use App\Events\MessageDeleted;
use App\Events\MessagePosted;
use App\Events\MessageUpdated;
use App\Events\PrivateMessageCreated;
use App\Events\RoomInlinePrivatePosted;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\StoreChatMessageRequest;
use App\Http\Requests\Chat\UpdateChatMessageRequest;
use App\Http\Resources\ChatMessageResource;
use App\Models\ChatMessage;
use App\Models\PrivateMessage;
use App\Models\Room;
use App\Models\RoomReadState;
use App\Models\User;
use App\Services\Moderation\ChatAutomoderationService;
use App\Services\Moderation\ContentWordFilter;
use App\Services\Moderation\UserPostingGate;
use App\Services\PrivateMessageGate;
use App\Support\ChatMessageBodyStyle;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ChatMessageController extends Controller
{
    public function __construct(
        private readonly SlashCommandPipeline $slashPipeline,
        private readonly ContentWordFilter $wordFilter,
        private readonly UserPostingGate $postingGate,
        private readonly ChatAutomoderationService $automod,
    ) {}

    public function index(Request $request, Room $room): AnonymousResourceCollection|JsonResponse
    {
        $this->authorize('interact', $room);

        $validated = $request->validate([
            'before' => ['sometimes', 'integer', 'min:1'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'since_read' => ['sometimes', 'boolean'],
        ]);

        $limit = (int) ($validated['limit'] ?? 50);
        $before = isset($validated['before']) ? (int) $validated['before'] : null;

        $uid = (int) $request->user()->id;

        $lastReadPostId = RoomReadState::query()
            ->where('user_id', $uid)
            ->where('room_id', $room->room_id)
            ->value('last_read_post_id');

        $query = ChatMessage::query()
            ->visibleInRoomForUser($room, $uid)
            ->orderByDesc('post_id');

        if ($before !== null) {
            $query->where('post_id', '<', $before);
        }

        $page = $query->limit($limit)->get()->sortBy('post_id')->values();

        $nextCursor = $page->isNotEmpty() ? $page->first()->post_id : null;

        $meta = [
            'next_cursor' => $nextCursor,
            'last_read_post_id' => $lastReadPostId !== null ? (int) $lastReadPostId : null,
        ];

        if ($request->boolean('since_read')) {
            $floor = (int) ($lastReadPostId ?? 0);
            $firstUnread = $page->first(static fn (ChatMessage $m) => (int) $m->post_id > $floor);

            $meta['first_unread_post_id'] = $firstUnread !== null ? (int) $firstUnread->post_id : null;
        }

        return ChatMessageResource::collection($page)->additional([
            'meta' => $meta,
        ]);
    }

    public function update(UpdateChatMessageRequest $request, Room $room, ChatMessage $message): JsonResponse
    {
        $this->authorize('interact', $room);

        if ((int) $message->post_roomid !== (int) $room->room_id) {
            abort(404);
        }

        $this->authorize('update', $message);

        $user = $request->user();
        $this->postingGate->ensureCanPost($user);

        $validated = $request->validated();
        $rawMsg = trim((string) ($validated['message'] ?? ''));
        if ($message->type === 'public') {
            $mod = $this->automod->applyToPublicMessage($rawMsg, $user);
            if (! $mod['ok']) {
                return response()->json(['message' => $mod['message']], 422);
            }
            $filtered = $mod['text'];
            $message->moderation_flag_at = $mod['flag'] ? time() : null;
        } else {
            $filtered = $this->wordFilter->filter($rawMsg);
        }

        if ($request->has('style')) {
            $sp = $validated['style'] ?? null;
            $message->post_style = ChatMessageBodyStyle::fromValidated(is_array($sp) ? $sp : null);
        }

        $message->post_message = $filtered;
        $message->post_edited_at = time();
        $message->save();

        broadcast(new MessageUpdated($message))->toOthers();

        return ChatMessageResource::make($message->fresh())->response();
    }

    public function destroy(Request $request, Room $room, ChatMessage $message): JsonResponse
    {
        $this->authorize('interact', $room);

        if ((int) $message->post_roomid !== (int) $room->room_id) {
            abort(404);
        }

        $this->authorize('delete', $message);

        $user = $request->user();
        $this->postingGate->ensureCanPost($user);

        $now = time();
        if ($message->post_deleted_at === null) {
            $message->post_deleted_at = $now;
            $message->post_message = '';
            $message->file = 0;
            $message->post_style = null;
            $message->save();
            broadcast(new MessageDeleted($message->fresh()))->toOthers();
        }

        return ChatMessageResource::make($message->fresh())->response();
    }

    public function store(StoreChatMessageRequest $request, Room $room): JsonResponse
    {
        $this->authorize('interact', $room);

        $user = $request->user();
        $this->postingGate->ensureCanPost($user);
        $clientId = $request->validated('client_message_id');

        $existing = ChatMessage::query()
            ->where('user_id', $user->id)
            ->where('client_message_id', $clientId)
            ->first();

        if ($existing !== null) {
            if ((int) $existing->post_roomid !== (int) $room->room_id) {
                return response()->json([
                    'message' => 'client_message_id already used for another room.',
                ], 422);
            }

            return $this->duplicateMessageResponse($existing);
        }

        $cachedSlash = Cache::get($this->slashClientOnlyCacheKey((int) $user->id, $clientId));
        if (is_array($cachedSlash)) {
            return response()->json($cachedSlash, Response::HTTP_OK);
        }

        $validated = $request->validated();
        $stylePayload = isset($validated['style']) && is_array($validated['style']) ? $validated['style'] : null;
        $postStyle = ChatMessageBodyStyle::fromValidated($stylePayload);

        $raw = (string) ($request->validated('message') ?? '');
        $fileRef = $request->filled('image_id') ? (int) $request->input('image_id') : 0;
        $inline = RoomInlinePrivateParser::tryParse($raw);

        if ($inline !== null) {
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
            $now = time();
            $avatarUrl = $user->resolveAvatarUrl();

            try {
                $message = null;
                $privateRow = null;

                DB::transaction(function () use ($user, $peer, $room, $body, $now, $avatarUrl, $clientId, $postStyle, &$message, &$privateRow): void {
                    $message = ChatMessage::query()->create([
                        'user_id' => $user->id,
                        'post_date' => $now,
                        'post_time' => date('H:i', $now),
                        'post_user' => $user->user_name,
                        'post_message' => $body,
                        'post_style' => $postStyle,
                        'post_color' => $user->resolveChatRole()->postColorClass(),
                        'post_roomid' => $room->room_id,
                        'type' => 'inline_private',
                        'post_target' => (string) $peer->id,
                        'avatar' => $avatarUrl,
                        'file' => 0,
                        'client_message_id' => $clientId,
                    ]);

                    $privateRow = PrivateMessage::query()->create([
                        'sender_id' => $user->id,
                        'recipient_id' => $peer->id,
                        'body' => $body,
                        'sent_at' => $now,
                        'sent_time' => date('H:i', $now),
                        'client_message_id' => $clientId,
                    ]);
                });
            } catch (QueryException $e) {
                if ($this->isDuplicateKey($e)) {
                    $retry = ChatMessage::query()
                        ->where('user_id', $user->id)
                        ->where('client_message_id', $clientId)
                        ->first();

                    if ($retry !== null) {
                        if ((int) $retry->post_roomid !== (int) $room->room_id) {
                            return response()->json([
                                'message' => 'client_message_id already used for another room.',
                            ], 422);
                        }

                        return $this->duplicateMessageResponse($retry);
                    }

                    if (PrivateMessage::query()
                        ->where('sender_id', $user->id)
                        ->where('client_message_id', $clientId)
                        ->exists()) {
                        return response()->json([
                            'message' => 'client_message_id already used for a private message.',
                        ], 422);
                    }

                    throw $e;
                }

                throw $e;
            }

            broadcast(new PrivateMessageCreated($privateRow));
            broadcast(new RoomInlinePrivatePosted($message));

            return ChatMessageResource::make($message)
                ->additional([
                    'meta' => [
                        'duplicate' => false,
                        'slash' => [
                            'name' => 'msg',
                            'recognized' => true,
                            'result' => 'public_message',
                        ],
                    ],
                ])
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
        }

        $trim = ltrim($raw);

        if (str_starts_with($trim, '//')) {
            $escapedBody = substr($trim, 1);

            return $this->persistRoomPublicMessage(
                $room,
                $user,
                $escapedBody,
                $postStyle,
                $fileRef,
                $clientId,
                [
                    'name' => null,
                    'recognized' => false,
                    'result' => 'public_message',
                    'escaped' => true,
                ],
            );
        }

        if ($trim !== '' && $trim[0] === '/') {
            $throttleMessage = $this->slashPipeline->ensureSlashThrottle($user);
            if ($throttleMessage !== null) {
                return response()->json(['message' => $throttleMessage], Response::HTTP_TOO_MANY_REQUESTS);
            }

            $dispatch = $this->slashPipeline->dispatchParsed($user, $room, $trim);
            $handlerResult = $dispatch['result'];
            $invocation = $dispatch['invocation'];

            if ($handlerResult->kind === 'client_only') {
                $slashMeta = array_merge(
                    [
                        'name' => $invocation->parsed->command !== '' ? $invocation->parsed->command : null,
                        'recognized' => false,
                        'result' => 'client_only',
                    ],
                    $handlerResult->slashMetaExtension,
                );

                $payload = [
                    'data' => null,
                    'meta' => [
                        'duplicate' => false,
                        'slash' => $slashMeta,
                        'client_only' => [
                            'lines' => array_values($handlerResult->clientOnlyLines),
                            'style' => 'terminal',
                        ],
                    ],
                ];

                Cache::put($this->slashClientOnlyCacheKey((int) $user->id, $clientId), $payload, 86400);

                return response()->json($payload, Response::HTTP_OK);
            }

            if ($handlerResult->kind === 'http_error') {
                return response()->json(
                    ['message' => $handlerResult->httpMessage ?? 'Помилка'],
                    $handlerResult->httpStatus ?? Response::HTTP_UNPROCESSABLE_ENTITY,
                );
            }

            $roomText = $handlerResult->roomMessage ?? '';
            $slashMeta = array_merge(
                [
                    'name' => $invocation->parsed->command !== '' ? $invocation->parsed->command : null,
                    'recognized' => true,
                    'result' => 'public_message',
                ],
                $handlerResult->slashMetaExtension,
            );

            return $this->persistRoomPublicMessage(
                $room,
                $user,
                $roomText,
                $postStyle,
                $fileRef,
                $clientId,
                $slashMeta,
            );
        }

        return $this->persistRoomPublicMessage(
            $room,
            $user,
            $raw,
            $postStyle,
            $fileRef,
            $clientId,
            [
                'name' => null,
                'recognized' => false,
                'result' => 'public_message',
            ],
        );
    }

    /**
     * @param  array<string, mixed>|null  $postStyle
     * @param  array<string, mixed>  $slashMeta
     */
    private function persistRoomPublicMessage(
        Room $room,
        User $user,
        string $messageText,
        ?array $postStyle,
        int $fileRef,
        string $clientId,
        array $slashMeta,
    ): JsonResponse {
        $mod = $this->automod->applyToPublicMessage($messageText, $user);
        if (! $mod['ok']) {
            return response()->json(['message' => $mod['message']], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $filtered = $mod['text'];
        $now = time();
        $avatarUrl = $user->resolveAvatarUrl();

        try {
            $message = ChatMessage::query()->create([
                'user_id' => $user->id,
                'post_date' => $now,
                'post_time' => date('H:i', $now),
                'post_user' => $user->user_name,
                'post_message' => $filtered,
                'post_style' => $postStyle,
                'post_color' => $user->resolveChatRole()->postColorClass(),
                'post_roomid' => $room->room_id,
                'type' => 'public',
                'post_target' => null,
                'avatar' => $avatarUrl,
                'file' => $fileRef,
                'client_message_id' => $clientId,
                'moderation_flag_at' => $mod['flag'] ? $now : null,
            ]);
        } catch (QueryException $e) {
            if ($this->isDuplicateKey($e)) {
                $retry = ChatMessage::query()
                    ->where('user_id', $user->id)
                    ->where('client_message_id', $clientId)
                    ->firstOrFail();

                if ((int) $retry->post_roomid !== (int) $room->room_id) {
                    return response()->json([
                        'message' => 'client_message_id already used for another room.',
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                return $this->duplicateMessageResponse($retry);
            }

            throw $e;
        }

        broadcast(new MessagePosted($message))->toOthers();

        return ChatMessageResource::make($message)
            ->additional([
                'meta' => [
                    'duplicate' => false,
                    'slash' => $slashMeta,
                ],
            ])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    private function slashClientOnlyCacheKey(int $userId, string $clientId): string
    {
        return 'slash_client_only:v1:'.$userId.':'.$clientId;
    }

    private function duplicateMessageResponse(ChatMessage $message): JsonResponse
    {
        return ChatMessageResource::make($message)
            ->additional([
                'meta' => [
                    'duplicate' => true,
                    'slash' => [
                        'name' => null,
                        'recognized' => false,
                        'result' => 'public_message',
                    ],
                ],
            ])
            ->response()
            ->setStatusCode(200);
    }

    private function isDuplicateKey(QueryException $e): bool
    {
        $sqlState = (string) ($e->errorInfo[0] ?? '');
        if ($sqlState === '23505') {
            return true;
        }

        $code = $e->errorInfo[1] ?? null;

        return in_array($code, [1062, 19, '1062', '19'], true);
    }
}
