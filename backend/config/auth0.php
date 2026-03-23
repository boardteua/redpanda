<?php

/**
 * Auth0 (T76): перевірка access token (JWKS) і провізіонінг користувачів.
 * Секрети не зберігати в репо — лише .env (див. .env.example).
 */
return [
    'enabled' => filter_var(env('AUTH0_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

    /** Напр. tenant.eu.auth0.com (без https://) */
    'domain' => env('AUTH0_DOMAIN'),

    /** Identifier API (Resource Server) — має збігатися з audience у SPA */
    'audience' => env('AUTH0_AUDIENCE'),

    /** Public SPA Application Client ID (для опційної перевірки claim `azp`) */
    'spa_client_id' => env('AUTH0_SPA_CLIENT_ID'),

    /** Кеш JWKS (секунди) */
    'jwks_cache_ttl' => (int) env('AUTH0_JWKS_CACHE_TTL', 3600),
];
