<?php

namespace App\Support;

/**
 * Канонічний список кодів ISO 3166-1 alpha-2 з {@see resource_path('data/iso3166-alpha2-uk.json')}.
 */
final class Iso3166Alpha2Uk
{
    /** @var list<string>|null */
    private static ?array $codes = null;

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        if (self::$codes !== null) {
            return self::$codes;
        }

        $path = resource_path('data/iso3166-alpha2-uk.json');
        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new \RuntimeException('Missing ISO 3166 data: '.$path);
        }

        /** @var list<array{code: string, labelUk: string}> $items */
        $items = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        self::$codes = array_values(array_map(
            static fn (array $row): string => $row['code'],
            $items
        ));

        return self::$codes;
    }
}
