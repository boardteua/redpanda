<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    /**
     * @return array<string, string>
     */
    private function statefulHeaders(): array
    {
        return ['Referer' => config('app.url')];
    }

    public function test_forgot_password_returns_uniform_message_for_unknown_email(): void
    {
        Notification::fake();

        $r = $this->from(config('app.url'))
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/auth/forgot-password', [
                'email' => 'nobody@example.com',
            ]);

        $r->assertOk();
        $this->assertSame(
            'Якщо для цієї адреси є обліковий запис з паролем, ми надіслали лист із посиланням для скидання.',
            $r->json('message'),
        );
        Notification::assertNothingSent();
    }

    public function test_forgot_password_sends_notification_for_eligible_user(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'u@example.com',
            'guest' => false,
            'password' => 'password-secure-1',
        ]);

        $r = $this->from(config('app.url'))
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/auth/forgot-password', [
                'email' => 'u@example.com',
            ]);

        $r->assertOk();
        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_forgot_password_does_not_send_for_user_without_password(): void
    {
        Notification::fake();

        $u = User::factory()->create([
            'email' => 'oauth@example.com',
            'guest' => false,
        ]);
        $u->forceFill(['password' => null, 'legacy_imported_at' => null])->save();

        $this->from(config('app.url'))
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/auth/forgot-password', [
                'email' => 'oauth@example.com',
            ])->assertOk();

        Notification::assertNothingSent();
    }

    public function test_forgot_password_sends_for_legacy_imported_user_without_password(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'legacy@example.com',
            'guest' => false,
        ]);
        $user->forceFill([
            'password' => null,
            'legacy_imported_at' => now(),
        ])->save();

        $this->from(config('app.url'))
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/auth/forgot-password', [
                'email' => 'legacy@example.com',
            ])->assertOk();

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_authenticated_legacy_user_can_request_password_link(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'a@example.com',
            'guest' => false,
        ]);
        $user->forceFill([
            'password' => null,
            'legacy_imported_at' => now(),
        ])->save();

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/auth/account-legacy-password-link')
            ->assertOk()
            ->assertJsonStructure(['message']);

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_authenticated_non_legacy_user_gets_422_on_legacy_password_link(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'b@example.com',
            'guest' => false,
        ]);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/auth/account-legacy-password-link')
            ->assertUnprocessable();

        Notification::assertNothingSent();
    }

    public function test_reset_password_with_valid_token_updates_password(): void
    {
        $user = User::factory()->create([
            'email' => 'u@example.com',
            'guest' => false,
            'password' => 'password-secure-1',
        ]);

        $token = Password::broker()->createToken($user);

        $r = $this->from(config('app.url'))
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/auth/reset-password', [
                'email' => 'u@example.com',
                'token' => $token,
                'password' => 'new-password-secure-9',
                'password_confirmation' => 'new-password-secure-9',
            ]);

        $r->assertOk();
        $user->refresh();
        $this->assertTrue(Hash::check('new-password-secure-9', $user->password));
    }

    public function test_reset_password_rejects_invalid_token(): void
    {
        User::factory()->create([
            'email' => 'u@example.com',
            'guest' => false,
            'password' => 'password-secure-1',
        ]);

        $r = $this->from(config('app.url'))
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/auth/reset-password', [
                'email' => 'u@example.com',
                'token' => 'wrong-token-plain',
                'password' => 'new-password-secure-9',
                'password_confirmation' => 'new-password-secure-9',
            ]);

        $r->assertUnprocessable();
        $r->assertJsonValidationErrors(['token']);
    }

    public function test_forgot_password_throttled_after_many_requests(): void
    {
        Notification::fake();

        for ($i = 0; $i < 5; $i++) {
            $this->from(config('app.url'))
                ->withHeaders($this->statefulHeaders())
                ->postJson('/api/v1/auth/forgot-password', ['email' => 'a@example.com'])
                ->assertOk();
        }

        $this->from(config('app.url'))
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/auth/forgot-password', ['email' => 'a@example.com'])
            ->assertStatus(429);
    }
}
