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

    'twilio' => [
        'enabled' => env('TWILIO_ENABLED', false),
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'from' => env('TWILIO_FROM'), // Número de WhatsApp (com código do país +5511999999999)
        'whatsapp' => env('TWILIO_WHATSAPP', true), // Usar formato de WhatsApp no Twilio (true = whatsapp:+55...)
        'sandbox' => env('TWILIO_SANDBOX', true), // Usar ambiente de sandbox do WhatsApp
    ],

];
