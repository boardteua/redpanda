<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\GuestRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::query()->create([
            'user_name' => $request->validated('user_name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'guest' => false,
        ]);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return UserResource::make($user)->response()->setStatusCode(201);
    }

    public function login(LoginRequest $request): UserResource
    {
        $user = User::query()->where('user_name', $request->validated('user_name'))->first();

        if (
            ! $user
            || $user->guest
            || ! $user->password
            || ! Hash::check($request->validated('password'), $user->password)
        ) {
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
