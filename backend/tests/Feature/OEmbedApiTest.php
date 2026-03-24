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

    public function test_threads_post_uses_graph_threads_net_with_omitscript_and_maxwidth(): void
    {
        Http::fake([
            'https://graph.threads.net/v1.0/oembed*' => Http::response([
                'type' => 'rich',
                'version' => '1.0',
                'html' => '<blockquote class="text-post-media" data-text-post-permalink="https://www.threads.com/t/ABC"><a href="https://www.threads.com/t/ABC">View</a></blockquote><script>alert(1)</script>',
                'provider_name' => 'Threads',
                'width' => 658,
            ], 200),
        ]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $resource = 'https://www.threads.com/@example/post/ShortCodeOk12';
        $json = $this->getJson('/api/v1/oembed?url='.rawurlencode($resource))
            ->assertOk()
            ->json();

        $this->assertArrayHasKey('html', $json);
        $this->assertStringContainsString('text-post-media', (string) $json['html']);
        $this->assertStringNotContainsString('<script', strtolower((string) $json['html']));

        Http::assertSent(function ($request) use ($resource): bool {
            $u = $request->url();

            return str_starts_with($u, 'https://graph.threads.net/v1.0/oembed')
                && str_contains($u, 'omitscript=true')
                && str_contains($u, 'maxwidth=')
                && str_contains($u, 'url='.rawurlencode($resource));
        });
    }

    public function test_tiktok_short_url_hits_tiktok_oembed_endpoint(): void
    {
        Http::fake([
            'https://www.tiktok.com/oembed*' => Http::response([
                'type' => 'video',
                'version' => '1.0',
                'title' => 'Tik',
                'html' => '<iframe src="https://www.tiktok.com/embed/v1/abc" width="200" height="200"></iframe>',
            ], 200),
        ]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $short = 'https://vm.tiktok.com/ZM123abc/';
        $this->getJson('/api/v1/oembed?url='.rawurlencode($short))
            ->assertOk()
            ->assertJsonPath('title', 'Tik');

        Http::assertSent(function ($request) use ($short): bool {
            $u = $request->url();

            return str_starts_with($u, 'https://www.tiktok.com/oembed')
                && str_contains($u, 'url='.rawurlencode($short));
        });
    }
}
