<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class SitemapXmlController extends Controller
{
    public function __invoke(): SymfonyResponse
    {
        $base = rtrim((string) config('app.url'), '/');
        $paths = array_merge([''], (array) config('seo.sitemap_paths', []));

        $urls = [];
        foreach ($paths as $p) {
            $p = is_string($p) ? trim($p, '/') : '';
            $loc = $p === '' ? $base.'/' : $base.'/'.$p;
            $urls[] = $loc;
        }
        $urls = array_values(array_unique($urls));

        $chunks = ['<?xml version="1.0" encoding="UTF-8"?>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];
        foreach ($urls as $loc) {
            $chunks[] = '  <url>';
            $chunks[] = '    <loc>'.htmlspecialchars($loc, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</loc>';
            $chunks[] = '    <changefreq>weekly</changefreq>';
            $chunks[] = '    <priority>'.($loc === $base.'/' ? '1.0' : '0.6').'</priority>';
            $chunks[] = '  </url>';
        }
        $chunks[] = '</urlset>';
        $xml = implode("\n", $chunks)."\n";

        return response($xml, Response::HTTP_OK, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
