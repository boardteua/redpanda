<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\StoreChatMessageRequest;
use App\Http\Requests\Chat\UpdateChatMessageRequest;
use App\Http\Resources\ChatMessageResource;
use App\Models\ChatMessage;
use App\Models\Room;
use App\Services\Chat\ChatMessageMutationService;
use App\Services\Chat\MessageFloodGate;
use App\Services\Chat\RoomMessageHistoryQuery;
use App\Services\Chat\RoomMessagePostOrchestrator;
use App\Services\Moderation\ProxyCheck\ProxyCheckGate;
use App\Services\Moderation\UserPostingGate;
use App\Support\ChatMessageListAbilityMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ChatMessageController extends Controller
{
    public function __construct(
        private readonly UserPostingGate $postingGate,
        private readonly ChatMessageMutationService $messageMutation,
        private readonly MessageFloodGate $messageFloodGate,
        private readonly ProxyCheckGate $proxyCheckGate,
        private readonly RoomMessageHistoryQuery $roomMessageHistoryQuery,
        private readonly RoomMessagePostOrchestrator $roomMessagePostOrchestrator,
    ) {}

    public function index(Request $request, Room $room): AnonymousResourceCollection|JsonResponse
    {
        $this->authorize('interact', $room);

        $result = $this->roomMessageHistoryQuery->execute($request, $room);
        $page = $result->messages;

        $request->attributes->set(
            ChatMessageResource::ABILITY_MAP_REQUEST_KEY,
            ChatMessageListAbilityMap::forMessages($request->user(), $page),
        );

        return ChatMessageResource::collection($page)->additional([
            'meta' => $result->meta,
        ]);
    }

    public function update(UpdateChatMessageRequest $request, Room $room, ChatMessage $message): JsonResponse
    {
        $this->authorize('interact', $room);

        if ((int) $message->post_roomid !== (int) $room->room_id) {
            abort(404);
        }

        $this->authorize('update', $message);

        return $this->messageMutation->update($request->user(), $message, $request);
    }

    public function destroy(Request $request, Room $room, ChatMessage $message): JsonResponse
    {
        $this->authorize('interact', $room);

        if ((int) $message->post_roomid !== (int) $room->room_id) {
            abort(404);
        }

        $this->authorize('delete', $message);

        return $this->messageMutation->softDelete($request->user(), $message);
    }

    public function store(StoreChatMessageRequest $request, Room $room): JsonResponse
    {
        $this->authorize('interact', $room);

        if ($resp = $this->proxyCheckGate->denyIfNeeded($request, 'chat_post_room')) {
            return $resp;
        }

        $user = $request->user();
        $this->postingGate->ensureCanPost($user);

        if ($resp = $this->messageFloodGate->ensureWithinLimit($user)) {
            return $resp;
        }

        return $this->roomMessagePostOrchestrator->handle($request, $room);
    }
}
