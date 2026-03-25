<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\PrivateMessageCreated;
use App\Events\PrivateThreadCleared;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\StorePrivateMessageRequest;
use App\Http\Resources\PrivateMessageResource;
use App\Models\PrivateMessage;
use App\Models\PrivateMessageReadState;
use App\Models\User;
use App\Services\Moderation\ContentWordFilter;
use App\Services\Moderation\UserPostingGate;
use App\Services\Chat\MessageFloodGate;
use App\Services\PrivateMessageGate;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class PrivateMessageController extends Controller
{
    public function __construct(
        private readonly ContentWordFilter $wordFilter,
        private readonly UserPostingGate $postingGate,
        private readonly MessageFloodGate $messageFloodGate,
    ) {}

    public function conversations(Request $request): JsonResponse
    {
        $uid = (int) $request->user()->id;

        $peerLatestIds = PrivateMessage::query()
            ->selectRaw('MAX(id) as max_id')
            ->where(function ($q) use ($uid) {
                $q->where('sender_id', $uid)->orWhere('recipient_id', $uid);
            })
            ->groupByRaw('CASE WHEN sender_id = ? THEN recipient_id ELSE sender_id END', [$uid])
            ->pluck('max_id');

        $sortedIds = $peerLatestIds->sortDesc()->values()->all();
        if ($sortedIds === []) {
            return response()->json([
                'data' => [],
                'meta' => ['total_private_unread' => 0],
            ]);
        }

        $lastMessages = PrivateMessage::query()
            ->with([
                'sender:id,user_name',
                'recipient:id,user_name',
            ])
            ->whereIn('id', $sortedIds)
            ->get()
            ->sortByDesc('id')
            ->values();

        $peerIds = $lastMessages->map(function (PrivateMessage $m) use ($uid) {
            $peer = (int) $m->sender_id === $uid ? $m->recipient : $m->sender;

            return (int) $peer->id;
        })->unique()->values()->all();

        $unreadByPeer = $this->unreadIncomingCountsByPeer($uid, $peerIds);

        $totalUnread = 0;
        $data = $lastMessages->map(function (PrivateMessage $m) use ($uid, $unreadByPeer, &$totalUnread) {
            $peer = (int) $m->sender_id === $uid ? $m->recipient : $m->sender;
            $peerId = (int) $peer->id;
            $unread = (int) ($unreadByPeer[$peerId] ?? 0);
            $totalUnread += $unread;

            return [
                'peer' => [
                    'id' => $peer->id,
                    'user_name' => $peer->user_name,
                ],
                'last_message' => PrivateMessageResource::make($m)->resolve(),
                'unread_count' => $unread,
            ];
        })->all();

        return response()->json([
            'data' => $data,
            'meta' => [
                'total_private_unread' => $totalUnread,
            ],
        ]);
    }

    public function index(Request $request, User $peer): AnonymousResourceCollection|JsonResponse
    {
        $user = $request->user();
        if ((int) $peer->id === (int) $user->id) {
            return response()->json(['message' => 'Неможливо відкрити чат із собою.'], 422);
        }
        if (PrivateMessageGate::isBlocked($user, $peer)) {
            return response()->json(['message' => 'Повідомлення недоступні.'], 403);
        }

        $validated = $request->validate([
            'before' => ['sometimes', 'integer', 'min:1'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $uid = (int) $user->id;
        $peerId = (int) $peer->id;
        $limit = (int) ($validated['limit'] ?? 50);
        $before = isset($validated['before']) ? (int) $validated['before'] : null;

        $query = PrivateMessage::query()
            ->where(function ($q) use ($uid, $peerId) {
                $q->where(function ($q2) use ($uid, $peerId) {
                    $q2->where('sender_id', $uid)->where('recipient_id', $peerId);
                })->orWhere(function ($q2) use ($uid, $peerId) {
                    $q2->where('sender_id', $peerId)->where('recipient_id', $uid);
                });
            })
            ->orderByDesc('id');

        if ($before !== null) {
            $query->where('id', '<', $before);
        }

        $page = $query->limit($limit)->get()->sortBy('id')->values();
        $nextCursor = $page->isNotEmpty() ? $page->first()->id : null;

        $this->markIncomingReadThrough($uid, $peerId);

        return PrivateMessageResource::collection($page)->additional([
            'meta' => [
                'next_cursor' => $nextCursor,
            ],
        ]);
    }

    public function read(Request $request, User $peer): JsonResponse
    {
        $user = $request->user();
        if ((int) $peer->id === (int) $user->id) {
            return response()->json(['message' => 'Неможливо застосувати до себе.'], 422);
        }
        if (PrivateMessageGate::isBlocked($user, $peer)) {
            return response()->json(['message' => 'Повідомлення недоступні.'], 403);
        }

        $this->markIncomingReadThrough((int) $user->id, (int) $peer->id);

        return response()->json([
            'meta' => ['ok' => true],
        ]);
    }

    public function store(StorePrivateMessageRequest $request, User $peer): JsonResponse
    {
        $user = $request->user();
        if ((int) $peer->id === (int) $user->id) {
            return response()->json(['message' => 'Неможливо написати собі.'], 422);
        }

        if (PrivateMessageGate::isBlocked($user, $peer)) {
            return response()->json(['message' => 'Надсилання заблоковано (ігнор).'], 403);
        }

        $this->postingGate->ensureCanPost($user);

        $clientId = $request->validated('client_message_id');

        $existing = PrivateMessage::query()
            ->where('sender_id', $user->id)
            ->where('client_message_id', $clientId)
            ->first();

        if ($existing !== null) {
            if ((int) $existing->recipient_id !== (int) $peer->id) {
                return response()->json([
                    'message' => 'client_message_id already used for another peer.',
                ], 422);
            }

            return PrivateMessageResource::make($existing)
                ->additional(['meta' => ['duplicate' => true]])
                ->response()
                ->setStatusCode(200);
        }

        if ($resp = $this->messageFloodGate->ensureWithinLimit($user)) {
            return $resp;
        }

        $now = time();
        $body = $this->wordFilter->filter($request->validated('message'));

        try {
            $message = PrivateMessage::query()->create([
                'sender_id' => $user->id,
                'recipient_id' => $peer->id,
                'body' => $body,
                'sent_at' => $now,
                'sent_time' => date('H:i', $now),
                'client_message_id' => $clientId,
            ]);
        } catch (QueryException $e) {
            if ($this->isDuplicateKey($e)) {
                $retry = PrivateMessage::query()
                    ->where('sender_id', $user->id)
                    ->where('client_message_id', $clientId)
                    ->firstOrFail();

                if ((int) $retry->recipient_id !== (int) $peer->id) {
                    return response()->json([
                        'message' => 'client_message_id already used for another peer.',
                    ], 422);
                }

                return PrivateMessageResource::make($retry)
                    ->additional(['meta' => ['duplicate' => true]])
                    ->response()
                    ->setStatusCode(200);
            }

            throw $e;
        }

        broadcast(new PrivateMessageCreated($message));

        return PrivateMessageResource::make($message)
            ->additional(['meta' => ['duplicate' => false]])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Видалити всю історію привату між поточним користувачем і peer (T68).
     */
    public function destroyThread(Request $request, User $peer): JsonResponse
    {
        $user = $request->user();
        if ((int) $peer->id === (int) $user->id) {
            return response()->json(['message' => 'Неможливо застосувати до себе.'], 422);
        }
        if (PrivateMessageGate::isBlocked($user, $peer)) {
            return response()->json(['message' => 'Повідомлення недоступні.'], 403);
        }

        $uid = (int) $user->id;
        $pid = (int) $peer->id;

        DB::transaction(function () use ($uid, $pid): void {
            PrivateMessage::query()
                ->where(function ($q) use ($uid, $pid) {
                    $q->where(function ($q2) use ($uid, $pid) {
                        $q2->where('sender_id', $uid)->where('recipient_id', $pid);
                    })->orWhere(function ($q2) use ($uid, $pid) {
                        $q2->where('sender_id', $pid)->where('recipient_id', $uid);
                    });
                })
                ->delete();

            PrivateMessageReadState::query()
                ->where(function ($q) use ($uid, $pid) {
                    $q->where(function ($q2) use ($uid, $pid) {
                        $q2->where('user_id', $uid)->where('peer_id', $pid);
                    })->orWhere(function ($q2) use ($uid, $pid) {
                        $q2->where('user_id', $pid)->where('peer_id', $uid);
                    });
                })
                ->delete();
        });

        $pairLo = min($uid, $pid);
        $pairHi = max($uid, $pid);
        broadcast(new PrivateThreadCleared($uid, $pairLo, $pairHi));

        return response()->json([
            'meta' => [
                'ok' => true,
                'cleared_peer_id' => $pid,
            ],
        ]);
    }

    /**
     * Один запит замість N×count() по peer (T102).
     *
     * @param  list<int>  $peerIds
     * @return array<int, int> peer_id => кількість вхідних від peer, ще не «прочитаних»
     */
    private function unreadIncomingCountsByPeer(int $readerId, array $peerIds): array
    {
        if ($peerIds === []) {
            return [];
        }

        $rows = DB::table('private_messages as pm')
            ->selectRaw('pm.sender_id as peer_id, COUNT(*) as unread_count')
            ->leftJoin('private_message_read_states as rs', function ($join) use ($readerId) {
                $join->on('rs.peer_id', '=', 'pm.sender_id')
                    ->where('rs.user_id', '=', $readerId);
            })
            ->where('pm.recipient_id', $readerId)
            ->whereIn('pm.sender_id', $peerIds)
            ->whereRaw('pm.id > COALESCE(rs.last_read_incoming_message_id, 0)')
            ->groupBy('pm.sender_id')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->peer_id] = (int) $row->unread_count;
        }

        return $map;
    }

    /**
     * Вхідні від peer до reader: позначити прочитаними до поточного max(id) (T56).
     */
    private function markIncomingReadThrough(int $readerId, int $peerId): void
    {
        $maxId = (int) (PrivateMessage::query()
            ->where('sender_id', $peerId)
            ->where('recipient_id', $readerId)
            ->max('id') ?? 0);

        if ($maxId <= 0) {
            return;
        }

        $existing = PrivateMessageReadState::query()
            ->where('user_id', $readerId)
            ->where('peer_id', $peerId)
            ->value('last_read_incoming_message_id');

        $current = (int) ($existing ?? 0);
        if ($maxId <= $current) {
            return;
        }

        PrivateMessageReadState::query()->updateOrCreate(
            ['user_id' => $readerId, 'peer_id' => $peerId],
            ['last_read_incoming_message_id' => $maxId],
        );
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
