<?php

namespace App\Http\Controllers\Api\V1;

use App\Chat\SlashCommandPipeline;
use App\Events\MessagePosted;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\StoreChatMessageRequest;
use App\Http\Resources\ChatMessageResource;
use App\Models\ChatMessage;
use App\Models\Room;
use App\Services\Moderation\ContentWordFilter;
use App\Services\Moderation\UserPostingGate;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class ChatMessageController extends Controller
{
    public function __construct(
        private readonly SlashCommandPipeline $slashPipeline,
        private readonly ContentWordFilter $wordFilter,
        private readonly UserPostingGate $postingGate,
    ) {}

    public function index(Request $request, Room $room): AnonymousResourceCollection|JsonResponse
    {
        $this->authorize('interact', $room);

        $validated = $request->validate([
            'before' => ['sometimes', 'integer', 'min:1'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $limit = (int) ($validated['limit'] ?? 50);
        $before = isset($validated['before']) ? (int) $validated['before'] : null;

        $query = ChatMessage::query()
            ->where('post_roomid', $room->room_id)
            ->orderByDesc('post_id');

        if ($before !== null) {
            $query->where('post_id', '<', $before);
        }

        $page = $query->limit($limit)->get()->sortBy('post_id')->values();

        $nextCursor = $page->isNotEmpty() ? $page->first()->post_id : null;

        return ChatMessageResource::collection($page)->additional([
            'meta' => [
                'next_cursor' => $nextCursor,
            ],
        ]);
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

        $raw = (string) ($request->validated('message') ?? '');
        $fileRef = $request->filled('image_id') ? (int) $request->input('image_id') : 0;
        $pipe = $this->slashPipeline->transform($raw, $user->user_name);
        $pipe['message'] = $this->wordFilter->filter($pipe['message']);
        $now = time();

        $avatarUrl = $user->resolveAvatarUrl();

        try {
            $message = ChatMessage::query()->create([
                'user_id' => $user->id,
                'post_date' => $now,
                'post_time' => date('H:i', $now),
                'post_user' => $user->user_name,
                'post_message' => $pipe['message'],
                'post_color' => $user->resolveChatRole()->postColorClass(),
                'post_roomid' => $room->room_id,
                'type' => 'public',
                'post_target' => null,
                'avatar' => $avatarUrl,
                'file' => $fileRef,
                'client_message_id' => $clientId,
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
                    ], 422);
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
                    'slash' => $pipe['slash'],
                ],
            ])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    private function duplicateMessageResponse(ChatMessage $message): JsonResponse
    {
        return ChatMessageResource::make($message)
            ->additional([
                'meta' => [
                    'duplicate' => true,
                    'slash' => ['name' => null, 'recognized' => false],
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
