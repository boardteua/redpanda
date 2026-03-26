<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Публічна картка користувача в контексті кімнати (T148): профіль і соцмережі з редокцією {@see UserResource}.
 */
class RoomUserProfileController extends Controller
{
    public function show(Request $request, Room $room, User $user): UserResource
    {
        $viewer = $request->user();
        abort_if($viewer === null || $viewer->guest, 403, 'Гості не можуть переглядати профіль інших користувачів.');

        $this->authorize('interact', $room);

        return UserResource::make($user);
    }
}
