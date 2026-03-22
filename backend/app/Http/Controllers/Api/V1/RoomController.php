<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Resources\RoomResource;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class RoomController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $query = Room::query()->orderBy('room_id');

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
        $room = Room::query()->create([
            'room_name' => $validated['room_name'],
            'topic' => $validated['topic'] ?? null,
            'access' => Room::ACCESS_PUBLIC,
        ]);

        return RoomResource::make($room->fresh())
            ->response()
            ->setStatusCode(201);
    }
}
