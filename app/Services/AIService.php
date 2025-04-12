<?php

namespace App\Services;

use App\Models\ReplicateSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    private $settings;
    private $apiToken;
    private $provider;
    private $model;

    // Lista de modelos disponíveis
    private const OPENAI_MODELS = [
        'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
        'gpt-4-turbo-preview' => 'GPT-4 Turbo',
        'gpt-4' => 'GPT-4'
    ];

    private const ANTHROPIC_MODELS = [
        'claude-3-opus-20240229' => 'Claude 3 Opus',
        'claude-3-sonnet-20240229' => 'Claude 3 Sonnet',
        'claude-3-haiku-20240307' => 'Claude 3 Haiku'
    ];

    public function __construct(ReplicateSetting $settings)
    {
        $this->settings = $settings;
        $this->apiToken = $settings->api_token;
        $this->provider = $settings->provider;
        $this->model = $this->validateModel($settings->model_version);
    }

    /**
     * Valida e retorna o modelo apropriado
     */
    private function validateModel($model)
    {
        if ($this->provider === 'openai') {
            // Se o modelo solicitado não estiver disponível, use gpt-3.5-turbo
            if (!isset(self::OPENAI_MODELS[$model])) {
                Log::warning("Modelo OpenAI '$model' não disponível, usando gpt-3.5-turbo");
                return 'gpt-3.5-turbo';
            }
        } elseif ($this->provider === 'anthropic') {
            // Se o modelo solicitado não estiver disponível, use claude-3-haiku
            if (!isset(self::ANTHROPIC_MODELS[$model])) {
                Log::warning("Modelo Anthropic '$model' não disponível, usando claude-3-haiku-20240307");
                return 'claude-3-haiku-20240307';
            }
        }
        return $model;
    }

    /**
     * Testa a conexão com o provedor de IA
     */
    public function test()
    {
        return match($this->provider) {
            'openai' => $this->testOpenAI(),
            'anthropic' => $this->testAnthropic(),
            default => throw new \Exception('Provedor de IA não suportado')
        };
    }

    /**
     * Analisa um texto usando o provedor configurado
     */
    public function analyze($text)
    {
        return match($this->provider) {
            'openai' => $this->analyzeWithOpenAI($text),
            'anthropic' => $this->analyzeWithAnthropic($text),
            default => throw new \Exception('Provedor de IA não suportado')
        };
    }

    /**
     * Testa a conexão com a OpenAI
     */
    private function testOpenAI()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => 'Você é um assistente útil.'],
                    ['role' => 'user', 'content' => 'Teste de conexão']
                ],
                'max_tokens' => 50
            ]);

            if (!$response->successful()) {
                $error = $response->json('error.message') ?? 'Erro desconhecido';
                Log::error('Erro OpenAI:', [
                    'status' => $response->status(),
                    'error' => $error,
                    'model' => $this->model
                ]);
                
                // Se o erro for relacionado ao modelo, tente com gpt-3.5-turbo
                if (str_contains($error, 'model') && $this->model !== 'gpt-3.5-turbo') {
                    Log::info('Tentando novamente com gpt-3.5-turbo');
                    $this->model = 'gpt-3.5-turbo';
                    return $this->testOpenAI();
                }
                
                throw new \Exception('Erro ao conectar com OpenAI: ' . $error);
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Exceção ao conectar com OpenAI:', [
                'error' => $e->getMessage(),
                'model' => $this->model
            ]);
            throw $e;
        }
    }

    /**
     * Testa a conexão com a Anthropic
     */
    private function testAnthropic()
    {
        try {
            Log::info('Iniciando teste de conexão com Anthropic', [
                'model' => $this->model
            ]);

            $response = Http::withHeaders([
                'x-api-key' => $this->apiToken,
                'anthropic-version' => '2024-02-15',
                'Content-Type' => 'application/json',
            ])->post('https://api.anthropic.com/v1/messages', [
                'model' => $this->model,
                'max_tokens' => 50,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Teste de conexão'
                    ]
                ]
            ]);

            Log::info('Resposta da Anthropic', [
                'status' => $response->status(),
                'body' => $response->json()
            ]);

            if (!$response->successful()) {
                $error = $response->json();
                Log::error('Erro detalhado Anthropic:', [
                    'status' => $response->status(),
                    'error' => $error,
                    'headers' => $response->headers(),
                    'model' => $this->model
                ]);
                
                // Se o erro for relacionado ao modelo, tente com claude-3-haiku
                if (isset($error['error']['type']) && $error['error']['type'] === 'authentication_error') {
                    throw new \Exception('Erro de autenticação: Verifique se sua chave API está correta');
                }
                
                if (isset($error['error']['message'])) {
                    throw new \Exception($error['error']['message']);
                }
                
                throw new \Exception('Erro desconhecido ao conectar com Anthropic');
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Exceção ao conectar com Anthropic:', [
                'error' => $e->getMessage(),
                'model' => $this->model,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Analisa texto usando OpenAI
     */
    private function analyzeWithOpenAI($text)
    {
        $systemPrompt = $this->settings->system_prompt ?? 
            'Você é um assistente especializado em análise de extratos bancários e transações financeiras.';

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiToken,
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $text]
            ],
            'temperature' => 0.3,
            'max_tokens' => 500
        ]);

        if (!$response->successful()) {
            throw new \Exception('Erro ao analisar com OpenAI: ' . $response->json('error.message'));
        }

        return $response->json('choices.0.message.content');
    }

    /**
     * Analisa texto usando Anthropic
     */
    private function analyzeWithAnthropic($text)
    {
        $systemPrompt = $this->settings->system_prompt ?? 
            'Você é um assistente especializado em análise de extratos bancários e transações financeiras.';

        $response = Http::withHeaders([
            'x-api-key' => $this->apiToken,
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => $this->model,
            'system' => $systemPrompt,
            'messages' => [
                ['role' => 'user', 'content' => $text]
            ],
            'max_tokens' => 500
        ]);

        if (!$response->successful()) {
            throw new \Exception('Erro ao analisar com Anthropic: ' . $response->json('error.message'));
        }

        return $response->json('content.0.text');
    }
} 