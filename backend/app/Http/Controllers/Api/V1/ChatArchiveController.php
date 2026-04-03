<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChatMessageResource;
use App\Models\ChatMessage;
use App\Models\Room;
use App\Services\Chat\IgnoredRoomMessageVisibility;
use App\Support\ChatMessageListAbilityMap;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;

class ChatArchiveController extends Controller
{
    /**
     * Архів публічних повідомлень: offset-пагінація та обмежений пошук (LIKE).
     * Область — кімнати, у яких користувач має `interact`; опційно один `room`.
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'in:10,25,50,100'],
            'q' => ['sometimes', 'string', 'max:200'],
            'room' => ['sometimes', 'integer', 'min:1'],
        ]);

        $user = $request->user();
        $perPage = (int) ($validated['per_page'] ?? 25);
        $page = (int) ($validated['page'] ?? 1);

        $accessibleIds = Room::query()
            ->orderBy('room_id')
            ->get()
            ->filter(fn (Room $room) => Gate::forUser($user)->allows('interact', $room))
            ->map->room_id
            ->values()
            ->all();

        if ($accessibleIds === []) {
            $emptyPaginator = new LengthAwarePaginator(collect(), 0, $perPage, $page, [
                'path' => $request->url(),
                'pageName' => 'page',
            ]);
            $emptyPaginator->appends($request->query());

            return ChatMessageResource::collection($emptyPaginator);
        }

        if (isset($validated['room'])) {
            $room = Room::query()->where('room_id', (int) $validated['room'])->first();
            if ($room === null) {
                return response()->json(['message' => 'Кімнату не знайдено.'], 404);
            }
            if (! Gate::forUser($user)->allows('interact', $room)) {
                return response()->json(['message' => 'Немає доступу до цієї кімнати.'], 403);
            }
            $accessibleIds = [$room->room_id];
        }

        $query = ChatMessage::query()
            ->where(function (Builder $outer) use ($accessibleIds, $user): void {
                $outer->where(function (Builder $q) use ($accessibleIds): void {
                    $q->whereIn('post_roomid', $accessibleIds)
                        ->whereIn('type', ['public', 'system'])
                        ->whereNull('post_deleted_at');
                });
                $outer->orWhere(function (Builder $q) use ($user): void {
                    $q->whereNotNull('archived_from_room_id')
                        ->whereIn('type', ['public', 'system']);
                    if ($user->guest) {
                        $q->where('archived_room_access', Room::ACCESS_PUBLIC);
                    } else {
                        $q->where(function (Builder $inner) use ($user): void {
                            $inner->where('archived_room_access', Room::ACCESS_PUBLIC)
                                ->orWhere('archived_room_access', Room::ACCESS_REGISTERED);
                            if ($user->canAccessVipRooms()) {
                                $inner->orWhere('archived_room_access', '>=', Room::ACCESS_VIP);
                            }
                        });
                    }
                });
            });
        IgnoredRoomMessageVisibility::scopeExcludeIgnoredAuthors($query, $user);
        $query->orderByDesc('post_id');

        $search = isset($validated['q']) ? trim($validated['q']) : '';
        if ($search !== '') {
            $term = '%'.$this->escapeLikeWildcards($search).'%';
            $query->where(function ($q) use ($term) {
                $q->where('post_message', 'like', $term)
                    ->orWhere('post_user', 'like', $term);
            });
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page)->appends($request->query());

        $request->attributes->set(
            ChatMessageResource::ABILITY_MAP_REQUEST_KEY,
            ChatMessageListAbilityMap::forMessages($user, $paginator->getCollection()),
        );

        return ChatMessageResource::collection($paginator);
    }

    private function escapeLikeWildcards(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
