<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\RoomResource;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
}
