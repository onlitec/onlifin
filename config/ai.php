<?php

return [
    'enabled' => env('AI_ENABLED', false),
    'provider' => env('AI_PROVIDER', 'openai'),
    
    // Configurações gerais por provedor
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-3.5-turbo'),
    ],
    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-3-haiku-20240307'),
    ],
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-1.5-pro'),
    ],
    
    // Lista de provedores e seus modelos para a interface
    'providers' => [
        'openai' => [
            'name' => 'OpenAI',
            'models' => [
                'gpt-3.5-turbo',
                'gpt-4',
                'gpt-4-turbo-preview',
                'gpt-4o',
                'gpt-4o-mini'
            ],
            'icon' => 'ri-openai-fill'
        ],
        'anthropic' => [
            'name' => 'Anthropic Claude',
            'models' => [
                'claude-3-opus-20240229',
                'claude-3-sonnet-20240229',
                'claude-3-haiku-20240307'
            ],
            'icon' => 'ri-robot-fill'
        ],
        'gemini' => [
            'name' => 'Google Gemini',
            'models' => [
                'gemini-1.5-flash',
                'gemini-1.5-pro',
                'gemini-pro',
                'gemini-pro-vision'
            ],
            'icon' => 'ri-google-fill'
        ],
        'grok' => [
            'name' => 'xAI Grok',
            'models' => [
                'grok-1',
                'grok-2'
            ],
            'icon' => 'ri-robot-2-fill'
        ],
        'copilot' => [
            'name' => 'Microsoft Copilot',
            'models' => [
                'copilot-gpt-4',
                'copilot-gpt-3.5-turbo'
            ],
            'icon' => 'ri-microsoft-fill'
        ],
        'tongyi' => [
            'name' => 'Alibaba Tongyi',
            'models' => [
                'qwen-turbo',
                'qwen-plus',
                'qwen-max'
            ],
            'icon' => 'ri-ali-pay-fill'
        ],
        'deepseek' => [
            'name' => 'DeepSeek',
            'models' => [
                'deepseek-chat',
                'deepseek-coder'
            ],
            'icon' => 'ri-braces-fill'
        ],
        'openrouter' => [
            'name' => 'OpenRouter',
            'models' => ['meta-llama/llama-3-70b-instruct', 'other-openrouter-models'],
            'icon' => 'ri-global-fill'
        ]
    ]
];
