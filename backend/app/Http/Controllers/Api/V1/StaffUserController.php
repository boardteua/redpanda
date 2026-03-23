<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Iso3166Alpha2Uk;
use App\Services\Moderation\ModerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StaffUserController extends Controller
{
    /** @var list<string> */
    private const BULK_ACTIONS = [
        'set_vip',
        'clear_vip',
        'set_rank',
        'mute',
        'clear_mute',
        'kick',
        'clear_kick',
        'disable_account',
        'enable_account',
    ];

    public function __construct(
        private readonly ModerationService $moderation,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:191'],
            'browse' => ['sometimes', 'boolean'],
            'guest' => ['sometimes', Rule::in([0, 1, '0', '1', true, false, 'true', 'false'])],
            'user_rank' => ['sometimes', 'integer', Rule::in([User::RANK_USER, User::RANK_MODERATOR, User::RANK_ADMIN])],
            'vip' => ['sometimes', 'boolean'],
            'muted' => ['sometimes', Rule::in([0, 1, '0', '1', true, false, 'true', 'false'])],
            'kicked' => ['sometimes', Rule::in([0, 1, '0', '1', true, false, 'true', 'false'])],
            'disabled' => ['sometimes', Rule::in([0, 1, '0', '1', true, false, 'true', 'false'])],
            'sort' => ['sometimes', 'string', Rule::in(['id', 'user_name', 'created_at'])],
            'direction' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);

        /** @var User $actor */
        $actor = $request->user();

        $qNorm = isset($validated['q']) ? trim((string) $validated['q']) : '';
        $browse = filter_var($validated['browse'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $hasFilters = $request->has('guest')
            || $request->has('user_rank')
            || $request->has('vip')
            || $request->has('muted')
            || $request->has('kicked')
            || $request->has('disabled');

        if (! $browse && ! $hasFilters && $qNorm === '') {
            abort(422, 'Вкажіть пошуковий запит q, передайте browse=1 або застосуйте фільтри.');
        }

        if ($qNorm !== '' && mb_strlen($qNorm) < 1) {
            abort(422, 'Запит q не може бути порожнім.');
        }

        $perPage = $validated['per_page'] ?? 20;
        $sort = $validated['sort'] ?? 'id';
        $direction = strtolower((string) ($validated['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        $query = User::query();

        if ($qNorm !== '') {
            $needle = mb_strtolower($qNorm, 'UTF-8');
            $query->where(function ($w) use ($qNorm, $needle): void {
                $w->whereRaw('instr(lower(user_name), ?) > 0', [$needle]);
                if (ctype_digit($qNorm)) {
                    $w->orWhere('id', (int) $qNorm);
                }
                if (str_contains($qNorm, '@')) {
                    $w->orWhereRaw('instr(lower(ifnull(email, "")), ?) > 0', [$needle]);
                }
            });
        }

        if ($request->has('guest')) {
            $query->where('guest', $this->coerceBool($validated['guest']));
        }

        if ($request->has('user_rank')) {
            $query->where('user_rank', (int) $validated['user_rank']);
        }

        if ($request->has('vip')) {
            $query->where('vip', filter_var($validated['vip'], FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('muted')) {
            $active = $this->coerceBool($validated['muted']);
            if ($active) {
                $query->whereNotNull('mute_until')->where('mute_until', '>', time());
            } else {
                $query->where(function ($w): void {
                    $w->whereNull('mute_until')->orWhere('mute_until', '<=', time());
                });
            }
        }

        if ($request->has('kicked')) {
            $active = $this->coerceBool($validated['kicked']);
            if ($active) {
                $query->whereNotNull('kick_until')->where('kick_until', '>', time());
            } else {
                $query->where(function ($w): void {
                    $w->whereNull('kick_until')->orWhere('kick_until', '<=', time());
                });
            }
        }

        if ($request->has('disabled')) {
            $disabled = $this->coerceBool($validated['disabled']);
            if ($disabled) {
                $query->whereNotNull('account_disabled_at');
            } else {
                $query->whereNull('account_disabled_at');
            }
        }

        $query->orderBy($sort, $direction);
        if ($sort !== 'id') {
            $query->orderBy('id', $direction);
        }

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

    public function show(Request $request, User $user): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json(['data' => $this->staffUserPayload($user, $actor)]);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $validated = $request->validate([
            'user_name' => ['required', 'string', 'min:2', 'max:191', 'unique:users,user_name', 'regex:/^[\p{L}\p{N}_-]+$/u'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['nullable', 'string', Password::defaults()],
        ]);

        $plain = $validated['password'] ?? null;
        $generated = false;
        if ($plain === null || $plain === '') {
            $plain = Str::password(20);
            $generated = true;
        }

        $user = User::query()->create([
            'user_name' => $validated['user_name'],
            'email' => $validated['email'],
            'password' => $plain,
            'guest' => false,
        ]);

        Log::info('staff.user.created', [
            'actor_id' => $actor->id,
            'target_user_id' => $user->id,
            'password_generated' => $generated,
        ]);

        $payload = [
            'data' => $this->staffUserPayload($user->fresh(), $actor),
        ];

        if ($generated) {
            $payload['meta'] = ['generated_password' => $plain];
        }

        return response()->json($payload, 201);
    }

    public function bulk(Request $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1', 'max:50'],
            'user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'action' => ['required', 'string', Rule::in(self::BULK_ACTIONS)],
            'minutes' => ['nullable', 'integer', 'min:1', 'max:525600'],
            'user_rank' => ['nullable', 'integer', Rule::in([User::RANK_USER, User::RANK_MODERATOR, User::RANK_ADMIN])],
        ]);

        $action = $validated['action'];
        $ids = $validated['user_ids'];

        if (in_array($action, ['mute', 'kick'], true)) {
            $minutes = $validated['minutes'] ?? null;
            if ($minutes === null || (int) $minutes < 1) {
                abort(422, 'Для mute/kick потрібно minutes від 1 до 525600.');
            }
        }

        if ($action === 'set_rank' && ! array_key_exists('user_rank', $validated)) {
            abort(422, 'Для set_rank потрібно поле user_rank.');
        }
        if ($action === 'set_rank' && $validated['user_rank'] === null) {
            abort(422, 'Для set_rank потрібно поле user_rank.');
        }

        DB::transaction(function () use ($actor, $action, $ids, $validated): void {
            foreach ($ids as $id) {
                $user = User::query()->findOrFail($id);

                if ((int) $user->id === (int) $actor->id) {
                    abort(422, 'Неможливо застосувати масову дію до власного облікового запису.');
                }

                if (! $user->canReceiveStaffManagementFrom($actor)) {
                    abort(422, 'Недостатньо прав щодо користувача #'.$user->id.'.');
                }

                match ($action) {
                    'set_vip' => $this->bulkSetVip($user, true),
                    'clear_vip' => $this->bulkSetVip($user, false),
                    'set_rank' => $this->bulkSetRank($actor, $user, (int) $validated['user_rank']),
                    'mute' => $this->moderation->muteUser($user, (int) $validated['minutes']),
                    'clear_mute' => $this->moderation->muteUser($user, null),
                    'kick' => $this->moderation->kickUser($user, (int) $validated['minutes']),
                    'clear_kick' => $this->moderation->kickUser($user, null),
                    'disable_account' => $this->bulkDisableAccount($user, true),
                    'enable_account' => $this->bulkDisableAccount($user, false),
                    default => abort(422, 'Невідома дія.'),
                };
            }
        });

        Log::info('staff.user.bulk', [
            'actor_id' => $actor->id,
            'action' => $action,
            'count' => count($ids),
        ]);

        return response()->json([
            'data' => [
                'ok' => true,
                'affected' => count($ids),
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
            'account_disabled' => ['sometimes', 'boolean'],
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

        if (array_key_exists('account_disabled', $validated)) {
            if ($user->guest) {
                abort(422, 'Обліковий запис гостя не вимикається цим способом.');
            }
            $user->account_disabled_at = filter_var($validated['account_disabled'], FILTER_VALIDATE_BOOLEAN)
                ? now()
                : null;
        }

        $user->save();

        Log::info('staff.user.roles_updated', [
            'actor_id' => $actor->id,
            'target_user_id' => $user->id,
            'vip' => $validated['vip'] ?? null,
            'user_rank' => $validated['user_rank'] ?? null,
            'account_disabled' => $validated['account_disabled'] ?? null,
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

        $profileIn = $request->input('profile');
        if (is_array($profileIn) && array_key_exists('country', $profileIn)) {
            $country = $profileIn['country'];
            if ($country === null || $country === '') {
                $profileIn['country'] = null;
            } elseif (is_string($country)) {
                $c = strtoupper(trim($country));
                $profileIn['country'] = $c === '' ? null : $c;
            }
            $request->merge(['profile' => $profileIn]);
        }

        $sexValues = ['male', 'female', 'other', 'prefer_not'];
        $socialRule = ['sometimes', 'nullable', 'string', 'max:500'];

        $validated = $request->validate([
            'profile' => ['sometimes', 'array'],
            'profile.country' => ['sometimes', 'nullable', 'string', Rule::in(Iso3166Alpha2Uk::codes())],
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

    private function bulkSetVip(User $user, bool $vip): void
    {
        if ($user->guest) {
            abort(422, 'VIP недоступно для гостя #'.$user->id.'.');
        }
        $user->forceFill(['vip' => $vip])->save();
    }

    private function bulkSetRank(User $actor, User $user, int $newRank): void
    {
        if ($newRank > (int) $actor->user_rank) {
            abort(422, 'Неможливо призначити ранг вищий за власний (користувач #'.$user->id.').');
        }
        $user->forceFill(['user_rank' => $newRank])->save();
    }

    private function bulkDisableAccount(User $user, bool $disable): void
    {
        if ($user->guest) {
            abort(422, 'Гість #'.$user->id.' не має облікового запису для вимкнення.');
        }
        $user->forceFill([
            'account_disabled_at' => $disable ? now() : null,
        ])->save();
    }

    private function coerceBool(mixed $v): bool
    {
        if (is_bool($v)) {
            return $v;
        }
        if ($v === 1 || $v === '1') {
            return true;
        }
        if ($v === 0 || $v === '0') {
            return false;
        }
        if ($v === 'true') {
            return true;
        }
        if ($v === 'false') {
            return false;
        }

        return filter_var($v, FILTER_VALIDATE_BOOLEAN);
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
            'muted_active' => $user->isMutedAt(),
            'kicked_active' => $user->isKickedAt(),
            'account_disabled' => $user->account_disabled_at !== null,
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
