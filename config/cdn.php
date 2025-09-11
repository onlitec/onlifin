<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CDN Configuration
    |--------------------------------------------------------------------------
    |
    | Configurações para CDN (Content Delivery Network) para otimização
    | de performance de assets estáticos.
    |
    */

    'enabled' => env('CDN_ENABLED', false),

    'url' => env('CDN_URL', 'https://cdn.onlifin.com'),

    'assets' => [
        'css' => env('CDN_CSS_URL', env('CDN_URL', 'https://cdn.onlifin.com') . '/css'),
        'js' => env('CDN_JS_URL', env('CDN_URL', 'https://cdn.onlifin.com') . '/js'),
        'images' => env('CDN_IMAGES_URL', env('CDN_URL', 'https://cdn.onlifin.com') . '/images'),
        'fonts' => env('CDN_FONTS_URL', env('CDN_URL', 'https://cdn.onlifin.com') . '/fonts'),
    ],

    'providers' => [
        'aws' => [
            'enabled' => env('CDN_AWS_ENABLED', false),
            'bucket' => env('CDN_AWS_BUCKET'),
            'region' => env('CDN_AWS_REGION', 'us-east-1'),
            'url' => env('CDN_AWS_URL'),
        ],
        'cloudflare' => [
            'enabled' => env('CDN_CLOUDFLARE_ENABLED', false),
            'zone_id' => env('CDN_CLOUDFLARE_ZONE_ID'),
            'api_token' => env('CDN_CLOUDFLARE_API_TOKEN'),
        ],
    ],

    'cache' => [
        'enabled' => env('CDN_CACHE_ENABLED', true),
        'ttl' => env('CDN_CACHE_TTL', 3600), // 1 hora
    ],

    'fallback' => [
        'enabled' => env('CDN_FALLBACK_ENABLED', true),
        'local_url' => env('APP_URL'),
    ],
];
