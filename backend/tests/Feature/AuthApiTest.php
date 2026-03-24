<?php

namespace Tests\Feature;

use App\Mail\WelcomeRegisteredUserMail;
use App\Models\ChatSetting;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthApiTest extends TestCase
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

    public function test_register_login_user_and_logout_flow(): void
    {
        Mail::fake();

        $register = $this->from(config('app.url'))
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/auth/register', [
                'user_name' => 'TestUser',
                'email' => 'test@example.com',
                'password' => 'password-secure-1',
                'password_confirmation' => 'password-secure-1',
            ]);

        $register->assertCreated()
            ->assertJsonPath('data.user_name', 'TestUser')
            ->assertJsonPath('data.guest', false);

        Mail::assertQueued(WelcomeRegisteredUserMail::class);

        $this->assertAuthenticatedAs(User::query()->where('user_name', 'TestUser')->first(), 'web');

        $this->from(config('app.url'))
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/auth/user')
            ->assertOk()
            ->assertJsonPath('data.user_name', 'TestUser');

        $this->from(config('app.url'))
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/auth/logout')
            ->assertNoContent();

        $this->assertGuest('web');

        $this->from(config('app.url'))
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/auth/login', [
                'user_name' => 'TestUser',
                'password' => 'password-secure-1',
            ])
            ->assertOk()
            ->assertJsonPath('data.user_name', 'TestUser');
    }

    public function test_guest_creates_user_and_registered_member_gate(): void
    {
        $response = $this->from(config('app.url'))
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/auth/guest', []);

        $response->assertCreated();
        $user = User::query()->where('guest', true)->first();
        $this->assertNotNull($user);
        $this->assertSame($user->user_name, $response->json('data.user_name'));

        $this->assertFalse(Gate::forUser($user)->allows('actAsRegisteredMember', $user));

        $registered = User::factory()->create(['guest' => false]);
        $this->assertTrue(Gate::forUser($registered)->allows('actAsRegisteredMember', $registered));
    }

    public function test_api_logs_out_disabled_account_on_next_request(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['account_disabled_at' => now()])->save();

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/auth/user')
            ->assertForbidden();

        $this->assertGuest('web');
    }

    public function test_login_rejects_disabled_account(): void
    {
        User::factory()->create([
            'user_name' => 'disabled_user',
            'email' => 'disabled@example.com',
            'password' => Hash::make('password-secure-1'),
            'guest' => false,
            'account_disabled_at' => now(),
        ]);

        $this->from(config('app.url'))
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/auth/login', [
                'user_name' => 'disabled_user',
                'password' => 'password-secure-1',
            ])
            ->assertUnprocessable();
    }

    public function test_guest_cannot_use_password_login(): void
    {
        $guest = User::factory()->guest()->create([
            'user_name' => 'anon1',
            'password' => null,
        ]);

        $this->from(config('app.url'))
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/auth/login', [
                'user_name' => 'anon1',
                'password' => 'any-password-here',
            ])
            ->assertUnprocessable();
    }

    public function test_login_rejects_password_longer_than_1024_chars(): void
    {
        $long = str_repeat('a', 1025);

        $this->from(config('app.url'))
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/auth/login', [
                'user_name' => 'anyone',
                'password' => $long,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_login_throttle_returns_429(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->from(config('app.url'))
                ->withHeaders($this->statefulHeaders())
                ->postJson('/api/v1/auth/login', [
                    'user_name' => 'nobody',
                    'password' => 'wrong',
                ])
                ->assertUnprocessable();
        }

        $this->from(config('app.url'))
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/auth/login', [
                'user_name' => 'nobody',
                'password' => 'wrong',
            ])
            ->assertStatus(429);
    }

    public function test_register_throttle_returns_429(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $n = "u{$i}";
            $this->from(config('app.url'))
                ->withHeaders($this->statefulHeaders())
                ->postJson('/api/v1/auth/register', [
                    'user_name' => $n,
                    'email' => "{$n}@example.com",
                    'password' => 'password-secure-1',
                    'password_confirmation' => 'password-secure-1',
                ])
                ->assertCreated();
        }

        $this->from(config('app.url'))
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/auth/register', [
                'user_name' => 'u5',
                'email' => 'u5@example.com',
                'password' => 'password-secure-1',
                'password_confirmation' => 'password-secure-1',
            ])
            ->assertStatus(429);
    }

    public function test_register_returns_403_when_registration_closed(): void
    {
        $row = ChatSetting::current();
        $flags = is_array($row->registration_flags) ? $row->registration_flags : [];
        $row->registration_flags = array_merge($flags, ['registration_open' => false]);
        $row->save();

        $this->from(config('app.url'))
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/auth/register', [
                'user_name' => 'ClosedReg',
                'email' => 'closed@example.com',
                'password' => 'password-secure-1',
                'password_confirmation' => 'password-secure-1',
            ])
            ->assertForbidden()
            ->assertJsonPath('message', 'Реєстрацію тимчасово вимкнено адміністратором.');

        $this->assertDatabaseMissing('users', ['user_name' => 'ClosedReg']);
    }

    public function test_register_rejects_non_empty_honeypot(): void
    {
        $this->from(config('app.url'))
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/auth/register', [
                'user_name' => 'HpUser',
                'email' => 'hp@example.com',
                'password' => 'password-secure-1',
                'password_confirmation' => 'password-secure-1',
                'department' => 'Sales',
            ])
            ->assertUnprocessable();

        $this->assertDatabaseMissing('users', ['user_name' => 'HpUser']);
    }
}
