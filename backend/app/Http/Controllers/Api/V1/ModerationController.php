<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BannedIp;
use App\Models\ChatMessage;
use App\Models\FilterWord;
use App\Models\User;
use App\Services\Moderation\ModerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ModerationController extends Controller
{
    public function __construct(
        private readonly ModerationService $moderation,
    ) {}

    public function indexBannedIps(): JsonResponse
    {
        $rows = BannedIp::query()->orderBy('id')->get(['id', 'ip', 'created_at']);

        return response()->json(['data' => $rows]);
    }

    public function storeBannedIp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ip' => ['required', 'string', 'max:45', 'ip'],
        ]);

        $row = $this->moderation->banIp($data['ip']);

        Log::info('moderation.ip_ban.created', [
            'actor_id' => $request->user()->id,
            'banned_ip' => $row->ip,
            'banned_ip_id' => $row->id,
        ]);

        return response()->json(['data' => $row], 201);
    }

    public function destroyBannedIp(Request $request, BannedIp $bannedIp): JsonResponse
    {
        $ip = $bannedIp->ip;
        $id = (int) $bannedIp->id;
        $this->moderation->unbanIp($id);

        Log::info('moderation.ip_ban.removed', [
            'actor_id' => $request->user()->id,
            'banned_ip' => $ip,
            'banned_ip_id' => $id,
        ]);

        return response()->json(null, 204);
    }

    /**
     * T60: черга повідомлень з `moderation_flag_at` (у т.ч. видалені — для зняття прапорця).
     */
    public function indexFlaggedMessages(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'room_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = isset($validated['per_page'])
            ? (int) $validated['per_page']
            : 25;
        $perPage = max(1, min(100, $perPage));

        $q = ChatMessage::query()
            ->whereNotNull('moderation_flag_at')
            ->with([
                'user:id,user_name',
                'room:room_id,room_name',
            ])
            ->orderByDesc('moderation_flag_at')
            ->orderByDesc('post_id');

        if ($request->filled('room_id')) {
            $q->where('post_roomid', (int) $validated['room_id']);
        }

        $page = $q->paginate($perPage);

        $data = $page->getCollection()->map(fn (ChatMessage $m) => $this->transformFlaggedMessage($m))->values();

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    /**
     * T60: зняти прапорець модерації (`moderation_flag_at` → null).
     */
    public function clearModerationFlag(Request $request, ChatMessage $message): JsonResponse
    {
        if ($message->moderation_flag_at === null) {
            return response()->json(['message' => 'Повідомлення без прапорця модерації.'], 422);
        }

        $message->moderation_flag_at = null;
        $message->save();

        Log::info('moderation.flagged_message.cleared', [
            'actor_id' => $request->user()->id,
            'post_id' => $message->post_id,
            'post_roomid' => $message->post_roomid,
        ]);

        return response()->json([
            'data' => [
                'post_id' => $message->post_id,
                'moderation_flag_at' => null,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function transformFlaggedMessage(ChatMessage $m): array
    {
        $deleted = $m->post_deleted_at !== null && (int) $m->post_deleted_at > 0;
        $body = (string) $m->post_message;
        if ($deleted) {
            $snippet = '';
        } else {
            $snippet = mb_strlen($body) > 160 ? mb_substr($body, 0, 160).'…' : $body;
        }

        $author = $m->user !== null ? (string) $m->user->user_name : (string) $m->post_user;

        return [
            'post_id' => $m->post_id,
            'post_roomid' => $m->post_roomid,
            'room_name' => $m->room !== null ? (string) $m->room->room_name : '',
            'author_name' => $author,
            'snippet' => $snippet,
            'post_time' => $m->post_time,
            'post_date' => $m->post_date,
            'moderation_flag_at' => $m->moderation_flag_at,
            'post_deleted_at' => $m->post_deleted_at,
            'is_deleted' => $deleted,
        ];
    }

    public function indexFilterWords(): JsonResponse
    {
        $rows = FilterWord::query()
            ->orderBy('id')
            ->get(['id', 'word', 'category', 'match_mode', 'action', 'mute_minutes', 'created_at']);

        return response()->json(['data' => $rows]);
    }

    public function storeFilterWord(Request $request): JsonResponse
    {
        $data = $request->validate([
            'word' => ['required', 'string', 'min:2', 'max:191', 'regex:/\S/'],
            'category' => ['nullable', 'string', 'max:64'],
            'match_mode' => ['sometimes', 'nullable', 'string', Rule::in([FilterWord::MATCH_SUBSTRING, FilterWord::MATCH_WHOLE_WORD])],
            'action' => ['sometimes', 'nullable', 'string', Rule::in([
                FilterWord::ACTION_MASK,
                FilterWord::ACTION_REJECT,
                FilterWord::ACTION_FLAG,
                FilterWord::ACTION_TEMP_MUTE,
            ])],
            'mute_minutes' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:525600'],
        ]);

        $data = $this->normalizedFilterWordAttributes($data, forCreate: true);

        $row = $this->moderation->addFilterWord($data);

        Log::info('moderation.filter_word.created', [
            'actor_id' => $request->user()->id,
            'filter_word_id' => $row->id,
            'category' => $row->category,
            'action' => $row->action,
            'match_mode' => $row->match_mode,
        ]);

        return response()->json(['data' => $row], 201);
    }

    public function updateFilterWord(Request $request, FilterWord $filterWord): JsonResponse
    {
        $data = $request->validate([
            'word' => ['sometimes', 'string', 'min:2', 'max:191', 'regex:/\S/'],
            'category' => ['sometimes', 'nullable', 'string', 'max:64'],
            'match_mode' => ['sometimes', 'nullable', 'string', Rule::in([FilterWord::MATCH_SUBSTRING, FilterWord::MATCH_WHOLE_WORD])],
            'action' => ['sometimes', 'nullable', 'string', Rule::in([
                FilterWord::ACTION_MASK,
                FilterWord::ACTION_REJECT,
                FilterWord::ACTION_FLAG,
                FilterWord::ACTION_TEMP_MUTE,
            ])],
            'mute_minutes' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:525600'],
        ]);

        if ($data === []) {
            return response()->json(['message' => 'Немає полів для оновлення.'], 422);
        }

        $merged = array_merge($filterWord->only([
            'word', 'category', 'match_mode', 'action', 'mute_minutes',
        ]), $data);
        $merged = $this->normalizedFilterWordAttributes($merged, forCreate: false);

        $row = $this->moderation->updateFilterWord($filterWord, $merged);

        Log::info('moderation.filter_word.updated', [
            'actor_id' => $request->user()->id,
            'filter_word_id' => $row->id,
            'category' => $row->category,
            'action' => $row->action,
            'match_mode' => $row->match_mode,
        ]);

        return response()->json(['data' => $row]);
    }

    public function destroyFilterWord(Request $request, FilterWord $filterWord): JsonResponse
    {
        $id = (int) $filterWord->id;
        $this->moderation->removeFilterWord($id);

        Log::info('moderation.filter_word.removed', [
            'actor_id' => $request->user()->id,
            'filter_word_id' => $id,
        ]);

        return response()->json(null, 204);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizedFilterWordAttributes(array $data, bool $forCreate): array
    {
        $action = $data['action'] ?? FilterWord::ACTION_MASK;
        $data['action'] = $action;
        $data['match_mode'] = $data['match_mode'] ?? FilterWord::MATCH_SUBSTRING;

        $cat = isset($data['category']) ? trim((string) $data['category']) : '';
        $data['category'] = $cat !== '' ? $cat : 'default';

        if ($action !== FilterWord::ACTION_TEMP_MUTE) {
            $data['mute_minutes'] = null;
        } elseif (! array_key_exists('mute_minutes', $data) && ! $forCreate) {
            unset($data['mute_minutes']);
        }

        return $data;
    }

    public function muteUser(Request $request, User $user): JsonResponse
    {
        $actor = $request->user();
        $this->assertCanActOn($actor, $user);

        $data = $request->validate([
            'minutes' => ['nullable', 'integer', 'min:0', 'max:525600'],
        ]);

        $minutes = $data['minutes'] ?? null;
        $this->moderation->muteUser($user, $minutes);
        $user->refresh();

        Log::info('moderation.user.mute', [
            'actor_id' => $actor->id,
            'target_user_id' => $user->id,
            'minutes' => $minutes,
            'mute_until' => $user->mute_until,
        ]);

        return response()->json(['data' => [
            'id' => $user->id,
            'user_name' => $user->user_name,
            'mute_until' => $user->mute_until,
            'kick_until' => $user->kick_until,
        ]]);
    }

    public function kickUser(Request $request, User $user): JsonResponse
    {
        $actor = $request->user();
        $this->assertCanActOn($actor, $user);

        $data = $request->validate([
            'minutes' => ['nullable', 'integer', 'min:0', 'max:525600'],
        ]);

        $minutes = $data['minutes'] ?? null;
        $this->moderation->kickUser($user, $minutes);
        $user->refresh();

        Log::info('moderation.user.kick', [
            'actor_id' => $actor->id,
            'target_user_id' => $user->id,
            'minutes' => $minutes,
            'kick_until' => $user->kick_until,
        ]);

        return response()->json(['data' => [
            'id' => $user->id,
            'user_name' => $user->user_name,
            'mute_until' => $user->mute_until,
            'kick_until' => $user->kick_until,
        ]]);
    }

    private function assertCanActOn(User $actor, User $target): void
    {
        if ((int) $actor->id === (int) $target->id) {
            abort(422, 'Неможливо застосувати до власного облікового запису.');
        }
        if (! $target->canReceiveStaffManagementFrom($actor)) {
            abort(403, 'Недостатньо прав для дії щодо цього користувача.');
        }
    }
}
