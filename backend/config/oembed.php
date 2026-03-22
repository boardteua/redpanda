<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Provider registry (iamcal / npm oembed-providers)
    |--------------------------------------------------------------------------
    |
    | Versioned copy under backend/data/. See docs/chat-v2/T55-oembed.md.
    |
    */
    'providers_path' => base_path('data/oembed-providers.json'),

    'default_cache_ttl_seconds' => (int) env('OEMBED_CACHE_TTL', 3600),

    'max_cache_ttl_seconds' => (int) env('OEMBED_CACHE_TTL_MAX', 86400),

    /*
    |--------------------------------------------------------------------------
    | Upstream fetch
    |--------------------------------------------------------------------------
    */
    'timeout_seconds' => (int) env('OEMBED_TIMEOUT', 8),

    'max_response_bytes' => (int) env('OEMBED_MAX_BYTES', 524_288),

    /** Guzzle: disallow redirects (SSRF hardening). */
    'allow_redirects' => false,

    /*
    |--------------------------------------------------------------------------
    | iframe src hosts (lowercase, no leading dot)
    |--------------------------------------------------------------------------
    |
    | oEmbed HTML is stripped to iframes whose host is in this list.
    |
    */
    /*
    |--------------------------------------------------------------------------
    | Testing-only: skip DNS / public-IP resolution for these hosts
    |--------------------------------------------------------------------------
    |
    | Used when APP_ENV=testing so PHPUnit passes without outbound DNS (e.g. CI
    | sandboxes). Comma-separated hostnames in OEMBED_TESTING_SKIP_DNS_HOSTS.
    | Never set in production.
    |
    */
    'testing_skip_dns_hosts' => array_values(array_filter(array_map(trim(...), explode(',', (string) env('OEMBED_TESTING_SKIP_DNS_HOSTS', ''))))),

    'allowed_iframe_hosts' => [
        'www.youtube.com',
        'youtube.com',
        'www.youtube-nocookie.com',
        'youtu.be',
        'player.vimeo.com',
        'www.dailymotion.com',
        'www.facebook.com',
        'facebook.com',
        'www.instagram.com',
        'instagram.com',
        'open.spotify.com',
        'embed.music.apple.com',
        'www.slideshare.net',
        'www.scribd.com',
        'cdn.embedly.com',
        'fast.wistia.net',
        'embed.ted.com',
        'www.kickstarter.com',
        'soundcloud.com',
        'w.soundcloud.com',
        'player.twitch.tv',
        'clips.twitch.tv',
        'www.tiktok.com',
        'tiktok.com',
        'dailymotion.com',
    ],

];
