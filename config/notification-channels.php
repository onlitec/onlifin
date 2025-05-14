<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configurações de Canais de Notificação
    |--------------------------------------------------------------------------
    |
    | Este arquivo contém as configurações para os diferentes canais de notificação
    | suportados pelo sistema, incluindo WhatsApp, Email e Push Notifications.
    |
    */

    'whatsapp' => [
        'default' => env('WHATSAPP_PROVIDER', 'twilio'),
        'enabled' => env('WHATSAPP_ENABLED', false),
        'use_templates' => env('WHATSAPP_USE_TEMPLATES', false),
        'debug_mode' => env('WHATSAPP_DEBUG_MODE', false),
        'retry_failed' => env('WHATSAPP_RETRY_FAILED', true),
        'max_retries' => env('WHATSAPP_MAX_RETRIES', 3),
        'queue' => env('WHATSAPP_QUEUE', 'notifications'),
        
        // Templates disponíveis para uso com provedores que suportam
        'templates' => [
            'notification_template' => [
                'name' => 'notification_template',
                'language' => 'pt_BR',
            ],
            'alert_template' => [
                'name' => 'alert_template',
                'language' => 'pt_BR',
            ],
            'reminder_template' => [
                'name' => 'reminder_template',
                'language' => 'pt_BR',
            ],
        ],
        
        'providers' => [
            'twilio' => [
                'enabled' => env('TWILIO_ENABLED', false),
                'account_sid' => env('TWILIO_ACCOUNT_SID'),
                'auth_token' => env('TWILIO_AUTH_TOKEN'),
                'from_number' => env('TWILIO_FROM_NUMBER'),
                'whatsapp_prefix' => 'whatsapp:',
                'sandbox_mode' => env('TWILIO_SANDBOX_MODE', false),
            ],
            
            'messagebird' => [
                'enabled' => env('MESSAGEBIRD_ENABLED', false),
                'access_key' => env('MESSAGEBIRD_ACCESS_KEY'),
                'channel_id' => env('MESSAGEBIRD_CHANNEL_ID'),
                'namespace' => env('MESSAGEBIRD_NAMESPACE'),
                'template_namespace' => env('MESSAGEBIRD_TEMPLATE_NAMESPACE'),
            ],
            
            'vonage' => [
                'enabled' => env('VONAGE_ENABLED', false),
                'api_key' => env('VONAGE_API_KEY'),
                'api_secret' => env('VONAGE_API_SECRET'),
                'from_number' => env('VONAGE_FROM_NUMBER'),
                'application_id' => env('VONAGE_APPLICATION_ID'),
            ],
            
            'infobip' => [
                'enabled' => env('INFOBIP_ENABLED', false),
                'base_url' => env('INFOBIP_BASE_URL'),
                'api_key' => env('INFOBIP_API_KEY'),
                'from_number' => env('INFOBIP_FROM_NUMBER'),
                'scenario_key' => env('INFOBIP_SCENARIO_KEY'),
            ],
            
            'zenvia' => [
                'enabled' => env('ZENVIA_ENABLED', false),
                'api_key' => env('ZENVIA_API_KEY'),
                'from_number' => env('ZENVIA_FROM_NUMBER'),
                'channel' => env('ZENVIA_CHANNEL', 'whatsapp'),
            ],
            
            'mock' => [
                'enabled' => env('MOCK_WHATSAPP_ENABLED', true),
                'log_messages' => true,
                'simulate_delay' => true,
                'simulate_errors' => env('MOCK_SIMULATE_ERRORS', false),
                'error_rate' => env('MOCK_ERROR_RATE', 0.1), // 10% de chance de erro
            ],
        ],
    ],
    
    'push' => [
        'enabled' => env('PUSH_NOTIFICATIONS_ENABLED', false),
        'vapid_public_key' => env('VAPID_PUBLIC_KEY'),
        'vapid_private_key' => env('VAPID_PRIVATE_KEY'),
        'vapid_subject' => env('VAPID_SUBJECT', 'mailto:notifications@onlifin.com'),
    ],
];
