<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class StaffUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:191'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        /** @var User $actor */
        $actor = $request->user();
        $q = trim($validated['q']);
        $needle = mb_strtolower($q, 'UTF-8');
        $perPage = $validated['per_page'] ?? 20;

        $query = User::query()
            ->where(function ($w) use ($q, $needle): void {
                $w->whereRaw('instr(lower(user_name), ?) > 0', [$needle]);
                if (ctype_digit($q)) {
                    $w->orWhere('id', (int) $q);
                }
                if (str_contains($q, '@')) {
                    $w->orWhereRaw('instr(lower(ifnull(email, "")), ?) > 0', [$needle]);
                }
            })
            ->orderByDesc('id');

        $paginator = $query->paginate($perPage)->withQueryString();

        $data = $paginator->getCollection()->map(fn (User $u) => $this->staffUserPayload($u, $actor));

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        if (! $user->canReceiveStaffManagementFrom($actor)) {
            if ((int) $user->id === (int) $actor->id) {
                abort(422, 'Неможливо змінити власний обліковий запис.');
            }
            abort(403, 'Недостатньо прав для дії щодо цього користувача.');
        }

        $validated = $request->validate([
            'vip' => ['sometimes', 'boolean'],
            'user_rank' => ['sometimes', 'integer', Rule::in([User::RANK_USER, User::RANK_MODERATOR, User::RANK_ADMIN])],
        ]);

        if ($validated === []) {
            abort(422, 'Немає полів для оновлення.');
        }

        if (array_key_exists('user_rank', $validated)) {
            $newRank = (int) $validated['user_rank'];
            if ($newRank > (int) $actor->user_rank) {
                abort(403, 'Неможливо призначити роль вищу за власну.');
            }
            $user->user_rank = $newRank;
        }

        if (array_key_exists('vip', $validated)) {
            if ($user->guest) {
                abort(422, 'VIP недоступно для гостя.');
            }
            $user->vip = (bool) $validated['vip'];
        }

        $user->save();

        Log::info('staff.user.roles_updated', [
            'actor_id' => $actor->id,
            'target_user_id' => $user->id,
            'vip' => $validated['vip'] ?? null,
            'user_rank' => $validated['user_rank'] ?? null,
        ]);

        return response()->json(['data' => $this->staffUserPayload($user->fresh(), $actor)]);
    }

    public function updateProfile(Request $request, User $user): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        if (! $user->canReceiveStaffManagementFrom($actor)) {
            if ((int) $user->id === (int) $actor->id) {
                abort(422, 'Неможливо змінити власний обліковий запис.');
            }
            abort(403, 'Недостатньо прав для дії щодо цього користувача.');
        }

        if ($user->guest) {
            abort(422, 'Профіль гостя не редагується через цей ендпоінт.');
        }

        $sexValues = ['male', 'female', 'other', 'prefer_not'];
        $socialRule = ['sometimes', 'nullable', 'string', 'max:500'];

        $validated = $request->validate([
            'profile' => ['sometimes', 'array'],
            'profile.country' => ['sometimes', 'nullable', 'string', 'max:100'],
            'profile.region' => ['sometimes', 'nullable', 'string', 'max:100'],
            'profile.age' => ['sometimes', 'nullable', 'integer', 'min:13', 'max:120'],
            'profile.sex' => ['sometimes', 'nullable', 'string', Rule::in($sexValues)],
            'profile.country_hidden' => ['sometimes', 'boolean'],
            'profile.region_hidden' => ['sometimes', 'boolean'],
            'profile.age_hidden' => ['sometimes', 'boolean'],
            'profile.sex_hidden' => ['sometimes', 'boolean'],
            'profile.occupation' => ['sometimes', 'nullable', 'string', 'max:191'],
            'profile.about' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'social_links' => ['sometimes', 'array'],
            'social_links.facebook' => $socialRule,
            'social_links.instagram' => $socialRule,
            'social_links.telegram' => $socialRule,
            'social_links.twitter' => $socialRule,
            'social_links.youtube' => $socialRule,
            'social_links.tiktok' => $socialRule,
            'social_links.discord' => $socialRule,
            'social_links.website' => $socialRule,
            'notification_sound_prefs' => ['sometimes', 'array'],
            'notification_sound_prefs.public_messages' => ['sometimes', 'boolean'],
            'notification_sound_prefs.mentions' => ['sometimes', 'boolean'],
            'notification_sound_prefs.private' => ['sometimes', 'boolean'],
            'notification_sound_prefs.volume_percent' => ['sometimes', 'integer', 'min:0', 'max:100'],
        ]);

        if ($validated === []) {
            abort(422, 'Немає полів для оновлення.');
        }

        if (isset($validated['profile']) && is_array($validated['profile'])) {
            $p = $validated['profile'];
            $map = [
                'country' => 'profile_country',
                'region' => 'profile_region',
                'age' => 'profile_age',
                'sex' => 'profile_sex',
                'country_hidden' => 'profile_country_hidden',
                'region_hidden' => 'profile_region_hidden',
                'age_hidden' => 'profile_age_hidden',
                'sex_hidden' => 'profile_sex_hidden',
                'occupation' => 'profile_occupation',
                'about' => 'profile_about',
            ];
            foreach ($map as $jsonKey => $column) {
                if (! array_key_exists($jsonKey, $p)) {
                    continue;
                }
                $user->{$column} = $p[$jsonKey];
            }
        }

        if (isset($validated['social_links']) && is_array($validated['social_links'])) {
            $merged = array_merge(User::defaultSocialLinkKeys(), $user->social_links ?? []);
            foreach (User::defaultSocialLinkKeys() as $key => $_) {
                if (array_key_exists($key, $validated['social_links'])) {
                    $merged[$key] = $validated['social_links'][$key] ?? '';
                }
            }
            $user->social_links = $merged;
        }

        if (isset($validated['notification_sound_prefs']) && is_array($validated['notification_sound_prefs'])) {
            $user->notification_sound_prefs = array_replace(
                User::defaultNotificationSoundPrefs(),
                $user->notification_sound_prefs ?? [],
                $validated['notification_sound_prefs']
            );
        }

        $user->save();

        Log::info('staff.user.profile_updated', [
            'actor_id' => $actor->id,
            'target_user_id' => $user->id,
            'keys' => array_keys($validated),
        ]);

        return response()->json(['data' => $this->staffUserPayload($user->fresh(), $actor)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function staffUserPayload(User $user, User $actor): array
    {
        $role = $user->resolveChatRole();

        $row = [
            'id' => $user->id,
            'user_name' => $user->user_name,
            'guest' => (bool) $user->guest,
            'user_rank' => (int) $user->user_rank,
            'vip' => (bool) $user->vip,
            'chat_role' => $role->value,
            'badge_color' => $role->badgeColor(),
            'mute_until' => $user->mute_until,
            'kick_until' => $user->kick_until,
            'can_manage' => $user->canReceiveStaffManagementFrom($actor),
        ];

        if (! $user->guest) {
            $row['email'] = $user->email;
        }

        if (! $user->guest) {
            $row['profile'] = [
                'country' => $user->profile_country,
                'region' => $user->profile_region,
                'age' => $user->profile_age,
                'sex' => $user->profile_sex,
                'country_hidden' => (bool) $user->profile_country_hidden,
                'region_hidden' => (bool) $user->profile_region_hidden,
                'age_hidden' => (bool) $user->profile_age_hidden,
                'sex_hidden' => (bool) $user->profile_sex_hidden,
                'occupation' => $user->profile_occupation,
                'about' => $user->profile_about,
            ];
        }

        return $row;
    }
}
