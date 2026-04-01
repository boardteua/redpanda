<?php

namespace App\Support;

use Illuminate\Database\QueryException;

final class DatabaseDuplicateKey
{
    public static function is(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? '');
        if ($sqlState === '23505') {
            return true;
        }

        $driverCode = $exception->errorInfo[1] ?? null;

        return in_array($driverCode, [1062, 19, '1062', '19'], true);
    }
}
