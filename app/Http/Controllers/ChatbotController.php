<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\AIConfigService;
use Illuminate\Support\Facades\Log;
use App\Services\FinancialDataService;
use Illuminate\Support\Facades\Auth;

class ChatbotController extends Controller
{
    protected $aiConfigService;
    protected $financialDataService;
    private $lastRateLimitedModel = null;
    private $retryCount = 0;
    private $maxRetries = 3;
    private $rateLimitedModels = [];

    public function __construct(
        AIConfigService $aiConfigService,
        FinancialDataService $financialDataService
    ) {
        $this->aiConfigService = $aiConfigService;
        $this->financialDataService = $financialDataService;
    }

    /**
     * Exibe a interface do chatbot financeiro.
     */
    public function index()
    {
        return view('chatbot.index');
    }

    /**
     * Recebe uma mensagem do usuário e responde usando a IA.
     */
    public function ask(Request $request)
    {
        $request->validate([
            'message' => 'required|string|min:2',
        ]);

        // Obter configuração da IA
        $config = $this->aiConfigService->getAIConfig();
        
        Log::info('Configuração da IA:', $config);
        
        if (!$config['is_configured'] || !$config['has_api_key']) {
            Log::error('Configuração da IA não encontrada ou inválida:', $config);
            return response()->json([
                'error' => 'Configuração da IA não encontrada. Por favor, configure a IA em Configurações > Modelos de IA.'
            ], 400);
        }

        // Verificar se o usuário tem empresa associada
        $user = Auth::user();
        if (!$user || !$user->company) {
            Log::warning('Usuário não tem empresa associada', ['user_id' => $user?->id]);
            return response()->json([
                'error' => 'Você precisa ter uma empresa associada para usar o chatbot financeiro. Por favor, configure sua empresa primeiro.'
            ], 400);
        }

        try {
            // Determinar o endpoint baseado no provedor
            $endpoint = $this->getEndpoint($config['provider']);
            Log::info('Endpoint da IA:', ['endpoint' => $endpoint]);
            
            // Preparar os headers baseados no provedor
            $headers = $this->getHeaders($config['provider'], $config['api_key']);
            Log::info('Headers da requisição:', ['headers' => array_keys($headers)]);
            
            // Preparar o payload baseado no provedor
            $payload = $this->getPayload($config['provider'], $config['model'], $config['system_prompt'], $request->message);
            Log::info('Payload da requisição:', ['payload' => $payload]);
            
            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->post($endpoint, $payload);

            Log::info('Resposta bruta da IA:', [
                'status' => $response->status(),
                'body' => $response->body(),
                'headers' => $response->headers(),
                'endpoint' => $endpoint,
                'payload' => $payload
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('Resposta JSON da IA:', ['response' => $responseData]);
                
                // Verificar se há erro de rate limit
                if (isset($responseData['error']) || isset($responseData['response']['error'])) {
                    $error = $responseData['error'] ?? $responseData['response']['error'];
                    if ($error['code'] === 429 || 
                        (isset($error['metadata']['raw']) && strpos($error['metadata']['raw'], 'rate-limited') !== false)) {
                        Log::warning('Rate limit atingido no OpenRouter:', $error);
                        
                        // Se já tentamos todas as opções, retorna erro amigável
                        if ($this->retryCount >= $this->maxRetries) {
                            $this->retryCount = 0;
                            $this->lastRateLimitedModel = null;
                            return response()->json([
                                'error' => 'O serviço de IA está temporariamente indisponível devido ao limite de requisições diárias. Por favor, tente novamente amanhã ou entre em contato com o suporte para aumentar o limite.'
                            ], 429);
                        }
                        
                        // Incrementa contador de tentativas
                        $this->retryCount++;
                        
                        // Armazena o modelo que atingiu o rate limit
                        $this->lastRateLimitedModel = $payload['model'];
                        
                        // Tenta novamente com o próximo modelo
                        return $this->ask($request);
                    }
                }
                
                // Reseta contadores em caso de sucesso
                $this->retryCount = 0;
                $this->lastRateLimitedModel = null;
                
                $answer = $this->extractAnswer($config['provider'], $responseData);
                
                if ($answer) {
                    return response()->json(['answer' => $answer]);
                } else {
                    Log::error('Formato de resposta inválido:', [
                        'provider' => $config['provider'],
                        'response' => $responseData
                    ]);
                    return response()->json([
                        'error' => 'Não foi possível processar a resposta da IA. Por favor, tente novamente.'
                    ], 500);
                }
            } else {
                Log::error('Erro na resposta da IA:', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'headers' => $response->headers(),
                    'endpoint' => $endpoint,
                    'payload' => $payload
                ]);
                return response()->json([
                    'error' => 'Ocorreu um erro ao processar sua solicitação. Por favor, tente novamente mais tarde.'
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Exceção ao processar pergunta do chatbot:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'endpoint' => isset($endpoint) ? $endpoint : null,
                'payload' => isset($payload) ? $payload : null
            ]);
            return response()->json([
                'error' => 'Ocorreu um erro inesperado. Por favor, tente novamente mais tarde.'
            ], 500);
        }
    }

    /**
     * Retorna o endpoint da API baseado no provedor
     */
    private function getEndpoint($provider)
    {
        switch (strtolower($provider)) {
            case 'openrouter':
                return 'https://openrouter.ai/api/v1/chat/completions';
            case 'google':
            case 'gemini':
                return 'https://generativelanguage.googleapis.com/v1/models/gemini-pro:generateContent';
            case 'openai':
                return 'https://api.openai.com/v1/chat/completions';
            case 'anthropic':
                return 'https://api.anthropic.com/v1/messages';
            default:
                Log::error('Provedor de IA não suportado:', ['provider' => $provider]);
                throw new \Exception('Provedor de IA não suportado: ' . $provider);
        }
    }

    /**
     * Retorna os headers da requisição baseados no provedor
     */
    private function getHeaders($provider, $apiKey)
    {
        $headers = [
            'Content-Type' => 'application/json'
        ];

        switch (strtolower($provider)) {
            case 'openrouter':
                $headers['Authorization'] = 'Bearer ' . $apiKey;
                $headers['HTTP-Referer'] = config('app.url');
                $headers['X-Title'] = 'Onlifin - Chatbot Financeiro';
                break;
            case 'google':
            case 'gemini':
                $headers['x-goog-api-key'] = $apiKey;
                break;
            case 'openai':
                $headers['Authorization'] = 'Bearer ' . $apiKey;
                break;
            case 'anthropic':
                $headers['x-api-key'] = $apiKey;
                $headers['anthropic-version'] = '2023-06-01';
                break;
            default:
                Log::error('Provedor de IA não suportado para headers:', ['provider' => $provider]);
                throw new \Exception('Provedor de IA não suportado: ' . $provider);
        }

        return $headers;
    }

    /**
     * Retorna o payload da requisição baseado no provedor
     */
    private function getPayload($provider, $model, $systemPrompt, $message)
    {
        // Obtém dados financeiros para incluir no contexto
        $financialContext = $this->getFinancialContext();
        
        // Adiciona o contexto financeiro ao prompt do sistema
        $enhancedSystemPrompt = $systemPrompt . "\n\nContexto Financeiro Atual:\n" . $financialContext;

        switch (strtolower($provider)) {
            case 'openrouter':
                if (in_array($model, $this->rateLimitedModels)) {
                    $model = $this->getNextAvailableModel($model);
                    Log::info('Trocando para modelo alternativo:', ['model' => $model]);
                }
                
                return [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $enhancedSystemPrompt
                        ],
                        [
                            'role' => 'user',
                            'content' => $message
                        ]
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 500,
                    'route' => 'fallback'
                ];
            case 'google':
            case 'gemini':
                return [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => ($enhancedSystemPrompt ?? 'Você é um assistente financeiro inteligente. Responda em português, de forma clara e objetiva, com base nos dados do usuário e contexto financeiro.') . "\n\n" . $message]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'maxOutputTokens' => 500
                    ]
                ];
            case 'openai':
                return [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $enhancedSystemPrompt
                        ],
                        [
                            'role' => 'user',
                            'content' => $message
                        ]
                    ],
                'temperature' => 0.7,
                    'max_tokens' => 500
                ];
            case 'anthropic':
                return [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $message
                        ]
                    ],
                    'system' => $enhancedSystemPrompt,
                    'max_tokens' => 500
                ];
            default:
                Log::error('Provedor de IA não suportado para payload:', ['provider' => $provider]);
                throw new \Exception('Provedor de IA não suportado: ' . $provider);
        }
    }

    /**
     * Obtém o contexto financeiro atual para incluir no prompt
     */
    private function getFinancialContext(): string
    {
        $transactions = $this->financialDataService->getRecentTransactions(5);
        $accounts = $this->financialDataService->getBankAccountsBalance();
        $summary = $this->financialDataService->getFinancialSummary();

        $context = "Dados Financeiros Atuais:\n\n";
        
        // Adiciona resumo financeiro
        $context .= "Resumo dos Últimos 30 Dias:\n";
        $context .= "- Receitas Totais: R$ " . number_format($summary['total_income'], 2, ',', '.') . "\n";
        $context .= "- Despesas Totais: R$ " . number_format($summary['total_expenses'], 2, ',', '.') . "\n";
        $context .= "- Resultado Líquido: R$ " . number_format($summary['net_income'], 2, ',', '.') . "\n\n";

        // Adiciona saldos das contas
        $context .= "Saldos das Contas:\n";
        foreach ($accounts as $account) {
            $context .= "- {$account['name']}: R$ " . number_format($account['balance'], 2, ',', '.') . "\n";
        }
        $context .= "\n";

        // Adiciona transações recentes
        $context .= "Transações Recentes:\n";
        foreach ($transactions as $transaction) {
            $context .= "- {$transaction['date']}: {$transaction['description']} - R$ " . 
                       number_format($transaction['amount'], 2, ',', '.') . 
                       " ({$transaction['type']})\n";
        }

        return $context;
    }

    /**
     * Extrai a resposta do payload baseado no provedor
     */
    private function extractAnswer($provider, $responseData)
    {
        try {
            Log::info('Extraindo resposta da IA:', [
                'provider' => $provider,
                'response' => $responseData
            ]);

            switch (strtolower($provider)) {
                case 'openrouter':
                    // Verificar se há erro de rate limit
                    if (isset($responseData['error']) || isset($responseData['response']['error'])) {
                        $error = $responseData['error'] ?? $responseData['response']['error'];
                        if ($error['code'] === 429 || 
                            (isset($error['metadata']['raw']) && strpos($error['metadata']['raw'], 'rate-limited') !== false)) {
                            Log::warning('Rate limit atingido no OpenRouter:', $error);
                            return null;
                        }
                    }
                    
                    // Tenta diferentes formatos de resposta do OpenRouter
                    if (isset($responseData['choices'][0]['message']['content'])) {
                        return $responseData['choices'][0]['message']['content'];
                    }
                    if (isset($responseData['data']['choices'][0]['message']['content'])) {
                        return $responseData['data']['choices'][0]['message']['content'];
                    }
                    if (isset($responseData['data']['text'])) {
                        return $responseData['data']['text'];
                    }
                    Log::error('Formato de resposta inválido do OpenRouter:', ['response' => $responseData]);
                    return null;
                case 'google':
                case 'gemini':
                    return $responseData['candidates'][0]['content']['parts'][0]['text'] ?? null;
                case 'openai':
                    return $responseData['choices'][0]['message']['content'] ?? null;
                case 'anthropic':
                    return $responseData['content'][0]['text'] ?? null;
                default:
                    Log::error('Provedor de IA não suportado para extração de resposta:', ['provider' => $provider]);
                    return null;
            }
        } catch (\Exception $e) {
            Log::error('Erro ao extrair resposta da IA:', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'response' => $responseData
            ]);
            return null;
        }
    }
} 