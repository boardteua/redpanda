<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Open Graph default image (path under public/)
    |--------------------------------------------------------------------------
    |
    | Recommended 1200×630. Override with SEO_OG_IMAGE_PATH in .env if needed.
    |
    */

    'og_image_path' => env('SEO_OG_IMAGE_PATH', '/brand/og-default.png'),

    /*
    |--------------------------------------------------------------------------
    | Organization logo for JSON-LD (path under public/)
    |--------------------------------------------------------------------------
    */

    'organization_logo_path' => env('SEO_ORGANIZATION_LOGO_PATH', '/brand/board-te-ua-orange.png'),

    /*
    |--------------------------------------------------------------------------
    | Sitemap: publicly indexable paths (no leading slash)
    |--------------------------------------------------------------------------
    |
    | Home "/" is always included. Add only routes that return safe public HTML
    | or documentation without authentication.
    |
    */

    'sitemap_paths' => [
        'llms.txt',
        'docs/openapi.yaml',
        'docs/chat-v2/AI-AGENT-FRIENDLY.md',
        'docs/project-specs/chat-v2-setup.md',
    ],

];
