<?php

namespace App\Services\Auth0;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use stdClass;
use Throwable;
use UnexpectedValueException;

class Auth0AccessTokenVerifier
{
    /**
     * Перевіряє підпис (JWKS), iss, aud; опційно azp.
     *
     * @throws UnexpectedValueException
     */
    public function verify(string $jwt): stdClass
    {
        $domain = $this->requireDomain();
        $audience = $this->requireAudience();
        $issuer = 'https://'.rtrim($domain, '/').'/';

        JWT::$leeway = 60;

        $jwks = $this->fetchJwks($domain);
        $keys = JWK::parseKeySet($jwks);

        try {
            $decoded = JWT::decode($jwt, $keys);
        } catch (Throwable $e) {
            throw new UnexpectedValueException('Invalid Auth0 access token.', 0, $e);
        }

        if (($decoded->iss ?? null) !== $issuer) {
            throw new UnexpectedValueException('Invalid token issuer.');
        }

        if (! $this->audienceMatches($decoded->aud ?? null, $audience)) {
            throw new UnexpectedValueException('Invalid token audience.');
        }

        $spaClientId = config('auth0.spa_client_id');
        if (is_string($spaClientId) && $spaClientId !== '') {
            $azp = $decoded->azp ?? null;
            if ($azp !== $spaClientId) {
                throw new UnexpectedValueException('Invalid token authorized party.');
            }
        }

        if (empty($decoded->sub) || ! is_string($decoded->sub)) {
            throw new UnexpectedValueException('Token missing subject.');
        }

        return $decoded;
    }

    private function requireDomain(): string
    {
        $d = config('auth0.domain');
        if (! is_string($d) || trim($d) === '') {
            throw new UnexpectedValueException('Auth0 domain is not configured.');
        }

        return trim($d);
    }

    private function requireAudience(): string
    {
        $a = config('auth0.audience');
        if (! is_string($a) || trim($a) === '') {
            throw new UnexpectedValueException('Auth0 audience is not configured.');
        }

        return trim($a);
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchJwks(string $domain): array
    {
        $ttl = max(60, (int) config('auth0.jwks_cache_ttl', 3600));
        $cacheKey = 'auth0.jwks.'.hash('sha256', $domain);

        return Cache::remember($cacheKey, $ttl, function () use ($domain) {
            $url = 'https://'.rtrim($domain, '/').'/.well-known/jwks.json';
            $response = Http::timeout(12)->acceptJson()->get($url);
            if (! $response->successful()) {
                throw new UnexpectedValueException('Could not fetch Auth0 JWKS.');
            }

            /** @var array<string, mixed> $json */
            $json = $response->json();

            return $json;
        });
    }

    private function audienceMatches(mixed $audClaim, string $expected): bool
    {
        if (is_string($audClaim)) {
            return $audClaim === $expected;
        }
        if (is_array($audClaim)) {
            return in_array($expected, $audClaim, true);
        }

        return false;
    }
}
