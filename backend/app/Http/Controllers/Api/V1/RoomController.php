<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Http\Resources\RoomResource;
use App\Models\Room;
use App\Services\Chat\RedPandaBotNewPublicRoomAnnouncer;
use App\Services\Chat\RoomSlugService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RoomController extends Controller
{
    public function __construct(
        private readonly RedPandaBotNewPublicRoomAnnouncer $newPublicRoomAnnouncer,
        private readonly RoomSlugService $roomSlugService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        if ($user === null) {
            abort(401);
        }
        $query = Room::query()
            ->withCount('messages')
            ->orderBy('room_id');

        if ($user->guest) {
            $query->where('access', Room::ACCESS_PUBLIC);
        } elseif (! $user->canAccessVipRooms()) {
            $query->where('access', '<', Room::ACCESS_VIP);
        }

        return RoomResource::collection($query->get());
    }

    public function store(StoreRoomRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! Gate::forUser($user)->allows('create', Room::class)) {
            return response()->json([
                'message' => 'Недостатньо публічних повідомлень для створення кімнати.',
                'code' => 'room_create_insufficient_messages',
            ], 403);
        }

        $validated = $request->validated();
        $payload = [
            'room_name' => $validated['room_name'],
            'topic' => $validated['topic'] ?? null,
            'access' => Room::ACCESS_PUBLIC,
            'created_by_user_id' => $user->id,
        ];

        if (array_key_exists('slug', $validated)) {
            $manualSlug = strtolower((string) $validated['slug']);
            $this->roomSlugService->assertAssignableSlug($manualSlug, null);
            $payload['slug'] = $manualSlug;
        }

        $room = DB::transaction(
            fn (): Room => Room::query()->create($payload),
        );

        $room->loadCount('messages');

        $this->newPublicRoomAnnouncer->announce($room);

        return RoomResource::make($room)
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateRoomRequest $request, Room $room): JsonResponse
    {
        $user = $request->user();

        if (! Gate::forUser($user)->allows('interact', $room)) {
            return response()->json(['message' => 'Немає доступу до цієї кімнати.'], 403);
        }

        $validated = $request->validated();

        if (array_key_exists('access', $validated)) {
            Gate::forUser($user)->authorize('updateAccess', $room);
        }

        if (array_key_exists('room_name', $validated) || array_key_exists('topic', $validated) || array_key_exists('slug', $validated)) {
            Gate::forUser($user)->authorize('updateDetails', $room);
        }

        if (array_key_exists('room_name', $validated) && $validated['room_name'] !== $room->room_name) {
            $this->roomSlugService->assignSlugAfterRename($room, $validated['room_name']);
            $room->room_name = $validated['room_name'];
        }

        if (array_key_exists('slug', $validated)) {
            $this->roomSlugService->assignManualSlug($room, $validated['slug']);
        }

        if (array_key_exists('topic', $validated)) {
            $topic = $validated['topic'];
            $room->topic = ($topic === null || $topic === '') ? null : $topic;
        }

        if (array_key_exists('access', $validated)) {
            $room->access = (int) $validated['access'];
        }

        if (array_key_exists('ai_bot_enabled', $validated)) {
            Gate::forUser($user)->authorize('updateChatAiBot', $room);
            $room->ai_bot_enabled = (bool) $validated['ai_bot_enabled'];
        }

        $room->save();
        $room->refresh();
        $room->loadCount('messages');

        return RoomResource::make($room)->response();
    }

    public function destroy(Request $request, Room $room): Response|JsonResponse
    {
        $user = $request->user();

        if ($user->guest) {
            return response()->json(['message' => 'Гості не можуть видаляти кімнати.'], 403);
        }

        if (! Gate::forUser($user)->allows('interact', $room)) {
            return response()->json(['message' => 'Немає доступу до цієї кімнати.'], 403);
        }

        Gate::forUser($user)->authorize('delete', $room);

        if ($room->messages()->exists()) {
            return response()->json([
                'message' => 'Неможливо видалити кімнату з повідомленнями в історії.',
                'code' => 'room_has_messages',
            ], 422);
        }

        $room->delete();

        return response()->noContent();
    }
}
