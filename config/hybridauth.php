<?php

return [
    /**
     * Debug Mode
     */
    'debug_mode' => env('HYBRIDAUTH_DEBUG', false),
    'debug_file' => storage_path('logs/hybridauth.log'),

    /**
     * Providers configuration
     */
    'providers' => [
        'Google' => [
            'enabled' => env('GOOGLE_ENABLED', true),
            'keys' => [
                'id' => env('GOOGLE_CLIENT_ID'),
                'secret' => env('GOOGLE_CLIENT_SECRET'),
            ],
            'scope' => 'openid profile email',
            'authorize_url_parameters' => [
                'approval_prompt' => 'auto',
                'access_type' => 'online',
            ],
        ],

        'Facebook' => [
            'enabled' => env('FACEBOOK_ENABLED', false),
            'keys' => [
                'id' => env('FACEBOOK_CLIENT_ID'),
                'secret' => env('FACEBOOK_CLIENT_SECRET'),
            ],
            'scope' => 'email,public_profile',
            'display' => 'popup',
        ],

        'Twitter' => [
            'enabled' => env('TWITTER_ENABLED', false),
            'keys' => [
                'key' => env('TWITTER_CLIENT_ID'),
                'secret' => env('TWITTER_CLIENT_SECRET'),
            ],
            'includeEmail' => true,
        ],

        'GitHub' => [
            'enabled' => env('GITHUB_ENABLED', false),
            'keys' => [
                'id' => env('GITHUB_CLIENT_ID'),
                'secret' => env('GITHUB_CLIENT_SECRET'),
            ],
            'scope' => 'user:email',
        ],

        'LinkedIn' => [
            'enabled' => env('LINKEDIN_ENABLED', false),
            'keys' => [
                'id' => env('LINKEDIN_CLIENT_ID'),
                'secret' => env('LINKEDIN_CLIENT_SECRET'),
            ],
            'scope' => 'r_liteprofile r_emailaddress',
        ],

        'Microsoft' => [
            'enabled' => env('MICROSOFT_ENABLED', false),
            'keys' => [
                'id' => env('MICROSOFT_CLIENT_ID'),
                'secret' => env('MICROSOFT_CLIENT_SECRET'),
            ],
            'scope' => 'openid profile email',
        ],
    ],

    /**
     * Callback URL base
     */
    'callback_url' => env('APP_URL') . '/auth/social/callback',

    /**
     * CURL settings
     */
    'curl_options' => [
        CURLOPT_SSL_VERIFYPEER => env('HYBRIDAUTH_SSL_VERIFY', true),
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_USERAGENT => 'Onlifin Social Auth',
    ],

    /**
     * Session settings
     */
    'session' => [
        'name' => 'hybridauth_session',
        'lifetime' => 1440, // 24 hours in minutes
    ],
]; 