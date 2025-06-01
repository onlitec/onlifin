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
    /**
     * Processa a mensagem enviada pelo usuário e retorna a resposta da IA
     */
    public function ask(Request $request)
    {
        try {
            $message = $request->input('message');
            
            // Obter a configuração da IA
            $config = $this->aiConfigService->getAIConfig();
            
            if (!$config['is_configured']) {
                return response()->json([
                    'success' => false,
                    'message' => 'A IA não está configurada. Por favor, configure-a nas configurações.'
                ], 400);
            }
            
            $provider = $config['provider'];
            $model = $config['model'];
            $apiKey = $config['api_key'];
            
            // Preparar o payload para a requisição
            $payload = $this->getChatPayload($message, $provider);
            
            // Fazer a requisição para a API
            $response = Http::withHeaders($this->getHeaders($provider, $apiKey))
                ->timeout(60)
                ->post($this->getEndpoint($provider, $model), $payload);
            
            if ($response->failed()) {
                Log::error('Erro na API de IA', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'provider' => $provider
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao comunicar com a IA: ' . $response->status()
                ], 500);
            }
            
            // Extrair a resposta da IA
            $answer = $this->extractAnswer($response->json(), $provider);
            
            // Adicionar a mensagem do usuário e a resposta ao histórico de mensagens
            $history = session('chat_history', []);
            $history[] = ['role' => 'user', 'content' => $message];
            $history[] = ['role' => 'assistant', 'content' => $answer];
            
            // Limitar o histórico às últimas 10 mensagens (5 pares de perguntas/respostas)
            if (count($history) > 10) {
                $history = array_slice($history, -10);
            }
            
            // Salvar o histórico atualizado na sessão
            session(['chat_history' => $history]);
            
            return response()->json([
                'success' => true,
                'message' => $answer
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erro ao processar mensagem: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar sua mensagem: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtém o endpoint da API baseado no provedor e modelo
     */
    private function getEndpoint(string $provider, string $model): string
    {
        switch ($provider) {
            case 'openrouter':
                return 'https://openrouter.ai/api/v1/chat/completions';
                
            case 'google':
            case 'gemini':
                return 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent';
                
            case 'openai':
                return 'https://api.openai.com/v1/chat/completions';
                
            case 'anthropic':
                return 'https://api.anthropic.com/v1/messages';
                
            default:
                throw new \Exception("Provedor não suportado: {$provider}");
        }
    }
    
    /**
     * Obtém os headers para a requisição baseado no provedor
     */
    private function getHeaders(string $provider, string $apiKey): array
    {
        $headers = [
            'Content-Type' => 'application/json',
        ];
        
        switch ($provider) {
            case 'openrouter':
                $headers['Authorization'] = "Bearer {$apiKey}";
                $headers['HTTP-Referer'] = config('app.url');
                $headers['X-Title'] = config('app.name', 'OnliFinance');
                break;
                
            case 'google':
            case 'gemini':
                $headers['x-goog-api-key'] = $apiKey;
                break;
                
            case 'openai':
                $headers['Authorization'] = "Bearer {$apiKey}";
                break;
                
            case 'anthropic':
                $headers['x-api-key'] = $apiKey;
                $headers['anthropic-version'] = '2023-06-01';
                break;
                
            default:
                throw new \Exception("Provedor não suportado: {$provider}");
        }
        
        return $headers;
    }

    /**
     * Prepara o payload para a requisição de chat
     */
    private function getChatPayload(string $message, string $provider): array
    {
        // Obter o contexto financeiro
        $financialContext = $this->getFinancialContext();
        
        // Formatar o contexto financeiro como JSON para facilitar o acesso pela IA
        $formattedContext = json_encode($financialContext, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        // Obter o prompt do sistema
        $systemPrompt = $this->aiConfigService->getSystemPrompt($provider);
        
        // Aprimorar o prompt do sistema com o contexto financeiro
        $enhancedSystemPrompt = $systemPrompt . "\n\n" . 
            "CONTEXTO FINANCEIRO ATUAL:\n```json\n" . $formattedContext . "\n```\n\n" .
            "Use o contexto financeiro acima para responder às perguntas do usuário. " .
            "A data atual é " . now()->translatedFormat('d \\d\\e F \\d\\e Y') . ". " .
            "Não peça informações que já estão disponíveis no contexto.";
        
        // Recuperar o histórico de mensagens da sessão
        $history = session('chat_history', []);
        
        // Preparar o payload baseado no provedor
        switch ($provider) {
            case 'openrouter':
                return [
                    'messages' => array_merge(
                        [['role' => 'system', 'content' => $enhancedSystemPrompt]],
                        $history
                    ),
                ];
                
            case 'google':
            case 'gemini':
                // Gemini tem uma estrutura diferente para mensagens
                $geminiMessages = [];
                
                // Adicionar o prompt do sistema como primeira mensagem do usuário
                $geminiMessages[] = [
                    'role' => 'user',
                    'parts' => [['text' => $enhancedSystemPrompt]]
                ];
                
                // Adicionar uma resposta vazia do modelo para manter o padrão de alternância
                $geminiMessages[] = [
                    'role' => 'model',
                    'parts' => [['text' => 'Entendido. Estou pronto para ajudar com suas finanças.']]
                ];
                
                // Adicionar o restante do histórico no formato do Gemini
                foreach ($history as $msg) {
                    $role = $msg['role'] === 'assistant' ? 'model' : 'user';
                    $geminiMessages[] = [
                        'role' => $role,
                        'parts' => [['text' => $msg['content']]]
                    ];
                }
                
                return [
                    'contents' => $geminiMessages,
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'topP' => 0.95,
                        'topK' => 40,
                        'maxOutputTokens' => 2048,
                    ]
                ];
                
            case 'openai':
                return [
                    'messages' => array_merge(
                        [['role' => 'system', 'content' => $enhancedSystemPrompt]],
                        $history
                    ),
                    'temperature' => 0.7,
                    'max_tokens' => 2048,
                ];
                
            case 'anthropic':
                return [
                    'messages' => array_merge(
                        [['role' => 'system', 'content' => $enhancedSystemPrompt]],
                        $history
                    ),
                    'temperature' => 0.7,
                    'max_tokens' => 2048,
                ];
                
            default:
                return [
                    'messages' => array_merge(
                        [['role' => 'system', 'content' => $enhancedSystemPrompt]],
                        $history
                    ),
                ];
        }
    }

    /**
     * Obtém o contexto financeiro atual para incluir no prompt
     */
    private function getFinancialContext()
    {
        $financialDataService = new FinancialDataService();
        $transactions = $financialDataService->getRecentTransactions(10);
        $accounts = $financialDataService->getBankAccountsBalance();
        $summary = $financialDataService->getFinancialSummary();
        
        // Adiciona a data atual ao contexto com mais detalhes
        $currentDate = now();
        $locale = app()->getLocale();
        setlocale(LC_TIME, $locale . '_' . strtoupper($locale) . '.UTF-8');
        
        return [
            'date' => [
                'current_date' => $currentDate->format('d/m/Y'),
                'current_month' => $currentDate->format('m'),
                'current_month_name' => $currentDate->translatedFormat('F'),
                'current_year' => $currentDate->format('Y'),
                'current_day' => $currentDate->format('d'),
                'current_day_name' => $currentDate->translatedFormat('l'),
                'current_day_of_week' => $currentDate->format('N'),
                'current_week_of_year' => $currentDate->format('W'),
                'current_quarter' => $currentDate->format('Q'),
                'formatted_date_long' => $currentDate->translatedFormat('d \\d\\e F \\d\\e Y'),
                'timestamp' => $currentDate->timestamp,
            ],
            'transactions' => $transactions,
            'accounts' => $accounts,
            'summary' => $summary,
            'instructions' => [
                'IMPORTANTE: Você tem acesso direto aos dados financeiros do usuário através deste contexto.',
                'Você DEVE usar a data atual fornecida no contexto para todas as referências temporais.',
                'Quando o usuário perguntar sobre "mês atual", use o mês indicado em current_month_name, que é "' . $currentDate->translatedFormat('F') . '".',
                'O ano atual é ' . $currentDate->format('Y') . ' e o mês atual é ' . $currentDate->translatedFormat('F') . '.',
                'Você pode e deve acessar os dados de transações, contas e resumo financeiro diretamente.',
                'Você pode gerar análises com base nos dados fornecidos sem pedir mais informações.',
                'Não peça ao usuário dados que já estão disponíveis no contexto.',
                'Se o usuário pedir um gráfico de gastos por categoria, você deve informar que ele pode visualizar esse gráfico no relatório financeiro.',
                'Sempre que o usuário fizer perguntas sobre "este mês", "mês atual", "hoje", "agora", etc., use as informações de data fornecidas neste contexto.',
            ]
        ];
    }

    /**
     * Extrai a resposta da IA do JSON retornado pela API
     */
    private function extractAnswer(array $responseData, string $provider): string
    {
        switch ($provider) {
            case 'openrouter':
            case 'openai':
                return $responseData['choices'][0]['message']['content'] ?? 'Não foi possível obter uma resposta.';
                
            case 'google':
            case 'gemini':
                return $responseData['candidates'][0]['content']['parts'][0]['text'] ?? 'Não foi possível obter uma resposta.';
                
            case 'anthropic':
                return $responseData['content'][0]['text'] ?? 'Não foi possível obter uma resposta.';
                
            default:
                Log::warning('Provedor desconhecido para extração de resposta:', ['provider' => $provider]);
                return 'Não foi possível processar a resposta do provedor de IA.';
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