<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeProfileApiTest extends TestCase
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

    public function test_guest_forbidden_on_me_profile_and_account(): void
    {
        $guest = User::factory()->guest()->create();

        $this->from(config('app.url'))
            ->actingAs($guest, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/me/profile')
            ->assertForbidden();

        $this->from(config('app.url'))
            ->actingAs($guest, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/me/profile', ['profile' => ['country' => 'UA']])
            ->assertForbidden();

        $this->from(config('app.url'))
            ->actingAs($guest, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/me/account', [
                'current_password' => 'x',
                'password' => 'new-password-secure-9',
                'password_confirmation' => 'new-password-secure-9',
            ])
            ->assertForbidden();
    }

    public function test_registered_user_gets_profile_payload_on_auth_user_and_me_profile(): void
    {
        $user = User::factory()->create([
            'profile_country' => 'UA',
            'profile_about' => 'Hello',
        ]);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/auth/user')
            ->assertOk()
            ->assertJsonPath('data.profile.country', 'UA')
            ->assertJsonPath('data.profile.about', 'Hello')
            ->assertJsonPath('data.notification_sound_prefs.volume_percent', 80);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/me/profile')
            ->assertOk()
            ->assertJsonPath('data.profile.country', 'UA');
    }

    public function test_patch_me_profile_persists(): void
    {
        $user = User::factory()->create();

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/me/profile', [
                'profile' => [
                    'country' => 'PL',
                    'region' => 'Małopolska',
                    'age' => 30,
                    'sex' => 'prefer_not',
                    'country_hidden' => true,
                    'occupation' => 'Dev',
                    'about' => 'About me',
                ],
                'social_links' => [
                    'telegram' => '@nick',
                    'website' => 'https://example.org',
                ],
                'notification_sound_prefs' => [
                    'public_messages' => false,
                    'volume_percent' => 42,
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.profile.country', 'PL')
            ->assertJsonPath('data.social_links.telegram', '@nick')
            ->assertJsonPath('data.notification_sound_prefs.public_messages', false)
            ->assertJsonPath('data.notification_sound_prefs.volume_percent', 42);

        $user->refresh();
        $this->assertSame('PL', $user->profile_country);
        $this->assertTrue($user->profile_country_hidden);
        $this->assertSame(42, $user->notification_sound_prefs['volume_percent']);
    }

    public function test_patch_me_account_requires_change_and_valid_current_password(): void
    {
        $user = User::factory()->create([
            'password' => 'old-password-secure-1',
        ]);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/me/account', [
                'current_password' => 'wrong-password',
                'password' => 'new-password-secure-9',
                'password_confirmation' => 'new-password-secure-9',
            ])
            ->assertUnprocessable();

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/me/account', [
                'current_password' => 'old-password-secure-1',
                'email' => 'newmail@example.com',
            ])
            ->assertOk()
            ->assertJsonPath('data.email', 'newmail@example.com');

        $user->refresh();
        $this->assertSame('newmail@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }
}
