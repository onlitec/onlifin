<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinancialChatbotService
{
    protected $aiConfigService;
    protected $aiService;
    
    public function __construct(AIConfigService $aiConfigService)
    {
        $this->aiConfigService = $aiConfigService;
    }
    
    /**
     * Processa uma mensagem do usuário e retorna uma resposta inteligente
     */
    public function processMessage(string $message, User $user): array
    {
        try {
            // 1. Analisar a intenção da mensagem
            $intent = $this->analyzeIntent($message);
            
            // 2. Coletar dados financeiros relevantes
            $financialData = $this->getFinancialData($user, $intent);
            
            // 3. Preparar contexto para a IA
            $context = $this->prepareContext($financialData, $intent, $message);
            
            // 4. Obter configuração da IA do chatbot
            $chatbotConfig = $this->getChatbotConfig();
            
            // 5. Gerar resposta usando IA
            $response = $this->generateAIResponse($context, $chatbotConfig);
            
            // 6. Processar e formatar resposta
            $formattedResponse = $this->formatResponse($response, $intent, $financialData);
            
            return [
                'success' => true,
                'response' => $formattedResponse,
                'intent' => $intent,
                'data_used' => array_keys($financialData)
            ];
            
        } catch (\Exception $e) {
            Log::error('Erro no FinancialChatbotService', [
                'message' => $e->getMessage(),
                'user_id' => $user->id,
                'user_message' => $message
            ]);
            
            return [
                'success' => false,
                'error' => 'Desculpe, ocorreu um erro ao processar sua mensagem. Tente novamente.',
                'debug' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Analisa a intenção da mensagem do usuário
     */
    private function analyzeIntent(string $message): array
    {
        $message = strtolower($message);
        
        $intents = [
            'balance' => ['saldo', 'balanço', 'quanto tenho', 'dinheiro disponível'],
            'expenses' => ['gastos', 'despesas', 'gastei', 'saídas', 'débitos'],
            'income' => ['receitas', 'ganhos', 'entradas', 'recebimentos', 'créditos'],
            'transactions' => ['transações', 'movimentações', 'histórico', 'extrato'],
            'categories' => ['categorias', 'tipos de gasto', 'classificação'],
            'predictions' => ['previsão', 'projeção', 'tendência', 'futuro', 'estimativa', 'previsões', 'próximo mês', 'próxima semana'],
            'analysis' => ['análise', 'relatório', 'resumo', 'insights', 'comparação'],
            'accounts' => ['contas', 'bancos', 'cartões'],
            'budget' => ['orçamento', 'planejamento', 'meta', 'limite'],
            'help' => ['ajuda', 'como', 'o que você pode', 'funcionalidades']
        ];
        
        $detectedIntents = [];
        $confidence = 0;
        
        foreach ($intents as $intent => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($message, $keyword) !== false) {
                    $detectedIntents[] = $intent;
                    $confidence += 0.1;
                }
            }
        }
        
        // Detectar período temporal
        $period = $this->detectTimePeriod($message);
        
        return [
            'primary' => $detectedIntents[0] ?? 'general',
            'all' => array_unique($detectedIntents),
            'confidence' => min($confidence, 1.0),
            'period' => $period,
            'original_message' => $message
        ];
    }
    
    /**
     * Detecta período temporal na mensagem
     */
    private function detectTimePeriod(string $message): array
    {
        $periods = [
            'today' => ['hoje', 'dia atual', 'hoje mesmo'],
            'week' => ['semana', 'últimos 7 dias', 'esta semana', 'semana atual'],
            'month' => ['mês', 'últimos 30 dias', 'mensal', 'este mês', 'mês atual'],
            'quarter' => ['trimestre', 'últimos 3 meses', 'trimestral'],
            'year' => ['ano', 'últimos 12 meses', 'anual', 'este ano', 'ano atual'],
            'all' => ['tudo', 'todas', 'total', 'geral', 'completo', 'histórico'],
            'custom' => []
        ];

        foreach ($periods as $period => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($message, $keyword) !== false) {
                    return [
                        'type' => $period,
                        'start_date' => $this->getPeriodStartDate($period),
                        'end_date' => now()
                    ];
                }
            }
        }

        // Para saudações simples como "olá", usar período mais amplo
        $greetings = ['olá', 'oi', 'hello', 'bom dia', 'boa tarde', 'boa noite'];
        foreach ($greetings as $greeting) {
            if (strpos($message, $greeting) !== false) {
                return [
                    'type' => 'all',
                    'start_date' => now()->subYears(2), // 2 anos para garantir todas as transações
                    'end_date' => now()->addDays(30) // Incluir futuro próximo
                ];
            }
        }

        // Para previsões, usar dados históricos mais amplos
        if (strpos($message, 'previsão') !== false || strpos($message, 'próximo') !== false) {
            return [
                'type' => 'prediction',
                'start_date' => now()->subMonths(6), // 6 meses de histórico para previsões
                'end_date' => now()->addDays(30)
            ];
        }

        // Padrão: usar período mais amplo para ter dados suficientes
        return [
            'type' => 'recent',
            'start_date' => now()->subMonths(3), // 3 meses para ter dados relevantes
            'end_date' => now()
        ];
    }
    
    /**
     * Obtém data de início baseada no período
     */
    private function getPeriodStartDate(string $period): Carbon
    {
        return match($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            'year' => now()->startOfYear(),
            'all' => now()->subYears(2), // Para visão geral, 2 anos para incluir todas as transações
            'prediction' => now()->subMonths(6), // 6 meses para previsões
            'recent' => now()->subMonths(3), // 3 meses para dados recentes
            default => now()->subMonths(3) // Padrão mais amplo
        };
    }
    
    /**
     * Coleta dados financeiros relevantes baseados na intenção
     */
    private function getFinancialData(User $user, array $intent): array
    {
        $data = [];
        $period = $intent['period'];
        
        // Contas do usuário
        $accounts = Account::where('user_id', $user->id)->get();
        $data['accounts'] = $accounts->map(function($account) {
            return [
                'id' => $account->id,
                'name' => $account->name ?? 'Conta sem nome',
                'type' => $account->type ?? 'unknown',
                'balance' => (float) ($account->current_balance ?? $account->balance ?? 0)
            ];
        })->toArray();
        
        // Transações no período
        $transactions = Transaction::where('user_id', $user->id)
            ->whereBetween('date', [$period['start_date'], $period['end_date']])
            ->with(['category', 'account'])
            ->orderBy('date', 'desc')
            ->get();

        $data['transactions'] = $transactions->map(function($transaction) {
            return [
                'id' => $transaction->id,
                'description' => $transaction->description ?? 'Sem descrição',
                'amount' => (float) ($transaction->amount / 100), // Converter centavos para reais
                'amount_formatted' => 'R$ ' . number_format($transaction->amount / 100, 2, ',', '.'),
                'type' => $transaction->type ?? 'unknown',
                'date' => $transaction->date ? $transaction->date->format('Y-m-d') : now()->format('Y-m-d'),
                'category' => $transaction->category?->name ?? 'Sem categoria',
                'account' => $transaction->account?->name ?? 'Conta desconhecida'
            ];
        })->toArray();
        
        // Resumo financeiro
        $totalIncome = $transactions->where('type', 'income')->sum('amount') / 100;
        $totalExpenses = $transactions->where('type', 'expense')->sum('amount') / 100;
        $netBalance = $totalIncome - $totalExpenses;

        $data['summary'] = [
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'net_balance' => $netBalance,
            'total_income_formatted' => 'R$ ' . number_format($totalIncome, 2, ',', '.'),
            'total_expenses_formatted' => 'R$ ' . number_format($totalExpenses, 2, ',', '.'),
            'net_balance_formatted' => 'R$ ' . number_format($netBalance, 2, ',', '.'),
            'transaction_count' => $transactions->count(),
            'period' => $period['type'],
            'start_date' => $period['start_date']->format('Y-m-d'),
            'end_date' => $period['end_date']->format('Y-m-d')
        ];
        
        // Categorias mais utilizadas
        $categoryStats = $transactions->groupBy('category.name')->map(function($group) {
            return [
                'category' => $group->first()->category?->name ?? 'Sem categoria',
                'count' => $group->count(),
                'total' => $group->sum('amount'),
                'type' => $group->first()->type
            ];
        })->sortByDesc('total')->take(10)->values()->toArray();
        
        $data['category_stats'] = $categoryStats;
        
        // Tendências (comparação com período anterior)
        $previousPeriod = [
            'start_date' => $period['start_date']->copy()->sub($period['start_date']->diffInDays($period['end_date']), 'days'),
            'end_date' => $period['start_date']
        ];
        
        $previousTransactions = Transaction::where('user_id', $user->id)
            ->whereBetween('date', [$previousPeriod['start_date'], $previousPeriod['end_date']])
            ->get();
            
        $data['trends'] = [
            'income_change' => $this->calculatePercentageChange(
                $previousTransactions->where('type', 'income')->sum('amount'),
                $data['summary']['total_income']
            ),
            'expense_change' => $this->calculatePercentageChange(
                $previousTransactions->where('type', 'expense')->sum('amount'),
                $data['summary']['total_expenses']
            )
        ];

        // Adicionar previsões se a intenção for de previsão
        if ($intent['primary'] === 'predictions' || strpos($intent['original_message'], 'previsão') !== false) {
            $data['predictions'] = $this->calculatePredictions($user, $intent);
        }

        return $data;
    }
    
    /**
     * Calcula previsões baseadas em dados históricos
     */
    private function calculatePredictions(User $user, array $intent): array
    {
        // Buscar dados dos últimos 6 meses para análise de tendência
        $sixMonthsAgo = now()->subMonths(6);
        $transactions = Transaction::where('user_id', $user->id)
            ->where('date', '>=', $sixMonthsAgo)
            ->orderBy('date', 'asc')
            ->get();

        if ($transactions->isEmpty()) {
            return [
                'next_month_income_prediction' => 0,
                'next_month_expenses_prediction' => 0,
                'confidence' => 0,
                'trend' => 'insufficient_data'
            ];
        }

        // Agrupar por mês
        $monthlyData = $transactions->groupBy(function($transaction) {
            return $transaction->date->format('Y-m');
        })->map(function($monthTransactions) {
            $income = $monthTransactions->where('type', 'income')->sum('amount') / 100;
            $expenses = $monthTransactions->where('type', 'expense')->sum('amount') / 100;
            return [
                'income' => $income,
                'expenses' => $expenses,
                'net' => $income - $expenses
            ];
        });

        // Calcular médias
        $avgIncome = $monthlyData->avg('income');
        $avgExpenses = $monthlyData->avg('expenses');

        // Calcular tendência (últimos 3 meses vs 3 anteriores)
        $recentMonths = $monthlyData->take(-3);
        $previousMonths = $monthlyData->take(-6)->take(3);

        $recentAvgIncome = $recentMonths->avg('income');
        $previousAvgIncome = $previousMonths->avg('income');

        $recentAvgExpenses = $recentMonths->avg('expenses');
        $previousAvgExpenses = $previousMonths->avg('expenses');

        // Aplicar tendência à previsão
        $incomeTrend = $previousAvgIncome > 0 ? ($recentAvgIncome - $previousAvgIncome) / $previousAvgIncome : 0;
        $expensesTrend = $previousAvgExpenses > 0 ? ($recentAvgExpenses - $previousAvgExpenses) / $previousAvgExpenses : 0;

        $predictedIncome = $avgIncome * (1 + $incomeTrend * 0.5); // Aplicar 50% da tendência
        $predictedExpenses = $avgExpenses * (1 + $expensesTrend * 0.5);

        return [
            'next_month_income_prediction' => max(0, $predictedIncome),
            'next_month_expenses_prediction' => max(0, $predictedExpenses),
            'next_month_income_formatted' => 'R$ ' . number_format(max(0, $predictedIncome), 2, ',', '.'),
            'next_month_expenses_formatted' => 'R$ ' . number_format(max(0, $predictedExpenses), 2, ',', '.'),
            'historical_avg_income' => $avgIncome,
            'historical_avg_expenses' => $avgExpenses,
            'confidence' => min(100, $monthlyData->count() * 20), // 20% por mês de dados
            'trend' => $incomeTrend > 0.1 ? 'growing' : ($incomeTrend < -0.1 ? 'declining' : 'stable'),
            'months_analyzed' => $monthlyData->count()
        ];
    }

    /**
     * Calcula mudança percentual
     */
    private function calculatePercentageChange(float $previous, float $current): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return round((($current - $previous) / $previous) * 100, 2);
    }
    
    /**
     * Prepara contexto para a IA
     */
    private function prepareContext(array $financialData, array $intent, string $message): string
    {
        $context = "ASSISTENTE FINANCEIRO INTELIGENTE\n\n";
        $context .= "DADOS FINANCEIROS DO USUÁRIO:\n";
        $context .= "Período analisado: {$financialData['summary']['period']} ({$financialData['summary']['start_date']} a {$financialData['summary']['end_date']})\n\n";
        
        $context .= "RESUMO FINANCEIRO:\n";
        $context .= "- Total de receitas: R$ " . number_format($financialData['summary']['total_income'], 2, ',', '.') . "\n";
        $context .= "- Total de despesas: R$ " . number_format($financialData['summary']['total_expenses'], 2, ',', '.') . "\n";
        $context .= "- Saldo líquido: R$ " . number_format($financialData['summary']['net_balance'], 2, ',', '.') . "\n";
        $context .= "- Número de transações: {$financialData['summary']['transaction_count']}\n\n";
        
        if (!empty($financialData['accounts'])) {
            $context .= "CONTAS BANCÁRIAS:\n";
            foreach ($financialData['accounts'] as $account) {
                $context .= "- {$account['name']} ({$account['type']}): R$ " . number_format($account['balance'], 2, ',', '.') . "\n";
            }
            $context .= "\n";
        }
        
        if (!empty($financialData['category_stats'])) {
            $context .= "PRINCIPAIS CATEGORIAS:\n";
            foreach (array_slice($financialData['category_stats'], 0, 5) as $stat) {
                $context .= "- {$stat['category']}: R$ " . number_format($stat['total'], 2, ',', '.') . " ({$stat['count']} transações)\n";
            }
            $context .= "\n";
        }
        
        if (!empty($financialData['trends'])) {
            $context .= "TENDÊNCIAS (comparado ao período anterior):\n";
            $context .= "- Receitas: {$financialData['trends']['income_change']}%\n";
            $context .= "- Despesas: {$financialData['trends']['expense_change']}%\n\n";
        }

        // Adicionar previsões se disponíveis
        if (!empty($financialData['predictions'])) {
            $predictions = $financialData['predictions'];
            $context .= "PREVISÕES PARA O PRÓXIMO MÊS:\n";
            $context .= "- Receitas previstas: {$predictions['next_month_income_formatted']}\n";
            $context .= "- Despesas previstas: {$predictions['next_month_expenses_formatted']}\n";
            $context .= "- Confiança da previsão: {$predictions['confidence']}%\n";
            $context .= "- Tendência: {$predictions['trend']}\n";
            $context .= "- Baseado em {$predictions['months_analyzed']} meses de dados\n\n";
        }

        $context .= "INTENÇÃO DETECTADA: {$intent['primary']}\n";
        $context .= "MENSAGEM DO USUÁRIO: {$message}\n\n";
        
        $context .= "INSTRUÇÕES:\n";
        $context .= "- Responda de forma clara e objetiva\n";
        $context .= "- Use os dados financeiros fornecidos\n";
        $context .= "- Forneça insights úteis e acionáveis\n";
        $context .= "- Seja empático e profissional\n";
        $context .= "- Use formatação em markdown quando apropriado\n";
        $context .= "- Inclua números e percentuais relevantes\n";
        
        return $context;
    }
    
    /**
     * Obtém configuração específica do chatbot
     */
    private function getChatbotConfig(): array
    {
        // Tentar obter configuração específica do chatbot
        $chatbotConfig = $this->aiConfigService->getChatbotConfig();

        if (!$chatbotConfig || !isset($chatbotConfig['enabled']) || !$chatbotConfig['enabled']) {
            // Fallback para configuração geral de IA
            $generalConfig = $this->aiConfigService->getAIConfig();

            return [
                'provider' => $generalConfig['provider'] ?? 'openai',
                'model' => $generalConfig['model'] ?? 'gpt-3.5-turbo',
                'api_key' => $generalConfig['api_key'] ?? '',
                'system_prompt' => $this->getDefaultSystemPrompt(),
                'temperature' => 0.7,
                'max_tokens' => 1000
            ];
        }

        return [
            'provider' => $chatbotConfig['provider'],
            'model' => $chatbotConfig['model'],
            'api_key' => $chatbotConfig['api_key'],
            'system_prompt' => $chatbotConfig['system_prompt'],
            'temperature' => $chatbotConfig['temperature'] ?? 0.7,
            'max_tokens' => $chatbotConfig['max_tokens'] ?? 1000
        ];
    }
    
    /**
     * Prompt padrão do sistema para o chatbot financeiro
     */
    private function getDefaultSystemPrompt(): string
    {
        return "Você é um assistente financeiro inteligente especializado em análise de dados financeiros pessoais. " .
               "Sua função é ajudar usuários a entender suas finanças, identificar padrões de gastos, " .
               "fornecer insights sobre receitas e despesas, e sugerir melhorias na gestão financeira. " .
               "Sempre baseie suas respostas nos dados financeiros fornecidos e seja preciso com números e cálculos. " .
               "Use linguagem clara e acessível, evitando jargões técnicos desnecessários.";
    }

    /**
     * Gera resposta usando IA
     */
    private function generateAIResponse(string $context, array $config): string
    {
        try {
            // Inicializar serviço de IA com configuração do chatbot
            $aiService = new AIService(
                $config['provider'],
                $config['model'],
                $config['api_key'],
                $config['endpoint'] ?? null,
                $config['system_prompt'],
                null, // chat_prompt
                null, // import_prompt
                null, // replicateSetting
                'chat' // promptType
            );

            // Gerar resposta
            $response = $aiService->analyze($context);

            // Garantir que a resposta seja uma string
            if (is_array($response)) {
                Log::warning('AIService retornou array em vez de string', [
                    'response' => $response,
                    'provider' => $config['provider']
                ]);

                // Tentar extrair texto da resposta
                if (isset($response['text'])) {
                    return $response['text'];
                } elseif (isset($response['content'])) {
                    return $response['content'];
                } elseif (isset($response['message'])) {
                    return $response['message'];
                } else {
                    return json_encode($response);
                }
            }

            return (string) $response;

        } catch (\Exception $e) {
            Log::error('Erro ao gerar resposta da IA', [
                'error' => $e->getMessage(),
                'provider' => $config['provider'] ?? 'unknown',
                'model' => $config['model'] ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);

            return "Desculpe, não foi possível processar sua solicitação no momento. " .
                   "Verifique se a configuração da IA está correta. Erro: " . $e->getMessage();
        }
    }

    /**
     * Formata a resposta final
     */
    private function formatResponse(string $response, array $intent, array $financialData): array
    {
        $formattedResponse = [
            'text' => $response,
            'type' => 'text',
            'intent' => $intent['primary'] ?? 'general',
            'confidence' => (float) ($intent['confidence'] ?? 0.0)
        ];

        // Adicionar dados estruturados baseados na intenção
        $primaryIntent = $intent['primary'] ?? 'general';

        switch ($primaryIntent) {
            case 'balance':
                $formattedResponse['data'] = [
                    'total_balance' => (float) array_sum(array_column($financialData['accounts'] ?? [], 'balance')),
                    'accounts' => $financialData['accounts'] ?? []
                ];
                break;

            case 'expenses':
                $formattedResponse['data'] = [
                    'total_expenses' => (float) ($financialData['summary']['total_expenses'] ?? 0),
                    'top_categories' => array_slice($financialData['category_stats'] ?? [], 0, 5),
                    'trend' => (float) ($financialData['trends']['expense_change'] ?? 0)
                ];
                break;

            case 'income':
                $formattedResponse['data'] = [
                    'total_income' => (float) ($financialData['summary']['total_income'] ?? 0),
                    'trend' => (float) ($financialData['trends']['income_change'] ?? 0)
                ];
                break;

            case 'analysis':
                $formattedResponse['data'] = [
                    'summary' => $financialData['summary'] ?? [],
                    'trends' => $financialData['trends'] ?? [],
                    'top_categories' => $financialData['category_stats'] ?? []
                ];
                break;
        }

        return $formattedResponse;
    }

    /**
     * Gera previsões financeiras baseadas em dados históricos
     */
    public function generatePredictions(User $user, int $months = 3): array
    {
        try {
            // Coletar dados históricos dos últimos 12 meses
            $historicalData = Transaction::where('user_id', $user->id)
                ->where('date', '>=', now()->subYear())
                ->selectRaw('
                    YEAR(date) as year,
                    MONTH(date) as month,
                    type,
                    SUM(amount) as total,
                    COUNT(*) as count
                ')
                ->groupBy('year', 'month', 'type')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get();

            // Calcular médias mensais
            $monthlyAverages = [
                'income' => $historicalData->where('type', 'income')->avg('total') ?? 0,
                'expense' => $historicalData->where('type', 'expense')->avg('total') ?? 0
            ];

            // Calcular tendências
            $trends = $this->calculateTrends($historicalData);

            // Gerar previsões
            $predictions = [];
            for ($i = 1; $i <= $months; $i++) {
                $futureDate = now()->addMonths($i);

                $predictedIncome = $monthlyAverages['income'] * (1 + ($trends['income'] / 100));
                $predictedExpense = $monthlyAverages['expense'] * (1 + ($trends['expense'] / 100));

                $predictions[] = [
                    'month' => $futureDate->format('Y-m'),
                    'month_name' => $futureDate->translatedFormat('F Y'),
                    'predicted_income' => round($predictedIncome, 2),
                    'predicted_expense' => round($predictedExpense, 2),
                    'predicted_balance' => round($predictedIncome - $predictedExpense, 2),
                    'confidence' => $this->calculateConfidence($historicalData->count())
                ];
            }

            return [
                'success' => true,
                'predictions' => $predictions,
                'historical_averages' => $monthlyAverages,
                'trends' => $trends
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao gerar previsões', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);

            return [
                'success' => false,
                'error' => 'Não foi possível gerar previsões no momento.'
            ];
        }
    }

    /**
     * Calcula tendências baseadas em dados históricos
     */
    private function calculateTrends($historicalData): array
    {
        $incomeData = $historicalData->where('type', 'income')->sortBy(['year', 'month']);
        $expenseData = $historicalData->where('type', 'expense')->sortBy(['year', 'month']);

        return [
            'income' => $this->calculateLinearTrend($incomeData->pluck('total')->toArray()),
            'expense' => $this->calculateLinearTrend($expenseData->pluck('total')->toArray())
        ];
    }

    /**
     * Calcula tendência linear simples
     */
    private function calculateLinearTrend(array $values): float
    {
        $count = count($values);
        if ($count < 2) return 0;

        $firstHalf = array_slice($values, 0, intval($count / 2));
        $secondHalf = array_slice($values, intval($count / 2));

        $avgFirst = array_sum($firstHalf) / count($firstHalf);
        $avgSecond = array_sum($secondHalf) / count($secondHalf);

        if ($avgFirst == 0) return 0;

        return round((($avgSecond - $avgFirst) / $avgFirst) * 100, 2);
    }

    /**
     * Calcula confiança da previsão baseada na quantidade de dados
     */
    private function calculateConfidence(int $dataPoints): float
    {
        if ($dataPoints >= 12) return 0.9;
        if ($dataPoints >= 6) return 0.7;
        if ($dataPoints >= 3) return 0.5;
        return 0.3;
    }
}
