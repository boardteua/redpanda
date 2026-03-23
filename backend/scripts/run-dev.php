<?php

declare(strict_types=1);

/**
 * Обгортка для `composer dev`: виставляє PHP_INI_SCAN_DIR на backend/php-ini.d,
 * щоб `php artisan serve` → вбудований PHP-сервер бачив upload_max_filesize/post_max_size (128M).
 * Без цього часто лишаються дефолти 2M — API повертає низький max_chat_image_upload_bytes.
 */
$backendRoot = dirname(__DIR__);
chdir($backendRoot);

$iniDir = $backendRoot.'/php-ini.d';
$existing = getenv('PHP_INI_SCAN_DIR');
$scan = ($existing !== false && $existing !== '')
    ? $iniDir.PATH_SEPARATOR.$existing
    : $iniDir;
putenv('PHP_INI_SCAN_DIR='.$scan);

$cmd = 'npx concurrently -c "#93c5fd,#c4b5fd,#fb7185,#fdba74" '
    .'"php artisan serve" '
    .'"php artisan queue:listen --tries=1 --timeout=0" '
    .'"php artisan pail --timeout=0" '
    .'"npm run dev" '
    .'--names=server,queue,logs,vite --kill-others';

passthru($cmd, $exitCode);
exit($exitCode ?? 1);
