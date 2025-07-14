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
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;
use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use Carbon\Carbon;

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
            $user = Auth::user();

            if (!$message) {
                return response()->json([
                    'success' => false,
                    'error' => 'Mensagem é obrigatória'
                ], 400);
            }

            // Usar o novo serviço de chatbot financeiro
            $financialChatbotService = new \App\Services\FinancialChatbotService($this->aiConfigService);
            $result = $financialChatbotService->processMessage($message, $user);

            if ($result['success']) {
                // Adicionar ao histórico da sessão
                $history = session('chat_history', []);
                $history[] = ['role' => 'user', 'content' => $message];
                $history[] = ['role' => 'assistant', 'content' => $result['response']['text']];

                // Limitar o histórico às últimas 10 mensagens
                if (count($history) > 10) {
                    $history = array_slice($history, -10);
                }
                session(['chat_history' => $history]);

                return response()->json([
                    'success' => true,
                    'answer' => $result['response']['text'],
                    'intent' => $result['intent'],
                    'confidence' => $result['response']['confidence'] ?? 0,
                    'data' => $result['response']['data'] ?? null,
                    'data_sources' => $result['data_used'] ?? []
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'],
                    'debug' => $result['debug'] ?? null
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Erro ao processar mensagem do chatbot', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'message' => $request->input('message'),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Desculpe, ocorreu um erro ao processar sua mensagem. Tente novamente.',
                'debug' => $e->getMessage()
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
        
        // Obter o prompt do sistema a partir da configuração
        $aiConfig = $this->aiConfigService->getAIConfig($provider);
        $systemPrompt = $aiConfig['system_prompt'] ?? $aiConfig['chat_prompt'] ?? '';
        
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

    /**
     * Processa mensagem do chatbot usando o novo sistema financeiro
     */
    public function processMessage(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:1000'
        ]);

        $user = Auth::user();
        $message = trim($request->message);

        try {
            // Usar o novo serviço de chatbot financeiro
            $financialChatbotService = new \App\Services\FinancialChatbotService($this->aiConfigService);
            $result = $financialChatbotService->processMessage($message, $user);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'answer' => $result['response']['text'],
                    'intent' => $result['intent'],
                    'confidence' => $result['response']['confidence'] ?? 0,
                    'data' => $result['response']['data'] ?? null,
                    'data_sources' => $result['data_used'] ?? []
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'],
                    'debug' => $result['debug'] ?? null
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Erro ao processar mensagem do chatbot', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'message' => $message,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Desculpe, ocorreu um erro ao processar sua mensagem. Tente novamente.',
                'debug' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Analisa a intenção da mensagem do usuário
     */
    private function analyzeIntent(string $message): string
    {
        // Palavras-chave para diferentes intenções
        $intents = [
            'saldo' => ['saldo', 'quanto tenho', 'dinheiro', 'valor', 'total'],
            'receitas' => ['receita', 'ganho', 'entrada', 'recebimento', 'renda'],
            'despesas' => ['despesa', 'gasto', 'saída', 'pagamento', 'custo'],
            'transferencias' => ['transferencia', 'transferir', 'enviar', 'mover'],
            'categorias' => ['categoria', 'classificação', 'tipo'],
            'contas' => ['conta', 'banco', 'carteira'],
            'relatorios' => ['relatório', 'relatorio', 'gráfico', 'grafico', 'análise'],
            'periodo' => ['mês', 'mes', 'ano', 'semana', 'hoje', 'ontem'],
            'ajuda' => ['ajuda', 'help', 'como', 'o que', 'onde']
        ];

        foreach ($intents as $intent => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($message, $keyword) !== false) {
                    return $intent;
                }
            }
        }

        return 'geral';
    }

    /**
     * Gera resposta baseada na intenção e dados do usuário
     */
    private function generateResponse(string $intent, string $message, User $user): string
    {
        switch ($intent) {
            case 'saldo':
                return $this->getSaldoInfo($user);
            
            case 'receitas':
                return $this->getReceitasInfo($user, $message);
            
            case 'despesas':
                return $this->getDespesasInfo($user, $message);
            
            case 'transferencias':
                return $this->getTransferenciasInfo($user);
            
            case 'categorias':
                return $this->getCategoriasInfo($user);
            
            case 'contas':
                return $this->getContasInfo($user);
            
            case 'relatorios':
                return $this->getRelatoriosInfo($user);
            
            case 'ajuda':
                return $this->getAjudaInfo();
            
            default:
                return $this->getRespostaGeral($message, $user);
        }
    }

    /**
     * Retorna informações sobre saldos das contas
     */
    private function getSaldoInfo(User $user): string
    {
        $accounts = Account::where('user_id', $user->id)
            ->where('active', true)
            ->get();

        if ($accounts->isEmpty()) {
            return "Você ainda não possui contas cadastradas. Que tal criar sua primeira conta em 'Contas' no menu?";
        }

        $totalSaldo = $accounts->sum('balance');
        $response = "💰 **Resumo dos seus saldos:**\n\n";
        
        foreach ($accounts as $account) {
            $response .= "• {$account->name}: R$ " . number_format($account->balance, 2, ',', '.') . "\n";
        }
        
        $response .= "\n**Total geral: R$ " . number_format($totalSaldo, 2, ',', '.') . "**";
        
        return $response;
    }

    /**
     * Retorna informações sobre receitas
     */
    private function getReceitasInfo(User $user, string $message): string
    {
        $periodo = $this->extractPeriodo($message);
        $query = Transaction::where('user_id', $user->id)
            ->where('type', 'income');

        if ($periodo) {
            $query = $this->applyPeriodoFilter($query, $periodo);
        } else {
            $query->whereMonth('date', now()->month)
                  ->whereYear('date', now()->year);
        }

        $receitas = $query->with('category')->get();
        $total = $receitas->sum('amount');

        if ($receitas->isEmpty()) {
            return "📈 Você não possui receitas registradas" . ($periodo ? " no período solicitado" : " neste mês") . ".";
        }

        $response = "📈 **Suas receitas" . ($periodo ? " no período" : " deste mês") . ":**\n\n";
        $response .= "**Total: R$ " . number_format($total, 2, ',', '.') . "**\n\n";
        
        $receitasPorCategoria = $receitas->groupBy('category.name');
        foreach ($receitasPorCategoria as $categoria => $transacoes) {
            $valorCategoria = $transacoes->sum('amount');
            $response .= "• {$categoria}: R$ " . number_format($valorCategoria, 2, ',', '.') . "\n";
        }

        return $response;
    }

    /**
     * Retorna informações sobre despesas
     */
    private function getDespesasInfo(User $user, string $message): string
    {
        $periodo = $this->extractPeriodo($message);
        $query = Transaction::where('user_id', $user->id)
            ->where('type', 'expense');

        if ($periodo) {
            $query = $this->applyPeriodoFilter($query, $periodo);
        } else {
            $query->whereMonth('date', now()->month)
                  ->whereYear('date', now()->year);
        }

        $despesas = $query->with('category')->get();
        $total = $despesas->sum('amount');

        if ($despesas->isEmpty()) {
            return "📉 Você não possui despesas registradas" . ($periodo ? " no período solicitado" : " neste mês") . ".";
        }

        $response = "📉 **Suas despesas" . ($periodo ? " no período" : " deste mês") . ":**\n\n";
        $response .= "**Total: R$ " . number_format($total, 2, ',', '.') . "**\n\n";
        
        $despesasPorCategoria = $despesas->groupBy('category.name');
        foreach ($despesasPorCategoria as $categoria => $transacoes) {
            $valorCategoria = $transacoes->sum('amount');
            $response .= "• {$categoria}: R$ " . number_format($valorCategoria, 2, ',', '.') . "\n";
        }

        return $response;
    }

    /**
     * Retorna informações sobre transferências
     */
    private function getTransferenciasInfo(User $user): string
    {
        $transferencias = Transaction::where('user_id', $user->id)
            ->where('type', 'transfer')
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->with(['account', 'transferToAccount'])
            ->get();

        if ($transferencias->isEmpty()) {
            return "🔄 Você não realizou transferências neste mês. Para fazer uma transferência, acesse 'Transações' → 'Nova Transferência'.";
        }

        $total = $transferencias->sum('amount');
        $response = "🔄 **Transferências deste mês:**\n\n";
        $response .= "**Total transferido: R$ " . number_format($total, 2, ',', '.') . "**\n\n";
        
        foreach ($transferencias->take(5) as $transferencia) {
            $de = $transferencia->account->name ?? 'N/A';
            $para = $transferencia->transferToAccount->name ?? 'N/A';
            $valor = number_format($transferencia->amount, 2, ',', '.');
            $data = $transferencia->date->format('d/m');
            
            $response .= "• {$data}: {$de} → {$para} - R$ {$valor}\n";
        }

        return $response;
    }

    /**
     * Retorna informações sobre categorias
     */
    private function getCategoriasInfo(User $user): string
    {
        $categorias = Category::where('user_id', $user->id)
            ->orWhereNull('user_id')
            ->get()
            ->groupBy('type');

        $response = "🏷️ **Suas categorias:**\n\n";
        
        if ($categorias->has('income')) {
            $response .= "**📈 Receitas:**\n";
            foreach ($categorias['income'] as $categoria) {
                $response .= "• {$categoria->name}\n";
            }
            $response .= "\n";
        }
        
        if ($categorias->has('expense')) {
            $response .= "**📉 Despesas:**\n";
            foreach ($categorias['expense'] as $categoria) {
                $response .= "• {$categoria->name}\n";
            }
        }

        $response .= "\nPara gerenciar categorias, acesse o menu 'Categorias'.";
        
        return $response;
    }

    /**
     * Retorna informações sobre contas
     */
    private function getContasInfo(User $user): string
    {
        $contas = Account::where('user_id', $user->id)->get();

        if ($contas->isEmpty()) {
            return "🏦 Você ainda não possui contas cadastradas. Crie sua primeira conta no menu 'Contas'.";
        }

        $response = "🏦 **Suas contas:**\n\n";
        
        foreach ($contas as $conta) {
            $status = $conta->active ? "✅" : "❌";
            $saldo = number_format($conta->balance, 2, ',', '.');
            $response .= "• {$status} {$conta->name}: R$ {$saldo}\n";
        }

        return $response;
    }

    /**
     * Retorna informações sobre relatórios
     */
    private function getRelatoriosInfo(User $user): string
    {
        $mesAtual = now();
        $receitasMes = Transaction::where('user_id', $user->id)
            ->where('type', 'income')
            ->whereMonth('date', $mesAtual->month)
            ->whereYear('date', $mesAtual->year)
            ->sum('amount');

        $despesasMes = Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereMonth('date', $mesAtual->month)
            ->whereYear('date', $mesAtual->year)
            ->sum('amount');

        $saldo = $receitasMes - $despesasMes;
        $mesNome = $mesAtual->format('F/Y');

        $response = "📊 **Relatório de {$mesNome}:**\n\n";
        $response .= "📈 Receitas: R$ " . number_format($receitasMes, 2, ',', '.') . "\n";
        $response .= "📉 Despesas: R$ " . number_format($despesasMes, 2, ',', '.') . "\n";
        $response .= "💰 Saldo: R$ " . number_format($saldo, 2, ',', '.') . "\n\n";
        
        if ($saldo > 0) {
            $response .= "🎉 Parabéns! Você teve um saldo positivo este mês!";
        } elseif ($saldo < 0) {
            $response .= "⚠️ Atenção: Suas despesas superaram as receitas este mês.";
        } else {
            $response .= "⚖️ Suas receitas e despesas estão equilibradas.";
        }

        return $response;
    }

    /**
     * Retorna informações de ajuda
     */
    private function getAjudaInfo(): string
    {
        return "🤖 **Como posso ajudá-lo:**\n\n" .
               "• **Saldos**: 'Qual meu saldo?' ou 'Quanto tenho?'\n" .
               "• **Receitas**: 'Minhas receitas' ou 'Quanto ganhei?'\n" .
               "• **Despesas**: 'Meus gastos' ou 'Quanto gastei?'\n" .
               "• **Transferências**: 'Minhas transferências'\n" .
               "• **Categorias**: 'Minhas categorias'\n" .
               "• **Contas**: 'Minhas contas bancárias'\n" .
               "• **Relatórios**: 'Relatório do mês'\n\n" .
               "Você também pode especificar períodos como 'receitas do mês passado' ou 'gastos de hoje'.";
    }

    /**
     * Resposta geral para mensagens não categorizadas
     */
    private function getRespostaGeral(string $message, User $user): string
    {
        $responses = [
            "Entendi! Posso ajudá-lo com informações sobre suas finanças. Pergunte sobre saldos, receitas, despesas ou categorias.",
            "Estou aqui para ajudar com suas questões financeiras. O que você gostaria de saber?",
            "Posso fornecer informações detalhadas sobre suas transações e contas. Como posso ajudar?",
            "Tenho acesso a todos os seus dados financeiros. Pergunte sobre qualquer aspecto das suas finanças!"
        ];

        return $responses[array_rand($responses)];
    }

    /**
     * Extrai período da mensagem (hoje, ontem, mês passado, etc.)
     */
    private function extractPeriodo(string $message): ?string
    {
        if (strpos($message, 'hoje') !== false) return 'hoje';
        if (strpos($message, 'ontem') !== false) return 'ontem';
        if (strpos($message, 'semana') !== false) return 'semana';
        if (strpos($message, 'mês passado') !== false || strpos($message, 'mes passado') !== false) return 'mes_passado';
        if (strpos($message, 'ano') !== false) return 'ano';
        
        return null;
    }

    /**
     * Aplica filtro de período na query
     */
    private function applyPeriodoFilter($query, string $periodo)
    {
        switch ($periodo) {
            case 'hoje':
                return $query->whereDate('date', today());
            
            case 'ontem':
                return $query->whereDate('date', yesterday());
            
            case 'semana':
                return $query->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()]);
            
            case 'mes_passado':
                return $query->whereMonth('date', now()->subMonth()->month)
                            ->whereYear('date', now()->subMonth()->year);
            
            case 'ano':
                return $query->whereYear('date', now()->year);
            
            default:
                return $query;
        }
    }
}