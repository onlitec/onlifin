<?php

namespace App\Services;

class AIProviderService
{
    /**
     * Lista de provedores de IA disponíveis com seus modelos
     */
    private const PROVIDERS = [
        'gemini' => [
            'name' => 'Google Gemini',
            'models' => [
                'gemini-2.0-flash' => 'Gemini 2.0 Flash',
                'gemini-1.5-pro' => 'Gemini 1.5 Pro',
                'gemini-1.5-flash' => 'Gemini 1.5 Flash',
                'gemini-pro' => 'Gemini Pro',
            ],
            'endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent',
            'api_key_param' => 'key'
        ],
        'openai' => [
            'name' => 'OpenAI',
            'models' => [
                'gpt-4o' => 'GPT-4o',
                'gpt-4-turbo' => 'GPT-4 Turbo',
                'gpt-4' => 'GPT-4',
                'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
            ],
            'endpoint' => 'https://api.openai.com/v1/chat/completions',
            'api_key_param' => 'Authorization'
        ],
        'anthropic' => [
            'name' => 'Anthropic',
            'models' => [
                'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet',
                'claude-3-opus-20240229' => 'Claude 3 Opus',
                'claude-3-sonnet-20240229' => 'Claude 3 Sonnet',
                'claude-3-haiku-20240307' => 'Claude 3 Haiku',
            ],
            'endpoint' => 'https://api.anthropic.com/v1/messages',
            'api_key_param' => 'x-api-key'
        ],
        'deepseek' => [
            'name' => 'DeepSeek',
            'models' => [
                'deepseek-chat' => 'DeepSeek Chat',
                'deepseek-coder' => 'DeepSeek Coder',
            ],
            'endpoint' => 'https://api.deepseek.com/v1/chat/completions',
            'api_key_param' => 'Authorization'
        ],
        'qwen' => [
            'name' => 'Qwen',
            'models' => [
                'qwen-turbo' => 'Qwen Turbo',
                'qwen-plus' => 'Qwen Plus',
                'qwen-max' => 'Qwen Max',
            ],
            'endpoint' => 'https://dashscope.aliyuncs.com/api/v1/services/aigc/text-generation/generation',
            'api_key_param' => 'Authorization'
        ],
        'openrouter' => [
            'name' => 'OpenRouter',
            'models' => [
                'openai/gpt-4o' => 'GPT-4o',
                'openai/gpt-4-turbo' => 'GPT-4 Turbo',
                'openai/gpt-4' => 'GPT-4',
                'openai/gpt-3.5-turbo' => 'GPT-3.5 Turbo',
                'anthropic/claude-3-5-sonnet' => 'Claude 3.5 Sonnet',
                'anthropic/claude-3-opus' => 'Claude 3 Opus',
                'anthropic/claude-3-sonnet' => 'Claude 3 Sonnet',
                'anthropic/claude-3-haiku' => 'Claude 3 Haiku',
                'google/gemini-pro' => 'Gemini Pro',
                'meta-llama/llama-3.1-405b-instruct' => 'Llama 3.1 405B',
                'meta-llama/llama-3.1-70b-instruct' => 'Llama 3.1 70B',
                'meta-llama/llama-3.1-8b-instruct' => 'Llama 3.1 8B',
            ],
            'endpoint' => 'https://openrouter.ai/api/v1/chat/completions',
            'api_key_param' => 'Authorization'
        ]
    ];

    /**
     * Retorna todos os provedores disponíveis
     *
     * @return array
     */
    public function getProviders(): array
    {
        return self::PROVIDERS;
    }

    /**
     * Retorna informações de um provedor específico
     *
     * @param string $provider
     * @return array|null
     */
    public function getProvider(string $provider): ?array
    {
        return self::PROVIDERS[$provider] ?? null;
    }

    /**
     * Retorna os modelos de um provedor específico
     *
     * @param string $provider
     * @return array
     */
    public function getModels(string $provider): array
    {
        return self::PROVIDERS[$provider]['models'] ?? [];
    }

    /**
     * Verifica se um provedor existe
     *
     * @param string $provider
     * @return bool
     */
    public function providerExists(string $provider): bool
    {
        return isset(self::PROVIDERS[$provider]);
    }

    /**
     * Verifica se um modelo existe para um provedor
     *
     * @param string $provider
     * @param string $model
     * @return bool
     */
    public function modelExists(string $provider, string $model): bool
    {
        return isset(self::PROVIDERS[$provider]['models'][$model]);
    }

    /**
     * Retorna o endpoint de um provedor
     *
     * @param string $provider
     * @return string|null
     */
    public function getEndpoint(string $provider): ?string
    {
        return self::PROVIDERS[$provider]['endpoint'] ?? null;
    }

    /**
     * Retorna o parâmetro de API key de um provedor
     *
     * @param string $provider
     * @return string|null
     */
    public function getApiKeyParam(string $provider): ?string
    {
        return self::PROVIDERS[$provider]['api_key_param'] ?? null;
    }

    /**
     * Retorna lista formatada para uso em select HTML
     *
     * @return array
     */
    public function getProvidersForSelect(): array
    {
        $providers = [];
        foreach (self::PROVIDERS as $key => $provider) {
            $providers[$key] = $provider['name'];
        }
        return $providers;
    }

    /**
     * Retorna lista de modelos formatada para uso em select HTML
     *
     * @param string $provider
     * @return array
     */
    public function getModelsForSelect(string $provider): array
    {
        return self::PROVIDERS[$provider]['models'] ?? [];
    }

    /**
     * Normaliza o nome do provedor (para compatibilidade)
     *
     * @param string $provider
     * @return string
     */
    public function normalizeProvider(string $provider): string
    {
        // Mapeamento para compatibilidade
        $mapping = [
            'google' => 'gemini',
            'meta' => 'openrouter', // Meta models are usually accessed via OpenRouter
        ];

        return $mapping[$provider] ?? $provider;
    }
}