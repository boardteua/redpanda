<?php

namespace App\Services\OEmbed;

use App\Services\OEmbed\Exceptions\OEmbedNoProviderException;
use App\Services\OEmbed\Exceptions\OEmbedUnsafeUrlException;
use App\Services\OEmbed\Exceptions\OEmbedUpstreamException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OEmbedProxyService
{
    public function __construct(
        private readonly PublicResourceUrlValidator $urlValidator,
        private readonly OEmbedProviderRegistry $registry,
        private readonly OEmbedHtmlSanitizer $htmlSanitizer,
    ) {}

    /**
     * @return array<string, mixed>
     *
     * @throws OEmbedNoProviderException
     * @throws OEmbedUnsafeUrlException
     * @throws OEmbedUpstreamException
     */
    public function fetch(string $resourceUrl, ?int $maxwidth, ?int $maxheight): array
    {
        $this->urlValidator->assertPublicHttpUrl($resourceUrl);

        $endpoint = $this->registry->findEndpointForResourceUrl($resourceUrl);
        if ($endpoint === null) {
            throw new OEmbedNoProviderException('No oEmbed provider for this URL.');
        }

        $this->urlValidator->assertPublicHttpUrl($endpoint['endpoint_url']);

        $cacheKey = $this->cacheKey($resourceUrl, $maxwidth, $maxheight);
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $payload = $this->requestProvider($resourceUrl, $maxwidth, $maxheight, $endpoint['endpoint_url']);
        $allowedHosts = (array) config('oembed.allowed_iframe_hosts', []);
        $html = isset($payload['html']) && is_string($payload['html'])
            ? $this->htmlSanitizer->sanitizeIframeHtml($payload['html'], $allowedHosts)
            : null;

        $shaped = $this->shapeResponse($payload, $html);
        $ttl = $this->resolveTtlFromPayload($payload);
        Cache::put($cacheKey, $shaped, $ttl);

        return $shaped;
    }

    /**
     * @return array<string, mixed>
     */
    private function requestProvider(string $resourceUrl, ?int $maxwidth, ?int $maxheight, string $endpointUrl): array
    {
        $query = array_filter([
            'url' => $resourceUrl,
            'format' => 'json',
            'maxwidth' => $maxwidth,
            'maxheight' => $maxheight,
        ], fn (mixed $v): bool => $v !== null && $v !== '');

        $sep = str_contains($endpointUrl, '?') ? '&' : '?';
        $requestUrl = $endpointUrl.$sep.http_build_query($query);

        $timeout = (int) config('oembed.timeout_seconds', 8);
        $maxBytes = (int) config('oembed.max_response_bytes', 524_288);
        $allowRedirects = (bool) config('oembed.allow_redirects', false);

        try {
            $response = Http::timeout($timeout)
                ->withOptions([
                    'allow_redirects' => $allowRedirects,
                ])
                ->acceptJson()
                ->get($requestUrl);
        } catch (ConnectionException) {
            throw new OEmbedUpstreamException('Provider unreachable.');
        } catch (Throwable $e) {
            Log::warning('oembed_upstream_error', [
                'type' => $e::class,
            ]);

            throw new OEmbedUpstreamException('Provider error.');
        }

        if (! $response->successful()) {
            throw new OEmbedUpstreamException('Provider returned an error.');
        }

        $raw = $response->body();
        if (strlen($raw) > $maxBytes) {
            throw new OEmbedUpstreamException('Provider response too large.');
        }

        $payload = json_decode($raw, true);
        if (! is_array($payload)) {
            throw new OEmbedUpstreamException('Invalid provider response.');
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveTtlFromPayload(array $payload): int
    {
        $default = $this->defaultTtl();
        $max = (int) config('oembed.max_cache_ttl_seconds', 86400);
        $age = $payload['cache_age'] ?? null;
        if (! is_numeric($age)) {
            return $default;
        }

        $age = (int) $age;
        if ($age <= 0) {
            return $default;
        }

        return min($max, max(60, $age));
    }

    private function defaultTtl(): int
    {
        return (int) config('oembed.default_cache_ttl_seconds', 3600);
    }

    private function cacheKey(string $resourceUrl, ?int $maxwidth, ?int $maxheight): string
    {
        $norm = strtolower(trim($resourceUrl));
        $fingerprint = hash('sha256', $norm.'|'.($maxwidth ?? '').'|'.($maxheight ?? ''));

        return 'oembed:v1:'.$fingerprint;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function shapeResponse(array $payload, ?string $html): array
    {
        $keys = [
            'type',
            'version',
            'title',
            'author_name',
            'author_url',
            'provider_name',
            'provider_url',
            'cache_age',
            'thumbnail_url',
            'thumbnail_width',
            'thumbnail_height',
            'width',
            'height',
        ];

        $out = [];
        foreach ($keys as $k) {
            if (! array_key_exists($k, $payload)) {
                continue;
            }
            $v = $payload[$k];
            if (is_string($v) || is_int($v) || is_float($v) || is_bool($v) || $v === null) {
                $out[$k] = $v;
            }
        }

        if ($html !== null) {
            $out['html'] = $html;
        }

        return $out;
    }
}
