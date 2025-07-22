<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GoogleChatbotController;
use App\Models\Transaction;
use App\Models\Account;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AIController extends Controller
{
    protected $chatbotController;

    public function __construct(GoogleChatbotController $chatbotController)
    {
        $this->chatbotController = $chatbotController;
    }

    /**
     * Chat com IA financeira
     */
    public function chat(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:1000',
            'context' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Usar o controlador existente do chatbot
            $response = $this->chatbotController->ask($request);
            
            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getContent(), true);
                return response()->json([
                    'success' => true,
                    'data' => [
                        'response' => $data['response'] ?? 'Resposta não disponível',
                        'timestamp' => now()->toISOString(),
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao processar solicitação da IA'
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao comunicar com IA: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Análise financeira inteligente
     */
    public function financialAnalysis(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'period' => 'nullable|in:week,month,quarter,year',
            'analysis_type' => 'nullable|in:spending,income,categories,trends',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Parâmetros inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $period = $request->get('period', 'month');
            $analysisType = $request->get('analysis_type', 'spending');

            // Obter dados financeiros do usuário
            $financialData = $this->getFinancialData($user, $period);

            // Preparar contexto para IA
            $context = [
                'user_id' => $user->id,
                'period' => $period,
                'analysis_type' => $analysisType,
                'data' => $financialData
            ];

            // Gerar prompt específico para análise
            $prompt = $this->generateAnalysisPrompt($analysisType, $financialData, $period);

            // Fazer solicitação para IA
            $aiRequest = new Request([
                'message' => $prompt,
                'context' => $context
            ]);

            $response = $this->chatbotController->ask($aiRequest);
            
            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getContent(), true);
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'analysis' => $data['response'] ?? 'Análise não disponível',
                        'period' => $period,
                        'analysis_type' => $analysisType,
                        'financial_summary' => $financialData['summary'],
                        'timestamp' => now()->toISOString(),
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao gerar análise'
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar análise: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sugestões de categorização automática
     */
    public function categorizationSuggestions(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'type' => 'required|in:income,expense',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Obter categorias existentes do usuário
            $categories = Category::where('user_id', $user->id)
                                 ->where('type', $request->type)
                                 ->get(['id', 'name', 'type']);

            // Obter transações similares para contexto
            $similarTransactions = Transaction::where('user_id', $user->id)
                                             ->where('type', $request->type)
                                             ->where('description', 'like', '%' . $request->description . '%')
                                             ->with('category')
                                             ->limit(5)
                                             ->get();

            // Preparar prompt para IA
            $prompt = "Baseado na descrição '{$request->description}' e valor R$ " . number_format($request->amount, 2, ',', '.') . 
                     " para uma transação do tipo '{$request->type}', sugira a melhor categoria entre as disponíveis: " .
                     $categories->pluck('name')->implode(', ') . 
                     ". Considere também estas transações similares: " .
                     $similarTransactions->map(function($t) {
                         return $t->description . ' -> ' . $t->category->name;
                     })->implode(', ');

            $aiRequest = new Request([
                'message' => $prompt,
                'context' => [
                    'categories' => $categories,
                    'similar_transactions' => $similarTransactions
                ]
            ]);

            $response = $this->chatbotController->ask($aiRequest);
            
            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getContent(), true);
                
                // Tentar extrair categoria sugerida da resposta
                $suggestedCategory = $this->extractCategoryFromResponse($data['response'], $categories);
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'suggested_category' => $suggestedCategory,
                        'ai_explanation' => $data['response'],
                        'available_categories' => $categories,
                        'similar_transactions' => $similarTransactions,
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao obter sugestão'
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao obter sugestão: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Insights financeiros personalizados
     */
    public function insights(Request $request): JsonResponse
    {
        $user = $request->user();

        try {
            // Obter dados financeiros recentes
            $recentData = $this->getFinancialData($user, 'month');
            $previousData = $this->getFinancialData($user, 'month', 1); // Mês anterior

            // Calcular tendências
            $trends = $this->calculateTrends($recentData, $previousData);

            // Gerar prompt para insights
            $prompt = "Baseado nos dados financeiros do usuário, forneça insights personalizados e sugestões de melhoria. " .
                     "Dados atuais: " . json_encode($recentData['summary']) . 
                     " Tendências: " . json_encode($trends);

            $aiRequest = new Request([
                'message' => $prompt,
                'context' => [
                    'current_data' => $recentData,
                    'previous_data' => $previousData,
                    'trends' => $trends
                ]
            ]);

            $response = $this->chatbotController->ask($aiRequest);
            
            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getContent(), true);
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'insights' => $data['response'],
                        'trends' => $trends,
                        'financial_summary' => $recentData['summary'],
                        'timestamp' => now()->toISOString(),
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao gerar insights'
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar insights: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obter dados financeiros do usuário
     */
    private function getFinancialData($user, $period, $offset = 0)
    {
        $startDate = match($period) {
            'week' => now()->subWeeks($offset)->startOfWeek(),
            'month' => now()->subMonths($offset)->startOfMonth(),
            'quarter' => now()->subQuarters($offset)->startOfQuarter(),
            'year' => now()->subYears($offset)->startOfYear(),
            default => now()->subMonths($offset)->startOfMonth()
        };

        $endDate = match($period) {
            'week' => now()->subWeeks($offset)->endOfWeek(),
            'month' => now()->subMonths($offset)->endOfMonth(),
            'quarter' => now()->subQuarters($offset)->endOfQuarter(),
            'year' => now()->subYears($offset)->endOfYear(),
            default => now()->subMonths($offset)->endOfMonth()
        };

        $transactions = Transaction::with(['category', 'account'])
            ->where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $income = $transactions->where('type', 'income')->sum('amount') / 100;
        $expense = $transactions->where('type', 'expense')->sum('amount') / 100;
        $balance = $income - $expense;

        $categoryStats = $transactions->groupBy('category.name')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total' => $group->sum('amount') / 100,
                'type' => $group->first()->type
            ];
        });

        return [
            'period' => $period,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'transactions' => $transactions,
            'summary' => [
                'income' => $income,
                'expense' => $expense,
                'balance' => $balance,
                'transactions_count' => $transactions->count()
            ],
            'categories' => $categoryStats
        ];
    }

    /**
     * Gerar prompt específico para análise
     */
    private function generateAnalysisPrompt($analysisType, $financialData, $period)
    {
        $summary = $financialData['summary'];
        
        $basePrompt = "Analise os dados financeiros do período de {$period}: " .
                     "Receitas: R$ " . number_format($summary['income'], 2, ',', '.') . ", " .
                     "Despesas: R$ " . number_format($summary['expense'], 2, ',', '.') . ", " .
                     "Saldo: R$ " . number_format($summary['balance'], 2, ',', '.') . ", " .
                     "Total de transações: {$summary['transactions_count']}. ";

        return match($analysisType) {
            'spending' => $basePrompt . "Foque na análise de gastos e identifique padrões de despesas.",
            'income' => $basePrompt . "Foque na análise de receitas e oportunidades de aumento de renda.",
            'categories' => $basePrompt . "Analise a distribuição por categorias e sugira otimizações.",
            'trends' => $basePrompt . "Identifique tendências e padrões nos dados financeiros.",
            default => $basePrompt . "Forneça uma análise geral da situação financeira."
        };
    }

    /**
     * Extrair categoria sugerida da resposta da IA
     */
    private function extractCategoryFromResponse($response, $categories)
    {
        foreach ($categories as $category) {
            if (stripos($response, $category->name) !== false) {
                return $category;
            }
        }
        return null;
    }

    /**
     * Calcular tendências entre períodos
     */
    private function calculateTrends($current, $previous)
    {
        $currentSummary = $current['summary'];
        $previousSummary = $previous['summary'];

        return [
            'income_change' => $this->calculatePercentageChange($previousSummary['income'], $currentSummary['income']),
            'expense_change' => $this->calculatePercentageChange($previousSummary['expense'], $currentSummary['expense']),
            'balance_change' => $this->calculatePercentageChange($previousSummary['balance'], $currentSummary['balance']),
            'transactions_change' => $this->calculatePercentageChange($previousSummary['transactions_count'], $currentSummary['transactions_count']),
        ];
    }

    /**
     * Calcular mudança percentual
     */
    private function calculatePercentageChange($old, $new)
    {
        if ($old == 0) {
            return $new > 0 ? 100 : 0;
        }
        return (($new - $old) / abs($old)) * 100;
    }
}
