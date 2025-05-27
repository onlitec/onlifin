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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'ai_statement_analyzer' => [
        'api_key' => env('AI_STATEMENT_API_KEY'),
        'api_url' => env('AI_STATEMENT_API_URL', 'https://api.exemplo.com/analyze-statement'),
    ],
    
    'whatsapp' => [
        'token' => env('WHATSAPP_API_TOKEN'),
        'endpoint' => env('WHATSAPP_API_ENDPOINT', 'https://api.whatsapp.com/v1/messages'),
        'from_phone_number' => env('WHATSAPP_FROM_NUMBER'),
        'enabled' => env('WHATSAPP_ENABLED', false),
    ],
    
    'push_notifications' => [
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
        'enabled' => env('PUSH_NOTIFICATIONS_ENABLED', false),
    ],

    'tiktok' => [
        'client_id' => env('TIKTOK_CLIENT_ID'),
        'client_secret' => env('TIKTOK_CLIENT_SECRET'),
        'redirect' => env('TIKTOK_REDIRECT_URI'),
    ],

    'gcp' => [
        'project_id' => env('GOOGLE_CLOUD_PROJECT'),
        'credentials' => env('GOOGLE_APPLICATION_CREDENTIALS'),
        'document_ai_region' => env('GOOGLE_DOCUMENT_AI_REGION', 'us'), // 'us' é um valor padrão comum
    ],

];
