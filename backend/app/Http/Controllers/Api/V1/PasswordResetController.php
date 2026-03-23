<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    /** Уніфікована відповідь (без user enumeration). */
    private const FORGOT_PUBLIC_MESSAGE = 'Якщо для цієї адреси є обліковий запис з паролем, ми надіслали лист із посиланням для скидання.';

    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        $email = $request->validated('email');
        $user = User::query()->where('email', $email)->first();

        if ($user === null || $user->guest || $user->password === null || $user->email === null) {
            Hash::check('password', AuthController::AUTH_TIMING_DUMMY_BCRYPT);

            return response()->json(['message' => self::FORGOT_PUBLIC_MESSAGE]);
        }

        $status = Password::broker()->sendResetLink(['email' => $user->email]);

        if ($status !== Password::RESET_LINK_SENT && $status !== Password::RESET_THROTTLED) {
            Hash::check('password', AuthController::AUTH_TIMING_DUMMY_BCRYPT);
        }

        return response()->json(['message' => self::FORGOT_PUBLIC_MESSAGE]);
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password', 'password_confirmation', 'token');

        $status = Password::broker()->reset(
            $credentials,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                $this->invalidateOtherSessionsForUser((int) $user->id);
                $user->tokens()->delete();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Пароль оновлено. Увійдіть з новим паролем.',
            ]);
        }

        if ($status === Password::INVALID_TOKEN) {
            throw ValidationException::withMessages([
                'token' => ['Посилання для скидання недійсне або прострочене.'],
            ]);
        }

        if ($status === Password::INVALID_USER) {
            throw ValidationException::withMessages([
                'email' => ['Обліковий запис для цієї адреси не знайдено.'],
            ]);
        }

        throw ValidationException::withMessages([
            'email' => ['Не вдалося скинути пароль. Перевірте дані або запросіть нове посилання.'],
        ]);
    }

    private function invalidateOtherSessionsForUser(int $userId): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        $table = (string) config('session.table', 'sessions');
        DB::table($table)->where('user_id', $userId)->delete();
    }
}
