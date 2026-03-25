<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoPublicRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_robots_txt_is_plain_text_and_lists_sitemap(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('User-agent:', false)
            ->assertSee('Sitemap: http://localhost/sitemap.xml', false)
            ->assertSee('GPTBot', false)
            ->assertSee('Allow: /', false);
    }

    public function test_sitemap_xml_lists_home_and_public_docs(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', false)
            ->assertSee('<loc>http://localhost/</loc>', false)
            ->assertSee('<loc>http://localhost/llms.txt</loc>', false)
            ->assertSee('<loc>http://localhost/docs/openapi.yaml</loc>', false);
    }

    public function test_home_includes_meta_og_canonical_and_json_ld(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('<title>', $html);
        $this->assertStringContainsString('meta name="description"', $html);
        $this->assertStringContainsString('link rel="canonical"', $html);
        $this->assertStringContainsString('property="og:title"', $html);
        $this->assertStringContainsString('property="og:description"', $html);
        $this->assertStringContainsString('property="og:url"', $html);
        $this->assertStringContainsString('property="og:image"', $html);
        $this->assertStringContainsString('property="og:type"', $html);
        $this->assertStringContainsString('name="twitter:card"', $html);
        $this->assertStringContainsString('summary_large_image', $html);
        $this->assertStringContainsString('application/ld+json', $html);
        $this->assertStringContainsString('"@type":"WebSite"', $html);
        $this->assertStringContainsString('"@type":"Organization"', $html);
        $this->assertStringContainsString('http://localhost/brand/og-default.png', $html);
    }
}
