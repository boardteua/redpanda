<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class RobotsTxtController extends Controller
{
    public function __invoke(): SymfonyResponse
    {
        $base = rtrim((string) config('app.url'), '/');
        $sitemapUrl = $base.'/sitemap.xml';

        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /api/',
            'Disallow: /sanctum/',
            'Disallow: /broadcasting/',
            '',
            '# Major crawlers and AI agents: same rules as * (nothing here blocks them).',
            'User-agent: Googlebot',
            'Allow: /',
            '',
            'User-agent: Bingbot',
            'Allow: /',
            '',
            'User-agent: GPTBot',
            'Allow: /',
            '',
            'User-agent: ChatGPT-User',
            'Allow: /',
            '',
            'User-agent: PerplexityBot',
            'Allow: /',
            '',
            'User-agent: ClaudeBot',
            'Allow: /',
            '',
            'User-agent: anthropic-ai',
            'Allow: /',
            '',
            'Sitemap: '.$sitemapUrl,
            '',
        ];

        $body = implode("\n", $lines);

        return response($body, Response::HTTP_OK, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
