<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingApiTest extends TestCase
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

    public function test_landing_get_is_public_and_structured(): void
    {
        $this->getJson('/api/v1/landing')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'landing' => ['page_title', 'tagline', 'news_title', 'news_body', 'links'],
                    'registration' => ['registration_open', 'min_age', 'show_social_login_buttons'],
                    'users_online',
                    'auth0' => ['enabled', 'domain', 'client_id', 'audience'],
                ],
            ])
            ->assertJsonPath('data.auth0.enabled', false);
    }

    public function test_admin_patch_landing_visible_on_public_endpoint(): void
    {
        $admin = User::factory()->admin()->create();

        $this->from(config('app.url'))
            ->actingAs($admin, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/chat/settings', [
                'landing_settings' => [
                    'news_title' => 'Тестова новина',
                    'news_body' => 'Опис',
                    'links' => [['label' => 'Архів', 'url' => '/archive']],
                ],
            ])
            ->assertOk();

        $this->getJson('/api/v1/landing')
            ->assertOk()
            ->assertJsonPath('data.landing.news_title', 'Тестова новина')
            ->assertJsonPath('data.landing.links.0.url', '/archive');
    }

    public function test_admin_patch_rejects_unsafe_landing_url(): void
    {
        $admin = User::factory()->admin()->create();

        $this->from(config('app.url'))
            ->actingAs($admin, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/chat/settings', [
                'landing_settings' => [
                    'links' => [['label' => 'x', 'url' => 'javascript:alert(1)']],
                ],
            ])
            ->assertUnprocessable();
    }

    public function test_admin_can_patch_sound_on_every_post(): void
    {
        $admin = User::factory()->admin()->create();

        $this->from(config('app.url'))
            ->actingAs($admin, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/chat/settings', [
                'sound_on_every_post' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.sound_on_every_post', true);
    }
}
