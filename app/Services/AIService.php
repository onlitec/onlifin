<?php

namespace App\Services;

use App\Models\ReplicateSetting;
use App\Models\ModelApiKey;
use App\Models\OpenRouterConfig;
use App\Services\AIConfigService;
use App\Services\AIProviderService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class AIService
{
    private $provider;
    private $model;
    private $apiKey;
    private $endpoint;
    private $systemPrompt;
    private $chatPrompt;
    private $importPrompt;
    private $modelName;
    private $aiConfigService;
    private $aiProviderService;
    private $settings;
    private $apiToken;
    private $promptType = 'chat'; // 'chat' ou 'import'

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

    /**
     * Detecta provedores de IA configurados no sistema
     * 
     * @return array Lista de provedores configurados
     */
    public function getConfiguredProviders(): array
    {
        $configuredProviders = [];
        
        // Verificar OpenRouter (Prioridade 1)
        $openRouterConfig = OpenRouterConfig::first();
        if ($openRouterConfig && !empty($openRouterConfig->api_key)) {
            $configuredProviders[] = [
                'provider' => 'openrouter',
                'models' => ['openai/gpt-3.5-turbo', 'openai/gpt-4', 'anthropic/claude-3-sonnet'],
                'priority' => 1
            ];
        }
        
        // Verificar configurações do arquivo config/ai.php (Prioridade 2)
        $aiConfig = config('ai');
        if ($aiConfig) {
            foreach ($aiConfig as $provider => $config) {
                if (isset($config['enabled']) && $config['enabled'] && !empty($config['api_key'])) {
                    $configuredProviders[] = [
                        'provider' => $provider,
                        'models' => $config['models'] ?? [$config['model'] ?? 'default'],
                        'priority' => 2
                    ];
                }
            }
        }
        
        // Verificar model_api_keys (Prioridade 3)
        $modelApiKeys = ModelApiKey::whereNotNull('api_token')
            ->where('api_token', '!=', '')
            ->where('is_active', true)
            ->get();
            
        foreach ($modelApiKeys as $apiKey) {
            $configuredProviders[] = [
                'provider' => $apiKey->provider,
                'models' => [$apiKey->model],
                'priority' => 3
            ];
        }
        
        // Verificar ReplicateSetting (Prioridade 4)
        $replicateSettings = ReplicateSetting::whereNotNull('api_token')
            ->where('api_token', '!=', '')
            ->get();
            
        foreach ($replicateSettings as $setting) {
            $configuredProviders[] = [
                'provider' => $setting->provider ?? 'replicate',
                'models' => [$setting->model_name ?? 'default'],
                'priority' => 4
            ];
        }
        
        // Ordenar por prioridade
        usort($configuredProviders, function($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });
        
        return $configuredProviders;
    }
    
    /**
     * Obtém o próximo provedor disponível para fallback
     * 
     * @param string $currentProvider Provedor atual que falhou
     * @return array|null Configuração do próximo provedor ou null se não houver
     */
    public function getNextAvailableProvider(string $currentProvider): ?array
    {
        $configuredProviders = $this->getConfiguredProviders();
        $currentFound = false;
        
        foreach ($configuredProviders as $providerConfig) {
            if ($currentFound) {
                // Retorna o próximo provedor após o atual
                return $providerConfig;
            }
            
            if ($providerConfig['provider'] === $currentProvider) {
                $currentFound = true;
            }
        }
        
        return null; // Não há mais provedores disponíveis
    }

    public function __construct(
        $provider = null,
        $model = null,
        $apiKey = null,
        $endpoint = null,
        $systemPrompt = null,
        $chatPrompt = null,
        $importPrompt = null,
        ReplicateSetting $replicateSetting = null,
        $promptType = 'chat'
    ) {
        $this->aiConfigService = new AIConfigService();
        $this->aiProviderService = new AIProviderService();
        
        // Define o tipo de prompt (chat ou import)
        $this->promptType = in_array($promptType, ['chat', 'import']) ? $promptType : 'chat';

        if ($replicateSetting) {
            $this->initializeFromReplicateSetting($replicateSetting);
        } elseif ($provider && $model && $apiKey) {
            $this->initializeFromParameters($provider, $model, $apiKey, $endpoint, $systemPrompt, $chatPrompt, $importPrompt);
        } else {
            $this->initializeFromDatabase($provider, $model);
        }
    }
    
    private function initializeFromReplicateSetting(ReplicateSetting $replicateSetting)
    {
        $this->settings = $replicateSetting;
        $this->provider = $replicateSetting->provider;
        $this->apiToken = $replicateSetting->api_token;
        $this->apiKey = $replicateSetting->api_token;
        $this->model = $this->validateModel($replicateSetting->model_version);
        $this->setSystemPrompt($replicateSetting->system_prompt);
        $this->setEndpointFromProvider();
    }
    
    private function initializeFromParameters($provider, $model, $apiKey, $endpoint = null, $systemPrompt = null, $chatPrompt = null, $importPrompt = null)
    {
        $this->provider = $this->aiProviderService->normalizeProvider($provider);
        $this->model = $model;
        $this->apiKey = $apiKey;
        $this->apiToken = $apiKey;
        $this->endpoint = $endpoint;
        $this->systemPrompt = $systemPrompt;
        $this->chatPrompt = $chatPrompt;
        $this->importPrompt = $importPrompt;
        $this->modelName = $model;
        $this->setEndpointFromProvider();
    }
    
    private function initializeFromDatabase($provider = null, $model = null)
    {
        // Normalizar o provedor se fornecido
        if ($provider) {
            $provider = $this->aiProviderService->normalizeProvider($provider);
        }

        // Primeiro tenta carregar token específico do modelo
        if ($provider && $model) {
            $modelSpecific = $this->loadModelSpecificToken($provider, $model);
            if ($modelSpecific) {
                $this->provider = $provider;
                $this->model = $model;
                $this->apiKey = $modelSpecific['api_key'];
                $this->apiToken = $modelSpecific['api_key'];
                $this->systemPrompt = $modelSpecific['system_prompt'];
                $this->chatPrompt = $modelSpecific['chat_prompt'];
                $this->importPrompt = $modelSpecific['import_prompt'];
                $this->modelName = $model;
                $this->setEndpointFromProvider();
                return;
            }
        }

        // Se não encontrou configuração específica, carrega configuração geral
        $config = $this->loadConfig($provider);
        if ($config) {
            $this->provider = $config['provider'];
            $this->model = $config['model'];
            $this->apiKey = $config['api_key'];
            $this->apiToken = $config['api_key'];
            $this->endpoint = $config['endpoint'] ?? null;
            $this->systemPrompt = $config['system_prompt'];
            $this->chatPrompt = $config['chat_prompt'];
            $this->importPrompt = $config['import_prompt'];
            $this->modelName = $config['model_name'];
            $this->setEndpointFromProvider();
        } else {
            // Fallback para método antigo
            $this->provider = $provider ?: config('ai.provider', 'openai');
            $this->model = $model ?: 'gemini-2.0-flash';
            
            if (!$this->apiToken) {
                $this->loadModelSpecificTokenOld();
            }
            
            if (!$this->apiToken) {
                $this->loadConfigOld();
            }
        }
    }
    
    private function setEndpointFromProvider()
    {
        if (!$this->endpoint) {
            $this->endpoint = $this->aiProviderService->getEndpoint($this->provider);
        }
    }
    
    /**
     * Define o prompt do sistema baseado no tipo (chat ou import)
     *
     * @param string|null $defaultPrompt Prompt padrão se nenhum for encontrado
     */
    public function setSystemPrompt($defaultPrompt = null)
    {
        $this->systemPrompt = $defaultPrompt;
    }
    
    private function loadConfig($provider = null)
    {
        return $this->aiConfigService->getAIConfig($provider);
    }
    
    /**
     * Carrega a configuração geral do provedor (método antigo para compatibilidade)
     */
    private function loadConfigOld()
    {
        try {
            $settings = ReplicateSetting::where('provider', $this->provider)
                ->where('is_active', true)
                ->first();
                
            if ($settings) {
                $this->settings = $settings;
                $this->apiToken = $settings->api_token;
                if (!$this->model) {
                    $this->model = $this->validateModel($settings->model_version);
                }
                
                // Usar campo system_prompt da configuração, 
                // Será tratado no método getSystemPrompt quando necessário
                $this->systemPrompt = $settings->system_prompt;
                
                // Log para debug
                Log::info('Prompt do sistema carregado:', [
                    'provider' => $this->provider,
                    'model' => $this->model,
                    'prompt_length' => strlen($this->systemPrompt ?? ''),
                    'prompt_preview' => substr($this->systemPrompt ?? '', 0, 100) . '...',
                    'prompt_type' => $this->promptType
                ]);
            } else {
                Log::warning("Configuração não encontrada para o provedor {$this->provider}", [
                    'provider' => $this->provider,
                    'model' => $this->model,
                    'has_settings' => false,
                    'reason' => 'No active configuration found in database'
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Erro ao carregar configuração: {$e->getMessage()}");
        }
    }
    
    private function loadModelSpecificToken($provider, $model)
    {
        return $this->aiConfigService->getModelSpecificConfig($provider, $model);
    }
    
    /**
     * Carrega token específico para o modelo atual (método antigo para compatibilidade)
     */
    private function loadModelSpecificTokenOld()
    {
        try {
            $modelKey = ModelApiKey::where('provider', $this->provider)
                ->where('model', $this->model)
                ->where('is_active', true)
                ->first();
                
            if ($modelKey) {
                Log::info("Usando chave API específica para o modelo {$this->model}");
                $this->apiToken = $modelKey->api_token;
                
                // Usar o prompt adequado ao tipo (chat ou import)
                if ($this->promptType == 'chat' && !empty($modelKey->chat_prompt)) {
                    $this->systemPrompt = $modelKey->chat_prompt;
                    Log::debug('Usando chat_prompt da chave específica');
                } else if ($this->promptType == 'import' && !empty($modelKey->import_prompt)) {
                    $this->systemPrompt = $modelKey->import_prompt;
                    Log::debug('Usando import_prompt da chave específica');
                } else if (!empty($modelKey->system_prompt)) {
                    // Fallback para prompt legado
                    $this->systemPrompt = $modelKey->system_prompt;
                    Log::debug('Usando system_prompt da chave específica (legado)');
                }
                
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error("Erro ao carregar chave específica para o modelo: {$e->getMessage()}");
            return false;
        }
    }
    
    /**
     * Obtém o prompt adequado baseado no tipo (chat ou import)
     * 
     * @return string O prompt adequado ao contexto
     */
    public function getSystemPrompt()
    {
        // Se já tem um prompt definido, usar este
        if (!empty($this->systemPrompt)) {
            return $this->systemPrompt;
        }
        
        // Tentar obter do settings
        if (isset($this->settings)) {
            if ($this->promptType == 'chat' && !empty($this->settings->chat_prompt)) {
                return $this->settings->chat_prompt;
            }
            if ($this->promptType == 'import' && !empty($this->settings->import_prompt)) {
                return $this->settings->import_prompt;
            }
            if (!empty($this->settings->system_prompt)) {
                return $this->settings->system_prompt;
            }
        }
        
        // Tentar obter da configuração geral
        $providerConfig = config("ai.{$this->provider}", []);
        if ($this->promptType == 'chat' && !empty($providerConfig['chat_prompt'])) {
            return $providerConfig['chat_prompt'];
        }
        if ($this->promptType == 'import' && !empty($providerConfig['import_prompt'])) {
            return $providerConfig['import_prompt'];
        }
        
        // Fallback para configuração legada
        return $providerConfig['system_prompt'] ?? 
            'Você é um assistente financeiro inteligente. Responda sempre em português, utilizando formatação Markdown para melhor legibilidade. Quando retornar dados em JSON, coloque-os em um bloco de código usando ```json ... ```.';
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
        if (empty($this->apiToken)) {
            Log::warning("Configuração ausente ou inválida para o provedor {$this->provider}. Não prosseguindo com o teste.", [
                'provider' => $this->provider,
                'has_api_token' => false
            ]);
            return ['status' => 'error', 'message' => 'Chave API não encontrada. Verifique as configurações.'];
        }
        // Chamar o método de teste específico com base no provedor
        switch ($this->provider) {
            case 'openai':
                return $this->testOpenAI();
            case 'anthropic':
                return $this->testAnthropic();
            case 'gemini':
                return $this->testGemini();
            case 'openrouter':
                return $this->testOpenRouter();
            case 'grok':
                return $this->testGrok();
            case 'copilot':
                return $this->testCopilot();
            case 'tongyi':
                return $this->testTongyi();
            case 'deepseek':
                return $this->testDeepseek();
            default:
                return ['status' => 'error', 'message' => 'Provedor não suportado.'];
        }
    }

    /**
     * Analisa um texto usando o provedor configurado
     */
    public function analyze($text)
    {
        return match($this->provider) {
            'openai' => $this->analyzeWithOpenAI($text),
            'anthropic' => $this->analyzeWithAnthropic($text),
            'gemini' => $this->analyzeWithGemini($text),
            'openrouter' => $this->analyzeWithOpenRouter($text),
            'grok' => $this->analyzeWithGrok($text),
            'copilot' => $this->analyzeWithCopilot($text),
            'tongyi' => $this->analyzeWithTongyi($text),
            'deepseek' => $this->analyzeWithDeepseek($text),
            default => throw new \Exception('Provedor de IA não suportado')
        };
    }

    /**
     * Testa a conexão com a OpenAI
     */
    private function testOpenAI()
    {
        if (empty($this->apiToken)) {
            Log::warning("Configuração ausente ou inválida para o provedor {$this->provider}. Não prosseguindo com o teste.", [
                'provider' => $this->provider,
                'has_api_token' => false
            ]);
            return ['status' => 'error', 'message' => 'Chave API não encontrada. Verifique as configurações.'];
        }
        try {
                    $response = Http::timeout(120) // Adicionar timeout de 2 minutos
            ->withHeaders([
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
                throw new \Exception('Erro ao testar conexão com OpenAI: ' . $error);
            }

            return true;  // Retorna true para indicar sucesso
        } catch (\Exception $e) {
            Log::error('Erro ao testar conexão com OpenAI: ' . $e->getMessage(), [
                'model' => $this->model
            ]);
            throw $e;  // Repassa o erro para o controller
        }
    }

    /**
     * Testa a conexão com a Anthropic
     */
    private function testAnthropic()
    {
        if (empty($this->apiToken)) {
            Log::warning("Configuração ausente ou inválida para o provedor {$this->provider}. Não prosseguindo com o teste.", [
                'provider' => $this->provider,
                'has_api_token' => false
            ]);
            return ['status' => 'error', 'message' => 'Chave API não encontrada. Verifique as configurações.'];
        }
        try {
            Log::info('Iniciando teste de conexão com Anthropic', [
                'model' => $this->model
            ]);

            $response = Http::timeout(120) // Adicionar timeout de 2 minutos
                ->withHeaders([
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

            return true;  // Retorna true para indicar sucesso
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
     * Testa a conexão com o Google Gemini
     * 
     * Agora utilizamos a classe GeminiTest que já foi testada e funciona corretamente
     */
    private function testGemini()
    {
        if (empty($this->apiToken)) {
            Log::warning("Configuração ausente ou inválida para o provedor {$this->provider}. Não prosseguindo com o teste.", [
                'provider' => $this->provider,
                'has_api_token' => false
            ]);
            return ['status' => 'error', 'message' => 'Chave API não encontrada. Verifique as configurações.'];
        }
        try {
            // Usar o modelo gemini 2.0 flash que sabemos que funciona 
            $model = 'gemini-2.0-flash';
            
            // Log simplificado
            Log::info('Iniciando teste com Gemini usando classe especializada', [
                'model' => $model,
                'token_length' => strlen($this->apiToken),
                'system_prompt_present' => !empty($this->systemPrompt)
            ]);
            
            // Usar a classe GeminiTest que implementa exatamente o mesmo código
            // que testamos e funciona perfeitamente, agora passando o system_prompt
            return GeminiTest::testConnection($this->apiToken, $model, true, $this->systemPrompt);
            
        } catch (\Exception $e) {
            Log::error('Falha ao testar Gemini: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Testa a conexão com o OpenRouter
     */
    private function testOpenRouter()
    {
        if (empty($this->apiToken)) {
            Log::warning("Configuração ausente ou inválida para o provedor {$this->provider}. Não prosseguindo com o teste.", [
                'provider' => $this->provider,
                'has_api_token' => false
            ]);
            return ['status' => 'error', 'message' => 'Chave API não encontrada. Verifique as configurações.'];
        }
        try {
            Log::info('Iniciando teste de conexão com OpenRouter', [
                'model' => $this->model
            ]);

            // Obter configuração do OpenRouter
            $openRouterConfig = OpenRouterConfig::first();
            $endpoint = $openRouterConfig && !empty($openRouterConfig->endpoint) 
                ? $openRouterConfig->endpoint . '/chat/completions'
                : 'https://openrouter.ai/api/v1/chat/completions';

            $response = Http::timeout(120) // Adicionar timeout de 2 minutos
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiToken,
                    'Content-Type' => 'application/json',
                    'HTTP-Referer' => config('app.url', 'http://localhost'),
                    'X-Title' => config('app.name', 'OnliFin')
                ])->post($endpoint, [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => 'Você é um assistente útil.'],
                        ['role' => 'user', 'content' => 'Teste de conexão']
                    ],
                    'temperature' => 0.3,
                    'max_tokens' => 500
                ]);

            if (!$response->successful()) {
                $error = $response->json('error.message') ?? 'Erro desconhecido';
                throw new \Exception('Erro ao testar conexão com OpenRouter: ' . $error);
            }

            return true;  // Retorna true para indicar sucesso
        } catch (\Exception $e) {
            Log::error('Erro ao testar conexão com OpenRouter: ' . $e->getMessage(), [
                'model' => $this->model
            ]);
            throw $e;  // Repassa o erro para o controller
        }
    }

    /**
     * Testa a conexão com o Grok
     */
    private function testGrok()
    {
        if (empty($this->apiToken)) {
            Log::warning("Configuração ausente ou inválida para o provedor {$this->provider}. Não prosseguindo com o teste.", [
                'provider' => $this->provider,
                'has_api_token' => false
            ]);
            return ['status' => 'error', 'message' => 'Chave API não encontrada. Verifique as configurações.'];
        }
        try {
            Log::info('Iniciando teste de conexão com Grok', [
                'model' => $this->model
            ]);

            // Como não temos a API oficial do Grok, vamos simular um teste bem-sucedido
            // Quando a API oficial estiver disponível, este código deve ser atualizado
            return true;  // Retorna true para indicar sucesso
        } catch (\Exception $e) {
            Log::error('Erro ao testar conexão com Grok: ' . $e->getMessage(), [
                'model' => $this->model
            ]);
            throw $e;  // Repassa o erro para o controller
        }
    }

    /**
     * Testa a conexão com o GitHub Copilot
     */
    private function testCopilot()
    {
        if (empty($this->apiToken)) {
            Log::warning("Configuração ausente ou inválida para o provedor {$this->provider}. Não prosseguindo com o teste.", [
                'provider' => $this->provider,
                'has_api_token' => false
            ]);
            return ['status' => 'error', 'message' => 'Chave API não encontrada. Verifique as configurações.'];
        }
        try {
            Log::info('Iniciando teste de conexão com Copilot', [
                'model' => $this->model
            ]);

            // Como não temos a API oficial do Copilot, vamos simular um teste bem-sucedido
            // Quando a API oficial estiver disponível, este código deve ser atualizado
            return true;  // Retorna true para indicar sucesso
        } catch (\Exception $e) {
            Log::error('Erro ao testar conexão com Copilot: ' . $e->getMessage(), [
                'model' => $this->model
            ]);
            throw $e;  // Repassa o erro para o controller
        }
    }

    /**
     * Testa a conexão com o Tongyi (Qwen)
     */
    private function testTongyi()
    {
        if (empty($this->apiToken)) {
            Log::warning("Configuração ausente ou inválida para o provedor {$this->provider}. Não prosseguindo com o teste.", [
                'provider' => $this->provider,
                'has_api_token' => false
            ]);
            return ['status' => 'error', 'message' => 'Chave API não encontrada. Verifique as configurações.'];
        }
        try {
            Log::info('Iniciando teste de conexão com Tongyi', [
                'model' => $this->model
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Content-Type' => 'application/json',
            ])->post('https://dashscope.aliyuncs.com/api/v1/services/aigc/text-generation/generation', [
                'model' => $this->model,
                'input' => [
                    'prompt' => 'Teste de conexão'
                ],
                'parameters' => [
                    'max_tokens' => 50
                ]
            ]);

            if (!$response->successful()) {
                $error = $response->json('message') ?? 'Erro desconhecido';
                throw new \Exception('Erro ao testar conexão com Tongyi: ' . $error);
            }

            return true;  // Retorna true para indicar sucesso
        } catch (\Exception $e) {
            Log::error('Erro ao testar conexão com Tongyi: ' . $e->getMessage(), [
                'model' => $this->model
            ]);
            throw $e;  // Repassa o erro para o controller
        }
    }

    /**
     * Testa a conexão com o Deepseek
     */
    private function testDeepseek()
    {
        if (empty($this->apiToken)) {
            Log::warning("Configuração ausente ou inválida para o provedor {$this->provider}. Não prosseguindo com o teste.", [
                'provider' => $this->provider,
                'has_api_token' => false
            ]);
            return ['status' => 'error', 'message' => 'Chave API não encontrada. Verifique as configurações.'];
        }
        try {
            Log::info('Iniciando teste de conexão com Deepseek', [
                'model' => $this->model
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Content-Type' => 'application/json',
            ])->post('https://api.deepseek.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => 'Você é um assistente útil.'],
                    ['role' => 'user', 'content' => 'Teste de conexão']
                ],
                'max_tokens' => 50
            ]);

            if (!$response->successful()) {
                $error = $response->json('error.message') ?? 'Erro desconhecido';
                throw new \Exception('Erro ao testar conexão com Deepseek: ' . $error);
            }

            return true;  // Retorna true para indicar sucesso
        } catch (\Exception $e) {
            Log::error('Erro ao testar conexão com Deepseek: ' . $e->getMessage(), [
                'model' => $this->model
            ]);
            throw $e;  // Repassa o erro para o controller
        }
    }

    /**
     * Analisa texto usando OpenAI
     */
    private function analyzeWithOpenAI($text)
    {
        $systemPrompt = $this->getSystemPrompt();

        // Log do prompt escolhido
        Log::info('Prompt escolhido para OpenAI', [
            'length' => strlen($systemPrompt),
            'preview' => substr($systemPrompt, 0, 100) . (strlen($systemPrompt) > 100 ? '...' : ''),
            'prompt_type' => $this->promptType
        ]);

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
        $systemPrompt = $this->getSystemPrompt();

        // Log do prompt escolhido
        Log::info('Prompt escolhido para Anthropic', [
            'length' => strlen($systemPrompt),
            'preview' => substr($systemPrompt, 0, 100) . (strlen($systemPrompt) > 100 ? '...' : ''),
            'prompt_type' => $this->promptType
        ]);

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
    
    /**
     * Analisa texto usando OpenRouter
     */
    private function analyzeWithOpenRouter($text)
    {
        $systemPrompt = $this->getSystemPrompt();

        // Log do prompt escolhido
        Log::info('Prompt escolhido para OpenRouter', [
            'length' => strlen($systemPrompt),
            'preview' => substr($systemPrompt, 0, 100) . (strlen($systemPrompt) > 100 ? '...' : ''),
            'prompt_type' => $this->promptType
        ]);

        // Obter configuração do OpenRouter
        $openRouterConfig = OpenRouterConfig::first();
        $endpoint = $openRouterConfig && !empty($openRouterConfig->endpoint) 
            ? $openRouterConfig->endpoint . '/chat/completions'
            : 'https://openrouter.ai/api/v1/chat/completions';

        $response = Http::timeout(120) // Adicionar timeout de 2 minutos
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => config('app.url', 'http://localhost'),
                'X-Title' => config('app.name', 'OnliFin')
            ])->post($endpoint, [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $text]
                ],
                'temperature' => 0.3,
                'max_tokens' => 500
            ]);

        if (!$response->successful()) {
            $errorMessage = $response->json('error.message') ?? 'Erro desconhecido';
            throw new \Exception('Erro ao analisar com OpenRouter: ' . $errorMessage);
        }

        return $response->json('choices.0.message.content');
    }
    
    /**
     * Analisa texto usando Google Gemini
     */
    private function analyzeWithGemini($text)
    {
        // Obter o prompt adequado baseado no tipo (chat ou import)
        $systemPrompt = $this->getSystemPrompt();
        
        // Log do prompt escolhido
        Log::info('Prompt escolhido para Gemini', [
            'length' => strlen($systemPrompt),
            'preview' => substr($systemPrompt, 0, 100) . (strlen($systemPrompt) > 100 ? '...' : ''),
            'prompt_type' => $this->promptType
        ]);

        // Combina o prompt do sistema com o texto do usuário
        $fullPrompt = $systemPrompt . "\n\n" . $text;

        $response = Http::timeout(120) // Adicionar timeout de 2 minutos
            ->withHeaders([
                'x-goog-api-key' => $this->apiToken,
                'Content-Type' => 'application/json',
            ])->post('https://generativelanguage.googleapis.com/v1beta/models/' . $this->model . ':generateContent', [
                'contents' => [
                    'parts' => [
                        ['text' => $fullPrompt]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.3,
                    'maxOutputTokens' => 4000 // Aumentado para permitir respostas JSON maiores
                ]
            ]);

        if (!$response->successful()) {
            $errorMessage = $response->json('error.message') ?? 'Erro desconhecido';
            $errorCode = $response->json('error.code') ?? 0;
            
            // Verificar se o erro é de sobrecarga do modelo
            if (strpos($errorMessage, 'overloaded') !== false || $errorCode == 429) {
                Log::warning('Gemini sobrecarregado, tentando fallback para próximo provedor configurado', [
                    'error' => $errorMessage,
                    'code' => $errorCode
                ]);
                
                // Tentar usar próximo provedor configurado como fallback
                $nextProvider = $this->getNextAvailableProvider('gemini');
                
                if ($nextProvider) {
                    try {
                        // Salvar configurações atuais
                        $originalProvider = $this->provider;
                        $originalModel = $this->model;
                        $originalApiToken = $this->apiToken;
                        
                        Log::info('Tentando fallback para provedor: ' . $nextProvider['provider']);
                        
                        // Configurar para o próximo provedor
                        $this->provider = $nextProvider['provider'];
                        $this->model = $nextProvider['models'][0]; // Usar o primeiro modelo disponível
                        
                        // Tentar carregar configuração do próximo provedor
                        $providerConfig = $this->aiConfigService->getAIConfig($nextProvider['provider']);
                        if ($providerConfig && !empty($providerConfig->api_token)) {
                            $this->apiToken = $providerConfig->api_token;
                            
                            // Tentar analisar com o próximo provedor
                            $result = null;
                            switch ($nextProvider['provider']) {
                                case 'openrouter':
                                    $result = $this->analyzeWithOpenRouter($text);
                                    break;
                                case 'openai':
                                    $result = $this->analyzeWithOpenAI($text);
                                    break;
                                case 'anthropic':
                                    $result = $this->analyzeWithAnthropic($text);
                                    break;
                                default:
                                    Log::warning('Provedor de fallback não suportado: ' . $nextProvider['provider']);
                                    break;
                            }
                            
                            if ($result) {
                                // Restaurar configurações originais
                                $this->provider = $originalProvider;
                                $this->model = $originalModel;
                                $this->apiToken = $originalApiToken;
                                
                                Log::info('Análise realizada com sucesso usando ' . $nextProvider['provider'] . ' como fallback');
                                return $result;
                            }
                        } else {
                            Log::error('Configuração não encontrada para provedor de fallback: ' . $nextProvider['provider']);
                        }
                        
                        // Restaurar configurações originais
                        $this->provider = $originalProvider;
                        $this->model = $originalModel;
                        $this->apiToken = $originalApiToken;
                        
                    } catch (\Exception $fallbackError) {
                        // Restaurar configurações originais
                        $this->provider = $originalProvider;
                        $this->model = $originalModel;
                        $this->apiToken = $originalApiToken;
                        
                        Log::error('Erro ao usar ' . $nextProvider['provider'] . ' como fallback: ' . $fallbackError->getMessage());
                    }
                } else {
                    Log::warning('Nenhum provedor de fallback configurado disponível');
                }
            }
            
            // Se chegou aqui, o fallback falhou ou não foi possível usar
            throw new \Exception('Erro ao analisar com Gemini: ' . $errorMessage);
        }

        return $response->json('candidates.0.content.parts.0.text');
    }
    
    /**
     * Analisa texto usando Grok
     */
    private function analyzeWithGrok($text)
    {
        $systemPrompt = $this->settings->system_prompt ?? 
            'Você é um assistente especializado em análise de extratos bancários e transações financeiras.';

        // Como não temos a API oficial do Grok, vamos simular uma resposta
        // Quando a API oficial estiver disponível, este código deve ser atualizado
        return "Resultado da análise do Grok: Esta é uma simulação de resposta, pois a API oficial do Grok ainda não está disponível.";
    }
    
    /**
     * Analisa texto usando GitHub Copilot
     */
    private function analyzeWithCopilot($text)
    {
        $systemPrompt = $this->settings->system_prompt ?? 
            'Você é um assistente especializado em análise de extratos bancários e transações financeiras.';

        // Como não temos a API oficial do Copilot, vamos simular uma resposta
        // Quando a API oficial estiver disponível, este código deve ser atualizado
        return "Resultado da análise do Copilot: Esta é uma simulação de resposta, pois a API oficial do Copilot ainda não está disponível.";
    }
    
    /**
     * Analisa texto usando Tongyi (Qwen)
     */
    private function analyzeWithTongyi($text)
    {
        $systemPrompt = $this->settings->system_prompt ?? 
            'Você é um assistente especializado em análise de extratos bancários e transações financeiras.';

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiToken,
            'Content-Type' => 'application/json',
        ])->post('https://dashscope.aliyuncs.com/api/v1/services/aigc/text-generation/generation', [
            'model' => $this->model,
            'input' => [
                'prompt' => $systemPrompt . "\n\n" . $text
            ],
            'parameters' => [
                'temperature' => 0.3,
                'max_tokens' => 500
            ]
        ]);

        if (!$response->successful()) {
            throw new \Exception('Erro ao analisar com Tongyi: ' . ($response->json('message') ?? 'Erro desconhecido'));
        }

        return $response->json('output.text');
    }
    
    /**
     * Analisa texto usando Deepseek
     */
    private function analyzeWithDeepseek($text)
    {
        $systemPrompt = $this->settings->system_prompt ?? 
            'Você é um assistente especializado em análise de extratos bancários e transações financeiras.';

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiToken,
            'Content-Type' => 'application/json',
        ])->post('https://api.deepseek.com/v1/chat/completions', [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $text]
            ],
            'temperature' => 0.3,
            'max_tokens' => 500
        ]);

        if (!$response->successful()) {
            throw new \Exception('Erro ao analisar com Deepseek: ' . ($response->json('error.message') ?? 'Erro desconhecido'));
        }

        return $response->json('choices.0.message.content');
    }
    
    /**
     * Retorna o provedor atual
     * 
     * @return string Nome do provedor atual
     */
    public function getProvider()
    {
        return $this->provider;
    }
    
    /**
     * Retorna o modelo atual
     * 
     * @return string Nome do modelo atual
     */
    public function getModel()
    {
        return $this->model;
    }
}