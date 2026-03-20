<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BannedIp;
use App\Models\FilterWord;
use App\Models\User;
use App\Services\Moderation\ModerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        return response()->json(['data' => $row], 201);
    }

    public function destroyBannedIp(BannedIp $bannedIp): JsonResponse
    {
        $this->moderation->unbanIp((int) $bannedIp->id);

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
            'word' => ['required', 'string', 'max:191', 'regex:/\S/'],
        ]);

        $row = $this->moderation->addFilterWord($data['word']);

        return response()->json(['data' => $row], 201);
    }

    public function destroyFilterWord(FilterWord $filterWord): JsonResponse
    {
        $this->moderation->removeFilterWord((int) $filterWord->id);

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
        if ((int) $target->user_rank >= (int) $actor->user_rank) {
            abort(403, 'Недостатньо прав для дії щодо цього користувача.');
        }
    }
}
