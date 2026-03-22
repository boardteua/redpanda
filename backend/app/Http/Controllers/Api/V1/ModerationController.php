<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BannedIp;
use App\Models\FilterWord;
use App\Models\User;
use App\Services\Moderation\ModerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

    public function indexFilterWords(): JsonResponse
    {
        $rows = FilterWord::query()->orderBy('id')->get(['id', 'word', 'created_at']);

        return response()->json(['data' => $rows]);
    }

    public function storeFilterWord(Request $request): JsonResponse
    {
        $data = $request->validate([
            'word' => ['required', 'string', 'min:2', 'max:191', 'regex:/\S/'],
        ]);

        $row = $this->moderation->addFilterWord($data['word']);

        Log::info('moderation.filter_word.created', [
            'actor_id' => $request->user()->id,
            'filter_word_id' => $row->id,
            'word' => $row->word,
        ]);

        return response()->json(['data' => $row], 201);
    }

    public function destroyFilterWord(Request $request, FilterWord $filterWord): JsonResponse
    {
        $id = (int) $filterWord->id;
        $word = $filterWord->word;
        $this->moderation->removeFilterWord($id);

        Log::info('moderation.filter_word.removed', [
            'actor_id' => $request->user()->id,
            'filter_word_id' => $id,
            'word' => $word,
        ]);

        return response()->json(null, 204);
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
