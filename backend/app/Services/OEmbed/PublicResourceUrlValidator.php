<?php

namespace App\Services\OEmbed;

use App\Services\OEmbed\Exceptions\OEmbedUnsafeUrlException;

/**
 * Rejects obvious SSRF targets in user-supplied resource URLs (loopback, RFC1918, metadata IP, link-local).
 */
class PublicResourceUrlValidator
{
    /**
     * @throws OEmbedUnsafeUrlException
     */
    public function assertPublicHttpUrl(string $url): void
    {
        if (strlen($url) > 2048) {
            throw new OEmbedUnsafeUrlException('URL too long.');
        }

        $parts = parse_url($url);
        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            throw new OEmbedUnsafeUrlException('Invalid URL.');
        }

        $scheme = strtolower((string) $parts['scheme']);
        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new OEmbedUnsafeUrlException('Only http and https are allowed.');
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new OEmbedUnsafeUrlException('URL must not contain credentials.');
        }

        $host = strtolower((string) $parts['host']);
        if ($host === '' || $host === 'localhost') {
            throw new OEmbedUnsafeUrlException('Host is not allowed.');
        }

        if (app()->environment('testing')) {
            $relaxed = config('oembed.testing_skip_dns_hosts', []);
            if (is_array($relaxed) && $relaxed !== [] && in_array($host, $relaxed, true)) {
                return;
            }
        }

        $this->assertHostResolvesToPublicIps($host);
    }

    /**
     * @throws OEmbedUnsafeUrlException
     */
    private function assertHostResolvesToPublicIps(string $host): void
    {
        $ip = filter_var($host, FILTER_VALIDATE_IP);
        if ($ip !== false) {
            if (! $this->isPublicIp($ip)) {
                throw new OEmbedUnsafeUrlException('Address is not a public endpoint.');
            }

            return;
        }

        $ips = $this->resolveHostIps($host);
        if ($ips === []) {
            throw new OEmbedUnsafeUrlException('Host could not be resolved.');
        }

        foreach ($ips as $resolved) {
            if (! $this->isPublicIp($resolved)) {
                throw new OEmbedUnsafeUrlException('Host resolves to a non-public address.');
            }
        }
    }

    /**
     * @return list<string>
     */
    private function resolveHostIps(string $host): array
    {
        $ips = [];

        $a4 = @gethostbynamel($host);
        if (is_string($a4) && $a4 !== '') {
            $ips[] = $a4;
        }

        $records = @dns_get_record($host, DNS_A | DNS_AAAA);
        if (is_array($records)) {
            foreach ($records as $row) {
                if (isset($row['ip']) && is_string($row['ip'])) {
                    $ips[] = $row['ip'];
                }
                if (isset($row['ipv6']) && is_string($row['ipv6'])) {
                    $ips[] = $row['ipv6'];
                }
            }
        }

        return array_values(array_unique($ips));
    }

    private function isPublicIp(string $ip): bool
    {
        $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | $flags) !== false;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | $flags) !== false;
        }

        return false;
    }
}
