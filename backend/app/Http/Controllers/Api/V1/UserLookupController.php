<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserLookupController extends Controller
{
    /**
     * Префіксний пошук зареєстрованих користувачів для автокомпліту привату (T85).
     * Використовує унікальний індекс на `user_name` (B-tree) — умова `LIKE 'prefix%'` без провідного wildcard.
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:64'],
        ]);

        $self = $request->user();
        $prefix = $validated['q'];
        $likePattern = $this->sqlLikePrefixPattern($prefix);

        $rows = User::query()
            ->where('guest', false)
            ->whereNull('account_disabled_at')
            ->where('id', '!=', $self->id)
            ->whereRaw("user_name LIKE ? ESCAPE '!'", [$likePattern])
            ->orderBy('user_name')
            ->limit(15)
            ->get(['id', 'user_name', 'guest', 'user_rank', 'vip']);

        $data = $rows->map(fn (User $user): array => $this->lookupPayload($user));

        return response()->json([
            'data' => $data,
        ]);
    }

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

        return response()->json([
            'data' => $this->lookupPayload($user),
        ]);
    }

    /**
     * Префіксний LIKE з літералами `%`, `_`, `!` (MySQL + SQLite; індекс на `user_name` лишається префіксним).
     */
    private function sqlLikePrefixPattern(string $prefix): string
    {
        $p = str_replace('!', '!!', $prefix);
        $p = str_replace(['%', '_'], ['!%', '!_'], $p);

        return $p.'%';
    }

    /**
     * @return array{id: int, user_name: string, guest: bool, chat_role: string, badge_color: string}
     */
    private function lookupPayload(User $user): array
    {
        $role = $user->resolveChatRole();

        return [
            'id' => $user->id,
            'user_name' => $user->user_name,
            'guest' => (bool) $user->guest,
            'chat_role' => $role->value,
            'badge_color' => $role->badgeColor(),
        ];
    }
}
