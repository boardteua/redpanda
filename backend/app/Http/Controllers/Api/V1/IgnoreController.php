<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserIgnore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IgnoreController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $uid = $request->user()->id;

        $rows = UserIgnore::query()
            ->where('user_id', $uid)
            ->with('ignoredUser:id,user_name')
            ->orderByDesc('created_at')
            ->get();

        $data = $rows->map(fn (UserIgnore $row) => [
            'user' => [
                'id' => $row->ignoredUser->id,
                'user_name' => $row->ignoredUser->user_name,
            ],
        ])->all();

        return response()->json(['data' => $data]);
    }

    public function store(Request $request, User $user): JsonResponse
    {
        $self = $request->user();
        if ((int) $user->id === (int) $self->id) {
            return response()->json(['message' => 'Неможливо ігнорувати себе.'], 422);
        }

        UserIgnore::query()->firstOrCreate([
            'user_id' => $self->id,
            'ignored_user_id' => $user->id,
        ]);

        return response()->json(['message' => 'Користувача додано до ігнору.'], 201);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $self = $request->user();

        $deleted = UserIgnore::query()
            ->where('user_id', $self->id)
            ->where('ignored_user_id', $user->id)
            ->delete();

        if ($deleted === 0) {
            return response()->json(['message' => 'Запису в ігнорі немає.'], 404);
        }

        return response()->json(['message' => 'Користувача прибрано з ігнору.']);
    }
}
