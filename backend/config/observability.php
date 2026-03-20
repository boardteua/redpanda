<?php

return [

    /*
    |--------------------------------------------------------------------------
    | HTTP summary log (structured context)
    |--------------------------------------------------------------------------
    |
    | Якщо true — на кожен HTTP-запит пишеться один рядок `http.request.summary`
    | (разом із shareContext: request_id, path). Шумно для production; зручно
    | для демонстрації JSON-каналу structured.
    |
    */

    'log_http_summary' => env('LOG_HTTP_SUMMARY', false),

    /*
    |--------------------------------------------------------------------------
    | Health readiness: Redis probe
    |--------------------------------------------------------------------------
    |
    | Див. HealthController::ready(). Читати лише через config(), не через env() у коді.
    |
    */

    'health_check_redis' => env('HEALTH_CHECK_REDIS', false),

];
