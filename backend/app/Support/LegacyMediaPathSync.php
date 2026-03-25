<?php

namespace App\Support;

/**
 * Копіювання каталогів legacy→redpanda: локально на одному сервері або через rsync+SSH (user@host:/path).
 */
final class LegacyMediaPathSync
{
    /**
     * Віддалений транспорт: префікс як у rsync `user@host:/шлях`.
     */
    public static function sourceUsesSshTransport(string $source): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9._-]+@[^:]+:.+/s', trim($source));
    }

    /**
     * @return list<string>
     */
    public static function rsyncArgv(string $source, string $dest, bool $dryRun): array
    {
        $args = [
            'rsync',
            '-a',
            '-v',
            '--human-readable',
        ];

        $src = trim($source);
        if (self::sourceUsesSshTransport($src)) {
            $args[] = '-e';
            $args[] = 'ssh -o BatchMode=yes -o StrictHostKeyChecking=accept-new';
        } else {
            $src = self::normalizeLocalDirTrailingSlash($src);
        }

        $dst = trim($dest);
        $dst = self::normalizeLocalDirTrailingSlash($dst);

        if ($dryRun) {
            $args[] = '-n';
        }

        $args[] = $src;
        $args[] = $dst;

        return $args;
    }

    private static function normalizeLocalDirTrailingSlash(string $path): string
    {
        if ($path === '' || self::sourceUsesSshTransport($path)) {
            return $path;
        }

        if (! str_starts_with($path, '/')) {
            return $path;
        }

        if (is_dir($path) && ! str_ends_with($path, '/')) {
            return $path.'/';
        }

        return $path;
    }
}
