<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FriendController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $uid = $request->user()->id;

        $rows = Friendship::query()
            ->where('status', Friendship::STATUS_ACCEPTED)
            ->where(function ($q) use ($uid) {
                $q->where('requester_id', $uid)->orWhere('addressee_id', $uid);
            })
            ->with(['requester:id,user_name', 'addressee:id,user_name'])
            ->orderByDesc('updated_at')
            ->get();

        $data = $rows->map(function (Friendship $f) use ($uid) {
            $peer = (int) $f->requester_id === (int) $uid ? $f->addressee : $f->requester;

            return [
                'user' => ['id' => $peer->id, 'user_name' => $peer->user_name],
                'since' => $f->updated_at?->toIso8601String(),
            ];
        })->all();

        return response()->json(['data' => $data]);
    }

    public function incoming(Request $request): JsonResponse
    {
        $uid = $request->user()->id;

        $rows = Friendship::query()
            ->where('addressee_id', $uid)
            ->where('status', Friendship::STATUS_PENDING)
            ->with('requester:id,user_name')
            ->orderByDesc('created_at')
            ->get();

        $data = $rows->map(fn (Friendship $f) => [
            'user' => ['id' => $f->requester->id, 'user_name' => $f->requester->user_name],
            'created_at' => $f->created_at?->toIso8601String(),
        ])->all();

        return response()->json(['data' => $data]);
    }

    public function outgoing(Request $request): JsonResponse
    {
        $uid = $request->user()->id;

        $rows = Friendship::query()
            ->where('requester_id', $uid)
            ->where('status', Friendship::STATUS_PENDING)
            ->with('addressee:id,user_name')
            ->orderByDesc('created_at')
            ->get();

        $data = $rows->map(fn (Friendship $f) => [
            'user' => ['id' => $f->addressee->id, 'user_name' => $f->addressee->user_name],
            'created_at' => $f->created_at?->toIso8601String(),
        ])->all();

        return response()->json(['data' => $data]);
    }

    public function store(Request $request, User $user): JsonResponse
    {
        $self = $request->user();
        if ((int) $user->id === (int) $self->id) {
            return response()->json(['message' => 'Неможливо додати себе.'], 422);
        }

        $forward = Friendship::query()
            ->where('requester_id', $self->id)
            ->where('addressee_id', $user->id)
            ->first();

        if ($forward !== null) {
            if ($forward->status === Friendship::STATUS_PENDING) {
                return response()->json(['message' => 'Запит уже надіслано.'], 422);
            }
            if ($forward->status === Friendship::STATUS_ACCEPTED) {
                return response()->json(['message' => 'Вже у списку друзів.'], 422);
            }
            $forward->update(['status' => Friendship::STATUS_PENDING]);

            return response()->json(['message' => 'Запит надіслано повторно.'], 201);
        }

        $reverse = Friendship::query()
            ->where('requester_id', $user->id)
            ->where('addressee_id', $self->id)
            ->first();

        if ($reverse !== null) {
            if ($reverse->status === Friendship::STATUS_PENDING) {
                return response()->json([
                    'message' => 'Цей користувач уже надіслав вам запит — прийміть його у вкладці «Запити».',
                ], 409);
            }
            if ($reverse->status === Friendship::STATUS_ACCEPTED) {
                return response()->json(['message' => 'Вже у списку друзів.'], 422);
            }
        }

        Friendship::query()->create([
            'requester_id' => $self->id,
            'addressee_id' => $user->id,
            'status' => Friendship::STATUS_PENDING,
        ]);

        return response()->json(['message' => 'Запит надіслано.'], 201);
    }

    public function accept(Request $request, User $user): JsonResponse
    {
        $self = $request->user();

        $row = Friendship::query()
            ->where('requester_id', $user->id)
            ->where('addressee_id', $self->id)
            ->where('status', Friendship::STATUS_PENDING)
            ->first();

        if ($row === null) {
            return response()->json(['message' => 'Немає вхідного запиту від цього користувача.'], 404);
        }

        $row->update(['status' => Friendship::STATUS_ACCEPTED]);

        return response()->json(['message' => 'Запит прийнято.']);
    }

    public function reject(Request $request, User $user): JsonResponse
    {
        $self = $request->user();

        $row = Friendship::query()
            ->where('requester_id', $user->id)
            ->where('addressee_id', $self->id)
            ->where('status', Friendship::STATUS_PENDING)
            ->first();

        if ($row === null) {
            return response()->json(['message' => 'Немає вхідного запиту від цього користувача.'], 404);
        }

        $row->update(['status' => Friendship::STATUS_REJECTED]);

        return response()->json(['message' => 'Запит відхилено.']);
    }
}
