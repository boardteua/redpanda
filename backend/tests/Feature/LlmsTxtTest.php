<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LlmsTxtTest extends TestCase
{
    use RefreshDatabase;

    public function test_llms_txt_returns_markdown_utf8(): void
    {
        $response = $this->get('/llms.txt');

        $response->assertOk();
        $contentType = $response->headers->get('Content-Type');
        $this->assertNotNull($contentType);
        $this->assertStringContainsString('text/markdown', $contentType);
        $this->assertStringContainsString('charset=utf-8', strtolower($contentType));
        $response->assertSee('Чат Рудої Панди', false);
        $response->assertSee('Chat v2', false);
        $response->assertSee('/api/v1/landing', false);
        $response->assertDontSee('__APP_URL__', false);
    }

    public function test_llms_txt_is_not_spa_fallback(): void
    {
        $this->get('/llms.txt')
            ->assertOk()
            ->assertDontSee('<div id="app"></div>', false);
    }

    public function test_public_openapi_yaml_is_served(): void
    {
        $response = $this->get('/docs/openapi.yaml');

        $response->assertOk();
        $contentType = $response->headers->get('Content-Type');
        $this->assertNotNull($contentType);
        $this->assertStringContainsString('yaml', $contentType);
        $response->assertSee('openapi:', false);
        $response->assertSee('redpanda Chat v2 API', false);
    }

    public function test_public_openapi_yaml_alternate_chat_v2_path(): void
    {
        $this->get('/docs/chat-v2/openapi.yaml')
            ->assertOk()
            ->assertHeaderContains('Content-Type', 'yaml')
            ->assertSee('openapi:', false);
    }

    public function test_public_doc_bundle_files_exist_on_disk(): void
    {
        $base = base_path('resources/public-monorepo-docs');
        $this->assertFileExists($base.'/chat-v2/openapi.yaml');
        $this->assertFileExists($base.'/chat-v2/AI-AGENT-FRIENDLY.md');
        $this->assertFileExists($base.'/project-specs/chat-v2-setup.md');
    }

    public function test_public_ai_agent_friendly_md_is_served(): void
    {
        $response = $this->get('/docs/chat-v2/AI-AGENT-FRIENDLY.md');

        $response->assertOk();
        $contentType = $response->headers->get('Content-Type');
        $this->assertNotNull($contentType);
        $this->assertStringContainsString('text/markdown', $contentType);
        $response->assertSee('Redpanda Chat v2', false);
        $response->assertSee('без виконання JavaScript SPA', false);
    }

    public function test_public_chat_v2_setup_md_is_served(): void
    {
        $response = $this->get('/docs/project-specs/chat-v2-setup.md');

        $response->assertOk();
        $contentType = $response->headers->get('Content-Type');
        $this->assertNotNull($contentType);
        $this->assertStringContainsString('text/markdown', $contentType);
        $response->assertSee('Chat v2', false);
        $response->assertSee('openapi.yaml', false);
    }
}
