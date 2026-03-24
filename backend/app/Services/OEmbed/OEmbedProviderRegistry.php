<?php

namespace App\Services\OEmbed;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class OEmbedProviderRegistry
{
    /**
     * @return array{endpoint_url: string, provider_name: string}|null
     */
    public function findEndpointForResourceUrl(string $resourceUrl): ?array
    {
        $normalized = $this->normalizeForSchemeMatch($resourceUrl);
        $providers = $this->loadProviders();

        foreach ($providers as $provider) {
            $name = (string) ($provider['provider_name'] ?? '');
            foreach ($provider['endpoints'] ?? [] as $endpoint) {
                $endpointUrl = (string) ($endpoint['url'] ?? '');
                if ($endpointUrl === '') {
                    continue;
                }
                $schemes = $endpoint['schemes'] ?? null;
                if (! is_array($schemes) || $schemes === []) {
                    continue;
                }
                foreach ($schemes as $scheme) {
                    if (! is_string($scheme) || $scheme === '') {
                        continue;
                    }
                    $pattern = $this->schemePatternToRegex($scheme);
                    if (@preg_match($pattern, $normalized) === 1) {
                        return [
                            'endpoint_url' => $endpointUrl,
                            'provider_name' => $name !== '' ? $name : 'unknown',
                        ];
                    }
                }
            }
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function loadProviders(): array
    {
        $path = config('oembed.providers_path', '');
        if (! is_string($path) || $path === '' || ! File::isFile($path)) {
            return [];
        }

        $mtime = (int) (@filemtime($path) ?: 0);

        return Cache::remember("oembed:providers:v1:{$mtime}", 3600, function () use ($path): array {
            $raw = File::get($path);
            $decoded = json_decode($raw, true);
            if (! is_array($decoded)) {
                return [];
            }

            return $decoded;
        });
    }

    private function normalizeForSchemeMatch(string $url): string
    {
        $p = parse_url($url);
        if ($p === false) {
            return $url;
        }

        $scheme = isset($p['scheme']) ? strtolower((string) $p['scheme']) : '';
        $host = isset($p['host']) ? strtolower((string) $p['host']) : '';
        $port = isset($p['port']) ? ':'.$p['port'] : '';
        $path = $p['path'] ?? '';
        $query = isset($p['query']) ? '?'.$p['query'] : '';
        $fragment = isset($p['fragment']) ? '#'.$p['fragment'] : '';

        if ($scheme === '' || $host === '') {
            return $url;
        }

        return $scheme.'://'.$host.$port.$path.$query.$fragment;
    }

    private function schemePatternToRegex(string $scheme): string
    {
        $out = '';
        $len = strlen($scheme);
        for ($i = 0; $i < $len; $i++) {
            if ($scheme[$i] === '*') {
                $out .= '.*';
            } else {
                $out .= preg_quote($scheme[$i], '/');
            }
        }

        return '/^'.$out.'$/i';
    }
}
