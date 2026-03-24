<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OEmbedApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        Cache::flush();
    }

    public function test_requires_authentication(): void
    {
        Http::fake();

        $this->getJson('/api/v1/oembed?url='.rawurlencode('https://www.youtube.com/watch?v=1'))
            ->assertUnauthorized();
    }

    public function test_successful_fetch_uses_provider_endpoint_not_resource_host(): void
    {
        Http::fake([
            'https://www.youtube.com/oembed*' => Http::response([
                'type' => 'video',
                'version' => '1.0',
                'title' => 'T',
                'html' => '<iframe src="https://www.youtube.com/embed/abc" width="200" height="113"></iframe>',
            ], 200),
        ]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $resource = 'https://www.youtube.com/watch?v=qa_oembed_ok';
        $this->getJson('/api/v1/oembed?url='.rawurlencode($resource))
            ->assertOk()
            ->assertJsonPath('type', 'video')
            ->assertJsonPath('title', 'T');

        Http::assertSent(function ($request) use ($resource): bool {
            $u = $request->url();

            return str_starts_with($u, 'https://www.youtube.com/oembed')
                && str_contains($u, 'format=json')
                && str_contains($u, 'url='.rawurlencode($resource))
                && ! str_contains($u, 'evil.example');
        });
    }

    public function test_script_in_upstream_html_is_not_returned(): void
    {
        Http::fake([
            'https://www.youtube.com/oembed*' => Http::response([
                'type' => 'video',
                'title' => 'X',
                'html' => '<script>alert(1)</script><iframe src="https://www.youtube.com/embed/abc"></iframe>',
            ], 200),
        ]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $json = $this->getJson('/api/v1/oembed?url='.rawurlencode('https://www.youtube.com/watch?v=strip_script'))
            ->assertOk()
            ->json();

        $this->assertArrayHasKey('html', $json);
        $this->assertStringNotContainsString('<script', strtolower((string) $json['html']));
        $this->assertStringContainsString('iframe', $json['html']);
    }

    public function test_unknown_resource_url_returns_422(): void
    {
        Http::fake();

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/oembed?url='.rawurlencode('https://example.com/no-oembed-here'))
            ->assertStatus(422)
            ->assertJsonStructure(['message']);

        Http::assertNothingSent();
    }

    public function test_loopback_url_returns_422_without_upstream_call(): void
    {
        Http::fake();

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/oembed?url='.rawurlencode('http://127.0.0.1:8080/internal'))
            ->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_upstream_error_returns_502(): void
    {
        Http::fake([
            'https://www.youtube.com/oembed*' => Http::response(['error' => true], 503),
        ]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/oembed?url='.rawurlencode('https://www.youtube.com/watch?v=upstream_fail'))
            ->assertStatus(502)
            ->assertJsonStructure(['message']);
    }
}
