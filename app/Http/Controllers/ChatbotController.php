<?php

/*
 * ATENÇÃO: CORREÇÃO CRÍTICA no ChatbotController.
 * NÃO ALTERAR ESTE CÓDIGO SEM AUTORIZAÇÃO EXPLÍCITA.
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\AIConfigService;
use Illuminate\Support\Facades\Log;
use App\Services\FinancialDataService;
use Illuminate\Support\Facades\Auth;
use App\Services\StatementImportService;
use Illuminate\Http\UploadedFile;
use App\Http\Controllers\TempStatementImportController;

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
        $config = $this->aiConfigService->getAIConfig();
        // Obter contas bancárias ativas do usuário
        $user = Auth::user();
        $accounts = $user->accounts()->where('active', true)->orderBy('name')->get();
        return view('chatbot.index', compact('config', 'accounts'));
    }

    /**
     * Recebe uma mensagem do usuário e responde usando a IA.
     */
    public function ask(Request $request)
    {
        try {
            // Validação e configuração da IA
            $request->validate(['message' => 'required|string|min:2']);
            $config = $this->aiConfigService->getAIConfig();
            Log::info('Configuração da IA:', $config);
            if (!$config['is_configured'] || !$config['has_api_key']) {
                Log::error('Configuração da IA inválida:', $config);
                return response()->json(['error' => 'IA não configurada.'], 400);
            }
            $user = Auth::user();
            if (!$user || !$user->currentCompany) {
                Log::warning('Usuário sem empresa associada', ['user_id' => $user?->id]);
                return response()->json(['error' => 'Empresa não associada.'], 400);
            }
            // Loop de fallback de provedores
            $configs = $this->aiConfigService->getAllAIConfigs();
            $errors = [];
            foreach ($configs as $cfg) {
                try {
                    Log::info('Tentando IA:', $cfg);
                    $endpoint = $this->getEndpoint($cfg['provider'], $cfg['model']);
                    $headers = $this->getHeaders($cfg['provider'], $cfg['api_key']);
                    $aiService = new \App\Services\AIService(
                        $cfg['provider'],
                        $cfg['model'],
                        $cfg['api_key'],
                        null, // endpoint
                        $cfg['system_prompt'] ?? null,
                        $cfg['chat_prompt'] ?? null,
                        $cfg['import_prompt'] ?? null,
                        null, // replicateSetting
                        'chat' // promptType
                    );
                    $payload = $this->getPayload($cfg['provider'], $cfg['model'], $aiService->getSystemPrompt(), $request->message);
                    $response = Http::withHeaders($headers)->timeout(30)->post($endpoint, $payload);
                    if ($response->successful()) {
                        $answer = $this->extractAnswer($cfg['provider'], $response->json());
                        if ($answer) {
                            return response()->json(['answer' => $answer]);
                        }
                    }
                    $status = $response->status();
                    if ($status === 429) {
                        $errors[] = 'Rate limit em ' . $cfg['provider'];
                        continue;
                    }
                    $errors[] = "Erro {$status} em {$cfg['provider']}";
                } catch (\Exception $inner) {
                    Log::warning('Erro no provedor ' . $cfg['provider'], ['error' => $inner->getMessage()]);
                    $errors[] = 'Falha ' . $cfg['provider'];
                }
            }
            return response()->json(['error' => 'Nenhum provedor disponível: ' . implode('; ', $errors)], 503);
        } catch (\Exception $e) {
            Log::error('Erro geral ChatbotController@ask', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Erro interno.'], 500);
        }
    }

    /**
     * Retorna o endpoint da API baseado no provedor
     */
    private function getEndpoint($provider, $model)
    {
        switch (strtolower($provider)) {
            case 'openrouter':
                return 'https://openrouter.ai/api/v1/chat/completions';
            case 'google':
            case 'gemini':
                return 'https://generativelanguage.googleapis.com/v1/models/' . $model . ':generateContent';
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
     * Prepara payload para chat de texto (sem exigir JSON na saída).
     */
    private function getChatPayload($provider, $model, $message)
    {
        // Obter configuração da IA
        $config = $this->aiConfigService->getAIConfig();
        
        // Usar o AIService com o tipo de prompt 'chat'
        $aiService = new \App\Services\AIService(
            $provider,
            $model,
            $config['api_key'],
            null, // endpoint
            $config['system_prompt'] ?? null,
            $config['chat_prompt'] ?? null,
            $config['import_prompt'] ?? null,
            null, // replicateSetting
            'chat' // promptType
        );
        
        // Obter o prompt de chat adequado
        $chatPrompt = $aiService->getSystemPrompt();
        
        // Log do prompt usado
        Log::info('Usando prompt de chat:', [
            'provider' => $provider,
            'model' => $model,
            'prompt_length' => strlen($chatPrompt),
            'prompt_preview' => substr($chatPrompt, 0, 100) . (strlen($chatPrompt) > 100 ? '...' : '')
        ]);
        
        return $this->getPayload($provider, $model, $chatPrompt, $message);
    }

    /**
     * Retorna o payload da requisição baseado no provedor
     */
    private function getPayload($provider, $model, $systemPrompt, $message)
    {
        // Obtém dados financeiros para incluir no contexto
        $financialContext = $this->getFinancialContext();
        
        // Se não houver prompt configurado, usa um padrão com instruções de formatação
        $basePrompt = $systemPrompt ?: 'Você é um assistente financeiro inteligente. Responda em português, utilizando Markdown para formatação. Quando voltar dados JSON, coloque-os em um bloco de código com ```json ...```.';
        // Adiciona o contexto financeiro ao prompt do sistema
        $enhancedSystemPrompt = $basePrompt . "\n\nContexto Financeiro Atual:\n" . $financialContext;

        // Se a mensagem do usuário for um JSON de transações, adicionar instruções específicas
        $decoded = json_decode($message, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $message = "As seguintes transações foram fornecidas em JSON:\n" . json_encode($decoded, JSON_UNESCAPED_UNICODE) .
                       "\n\nPor favor, analise estas transações e informe qual categoria de despesa acumulou o maior valor, incluindo o valor total dessa categoria.";
        }

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

    /**
     * Endpoint para upload de extrato via chatbot
     */
    public function uploadStatement(Request $request)
    {
        $request->validate([
            'statement_file' => 'required|file|mimes:csv,ofx,qfx,qif,pdf,txt,xls,xlsx|max:10240',
            'account_id' => 'required|exists:accounts,id'
        ]);

        // Verifica conta bancária associada
        $accountId = $request->input('account_id');
        // TODO: validar se a conta pertence ao usuário autenticado

        $file = $request->file('statement_file');
        $service = new StatementImportService();
        $result = $service->importAndAnalyze($file, $accountId);

        return response()->json($result);
    }

    /**
     * Endpoint para processar e analisar o extrato enviado
     */
    public function processStatement(Request $request)
    {
        $request->validate([
            'file_path'  => 'required|string',
            'account_id' => 'required|integer',
            'extension'  => 'nullable|string'
        ]);

        $filePath  = $request->input('file_path');
        $accountId = $request->input('account_id');
        $extension = $request->input('extension', pathinfo($filePath, PATHINFO_EXTENSION));

        // Extrair, analisar e categorizar transações
        $tempImport      = new TempStatementImportController();
        $transactions    = $tempImport->extractTransactions($filePath, $extension);
        $analysis        = $tempImport->analyzeTransactionsWithAI($transactions);
        $categorized     = $tempImport->applyCategorizationToTransactions($transactions, $analysis);

        // Montar sub-request para salvar as transações
        $saveRequest = Request::create('', 'POST', [
            'account_id'   => $accountId,
            'file_path'    => $filePath,
            'transactions' => $categorized
        ]);
        $saveRequest->headers->set('Accept', 'application/json');

        // Executa salvamento e retorna resposta
        return $tempImport->saveTransactions($saveRequest);
    }
}