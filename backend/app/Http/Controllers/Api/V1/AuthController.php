<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\GuestRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\ChatSetting;
use App\Models\User;
use App\Services\Mail\TransactionalMailService;
use App\Services\Moderation\ProxyCheck\ProxyCheckGate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct(
        private readonly TransactionalMailService $transactionalMail,
        private readonly ProxyCheckGate $proxyCheckGate,
    ) {}

    /**
     * Bcrypt з відомим відкритим текстом (Laravel testing stub) — для вирівнювання часу відповіді,
     * коли облікового запису немає / гість / без пароля (зменшує простий таймінг-канал).
     */
    /** Публічний констант для узгодженого timing-mitigation з іншими auth-ендпоінтами (T94). */
    public const AUTH_TIMING_DUMMY_BCRYPT = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    public function register(RegisterRequest $request): JsonResponse
    {
        if ($resp = $this->proxyCheckGate->denyIfNeeded($request, 'auth_register')) {
            return $resp;
        }

        if (! ChatSetting::current()->resolvedRegistrationFlags()['registration_open']) {
            return response()->json([
                'message' => 'Реєстрацію тимчасово вимкнено адміністратором.',
            ], 403);
        }

        $user = User::query()->create([
            'user_name' => $request->validated('user_name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'guest' => false,
        ]);

        Auth::guard('web')->login($user, true);
        $request->session()->regenerate();

        $this->transactionalMail->sendWelcomeRegisteredUser($user);

        return UserResource::make($user)->response()->setStatusCode(201);
    }

    public function login(LoginRequest $request): UserResource|JsonResponse
    {
        if ($resp = $this->proxyCheckGate->denyIfNeeded($request, 'auth_login')) {
            return $resp;
        }

        $plain = $request->validated('password');
        $user = User::query()->where('user_name', $request->validated('user_name'))->first();

        if ($user !== null && $user->isSystemBot()) {
            Hash::check($plain, self::AUTH_TIMING_DUMMY_BCRYPT);
            throw ValidationException::withMessages([
                'user_name' => [__('auth.failed')],
            ]);
        }

        $passwordOk = false;
        if ($user && ! $user->guest && $user->password) {
            $passwordOk = Hash::check($plain, $user->password);
        } else {
            Hash::check($plain, self::AUTH_TIMING_DUMMY_BCRYPT);
        }

        if (! $passwordOk || ! $user || $user->guest || ! $user->password) {
            throw ValidationException::withMessages([
                'user_name' => [__('auth.failed')],
            ]);
        }

        if ($user->account_disabled_at !== null) {
            throw ValidationException::withMessages([
                'user_name' => [__('auth.failed')],
            ]);
        }

        Auth::guard('web')->login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return UserResource::make($user);
    }

    public function guest(GuestRequest $request): JsonResponse
    {
        if ($resp = $this->proxyCheckGate->denyIfNeeded($request, 'auth_guest')) {
            return $resp;
        }

        $userName = $request->validated('user_name') ?? $this->uniqueGuestUserName();

        $user = User::query()->create([
            'user_name' => $userName,
            'email' => null,
            'password' => null,
            'guest' => true,
        ]);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return UserResource::make($user)->response()->setStatusCode(201);
    }

    public function user(Request $request): UserResource
    {
        $user = $request->user()->fresh();

        return UserResource::make($user);
    }

    public function logout(Request $request): Response
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    private function uniqueGuestUserName(): string
    {
        for ($i = 0; $i < 20; $i++) {
            $candidate = 'guest_'.Str::lower(Str::random(12));
            if (! User::query()->where('user_name', $candidate)->exists()) {
                return $candidate;
            }
        }

        throw ValidationException::withMessages([
            'user_name' => ['Could not allocate a guest name. Try again.'],
        ]);
    }
}
