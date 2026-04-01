<?php

/**
 * Роутер для `php artisan serve` (замість дефолтного з фреймворку).
 *
 * PWA: service worker лежить у `/build/sw.js`. Браузер за замовчуванням дозволяє scope лише
 * підкаталогу скрипта (`/build/`), а реєстрація йде з scope `/` — без заголовка
 * `Service-Worker-Allowed: /` отримуєш помилку в консолі.
 *
 * Вбудований PHP-сервер віддає існуючі файли з `public/` без Laravel → заголовка не було.
 * Тут перехоплюємо лише SW і додаємо заголовок. У проді налаштуй nginx (або аналог) так само.
 */
$publicPath = getcwd();

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

$swPath = $publicPath.'/build/sw.js';
if ($uri === '/build/sw.js' && is_file($swPath)) {
    header('Content-Type: application/javascript; charset=utf-8');
    header('Service-Worker-Allowed: /');

    readfile($swPath);

    return true;
}

// Далі — стандартна логіка Laravel (`vendor/laravel/framework/.../resources/server.php`).
if ($uri !== '/' && file_exists($publicPath.$uri)) {
    return false;
}

$formattedDateTime = date('D M j H:i:s Y');

$requestMethod = $_SERVER['REQUEST_METHOD'];
$remoteAddress = $_SERVER['REMOTE_ADDR'].':'.($_SERVER['REMOTE_PORT'] ?? '');

file_put_contents('php://stdout', "[$formattedDateTime] $remoteAddress [$requestMethod] URI: $uri\n");

require_once $publicPath.'/index.php';
