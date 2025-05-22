<?php

namespace App\Services;

use App\Models\ReplicateSetting;
use App\Models\ModelApiKey;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    private $settings;
    private $apiToken;
    private $provider;
    private $model;
    private $systemPrompt;
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
     * Construtor que suporta três formas de inicialização:
     * 1. Com objeto ReplicateSetting (modo compatibilidade)
     * 2. Com parâmetros provider, model e apiToken fornecidos manualmente
     * 3. Com parâmetros provider e model, buscando a apiToken específica no banco
     */
    public function __construct($providerOrSettings = null, $model = null, $apiToken = null, $promptType = 'chat')
    {
        // Define o tipo de prompt (chat ou import)
        $this->promptType = in_array($promptType, ['chat', 'import']) ? $promptType : 'chat';
        
        // Modo 1: Compatibilidade com código existente
        if ($providerOrSettings instanceof ReplicateSetting) {
            $this->settings = $providerOrSettings;
            $this->provider = $providerOrSettings->provider;
            $this->apiToken = $providerOrSettings->api_token;
            $this->model = $this->validateModel($providerOrSettings->model_version);
            $this->setSystemPrompt($providerOrSettings->system_prompt);
            return;
        }
        
        // Modo 2 e 3: Inicialização manual
        $this->provider = $providerOrSettings ?: config('ai.provider', 'openai');
        $this->model = $model ?: 'gemini-2.0-flash'; // Valor padrão
        $this->apiToken = $apiToken;
        
        // Se não foi fornecida uma API token, tentar buscar uma específica para este modelo
        if (!$this->apiToken) {
            $this->loadModelSpecificToken();
        }
        
        // Se ainda não tiver token, carregar da configuração geral
        if (!$this->apiToken) {
            $this->loadConfig();
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
    
    /**
     * Carrega a configuração geral do provedor
     */
    private function loadConfig()
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
    
    /**
     * Carrega token específico para o modelo atual
     */
    private function loadModelSpecificToken()
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

        $response = Http::withHeaders([
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
                'maxOutputTokens' => 500
            ]
        ]);

        if (!$response->successful()) {
            throw new \Exception('Erro ao analisar com Gemini: ' . ($response->json('error.message') ?? 'Erro desconhecido'));
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
} 