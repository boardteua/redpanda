<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserLookupController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
        ]);

        $user = User::query()->where('user_name', $validated['name'])->first();
        if ($user === null) {
            return response()->json(['message' => 'Користувача не знайдено.'], 404);
        }

        $self = $request->user();
        if ((int) $user->id === (int) $self->id) {
            return response()->json(['message' => 'Неможливо вибрати себе.'], 422);
        }

        $role = $user->resolveChatRole();

        return response()->json([
            'data' => [
                'id' => $user->id,
                'user_name' => $user->user_name,
                'guest' => (bool) $user->guest,
                'chat_role' => $role->value,
                'badge_color' => $role->badgeColor(),
            ],
        ]);
    }
}
