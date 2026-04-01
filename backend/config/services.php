<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'web_push' => [
        'vapid' => [
            'subject' => env('WEB_PUSH_VAPID_SUBJECT', env('APP_URL')),
            'public_key' => env('WEB_PUSH_VAPID_PUBLIC_KEY'),
            'private_key' => env('WEB_PUSH_VAPID_PRIVATE_KEY'),
        ],
        'ttl' => (int) env('WEB_PUSH_TTL', 300),
        'urgency' => env('WEB_PUSH_URGENCY', 'normal'),
        'batch_size' => (int) env('WEB_PUSH_BATCH_SIZE', 200),
        'public_avatar_url_ttl_seconds' => (int) env('WEB_PUSH_PUBLIC_AVATAR_TTL_SECONDS', 604800),
    ],

    'proxycheck' => [
        'enabled' => (bool) env('PROXYCHECK_ENABLED', false),
        'key' => env('PROXYCHECK_API_KEY'),
        'timeout_ms' => (int) env('PROXYCHECK_TIMEOUT_MS', 1500),
        'cache_ttl_seconds' => (int) env('PROXYCHECK_CACHE_TTL_SECONDS', 600),
        'deny_if_proxy_or_vpn' => (bool) env('PROXYCHECK_DENY_IF_PROXY_OR_VPN', true),
        'deny_risk_threshold' => (int) env('PROXYCHECK_DENY_RISK_THRESHOLD', 67),
    ],

    'gemini' => [
        'enabled' => (bool) env('GEMINI_ENABLED', false),
        'api_key' => env('GEMINI_API_KEY'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com'),
        'api_version' => env('GEMINI_API_VERSION', 'v1beta'),
        'timeout_ms' => (int) env('GEMINI_TIMEOUT_MS', 8000),
        'default_model' => env('GEMINI_DEFAULT_MODEL', 'gemini-2.5-flash'),
        // T180: маршрутизація tier-ів (порожній model_flash_lite → fallback на model_flash)
        'model_flash_lite' => env('GEMINI_MODEL_FLASH_LITE', ''),
        'model_flash' => env('GEMINI_MODEL_FLASH', ''),
        'model_pro' => env('GEMINI_MODEL_PRO', ''),
        'model_image' => env('GEMINI_MODEL_IMAGE', ''),
        // Опційно для Pro: thinkingBudget (128–8192) або thinkingLevel (Gemini 3.x), див. ai.google.dev thinking-mode
        'pro_thinking_budget' => (int) env('GEMINI_PRO_THINKING_BUDGET', 0),
        'pro_thinking_level' => env('GEMINI_PRO_THINKING_LEVEL', ''),
    ],

];
