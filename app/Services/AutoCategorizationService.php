<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

class AutoCategorizationService
{
    /**
     * Tenta categorizar automaticamente as transações com base no histórico
     * 
     * @param array $transactions Transações a serem categorizadas
     * @param int $userId ID do usuário
     * @return array Transações com categorias sugeridas
     */
    public function categorizeTransactions(array $transactions, int $userId): array
    {
        Log::info('Iniciando categorização automática', [
            'transactions_count' => count($transactions),
            'user_id' => $userId
        ]);

        // Buscar categorias do usuário
        $userCategories = Category::where('user_id', $userId)
            ->orderBy('name')
            ->get();

        // Buscar histórico de transações para aprendizado
        $historicalTransactions = Transaction::whereHas('account', function($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->whereNotNull('category_id')
            ->with('category')
            ->orderBy('created_at', 'desc')
            ->limit(1000) // Últimas 1000 transações para análise
            ->get();

        Log::info('Dados carregados para categorização', [
            'user_categories' => $userCategories->count(),
            'historical_transactions' => $historicalTransactions->count()
        ]);

        $categorizedTransactions = [];
        $categorizationStats = [
            'auto_categorized' => 0,
            'no_match' => 0,
            'multiple_matches' => 0
        ];

        foreach ($transactions as $transaction) {
            $suggestedCategory = $this->findBestCategoryMatch(
                $transaction,
                $userCategories,
                $historicalTransactions
            );

            $categorizedTransaction = array_merge($transaction, [
                'suggested_category_id' => $suggestedCategory['category_id'] ?? null,
                'suggested_category_name' => $suggestedCategory['category_name'] ?? null,
                'categorization_confidence' => $suggestedCategory['confidence'] ?? 0,
                'categorization_method' => $suggestedCategory['method'] ?? 'none',
                'needs_manual_category' => $suggestedCategory['category_id'] === null
            ]);

            if ($suggestedCategory['category_id']) {
                $categorizationStats['auto_categorized']++;
            } else {
                $categorizationStats['no_match']++;
            }

            $categorizedTransactions[] = $categorizedTransaction;
        }

        Log::info('Categorização automática concluída', $categorizationStats);

        return $categorizedTransactions;
    }

    /**
     * Encontra a melhor categoria para uma transação
     */
    private function findBestCategoryMatch(array $transaction, $userCategories, $historicalTransactions): array
    {
        $description = $transaction['description'] ?? '';
        $amount = $this->parseAmount($transaction['amount']);
        $type = $transaction['type'] ?? 'expense'; // Usar tipo da transação
        
        // Método 1: Correspondência exata no histórico
        $exactMatch = $this->findExactHistoricalMatch($description, $historicalTransactions);
        if ($exactMatch) {
            return [
                'category_id' => $exactMatch->category_id,
                'category_name' => $exactMatch->category->name,
                'confidence' => 0.95,
                'method' => 'exact_historical_match'
            ];
        }

        // Método 2: Correspondência por similaridade no histórico
        $similarMatch = $this->findSimilarHistoricalMatch($description, $historicalTransactions);
        if ($similarMatch && $similarMatch['confidence'] >= 0.8) {
            return [
                'category_id' => $similarMatch['transaction']->category_id,
                'category_name' => $similarMatch['transaction']->category->name,
                'confidence' => $similarMatch['confidence'],
                'method' => 'similar_historical_match'
            ];
        }

        // Método 3: Correspondência por palavras-chave
        $keywordMatch = $this->findKeywordMatch($description, $type, $userCategories);
        if ($keywordMatch) {
            return [
                'category_id' => $keywordMatch['category']->id,
                'category_name' => $keywordMatch['category']->name,
                'confidence' => $keywordMatch['confidence'],
                'method' => 'keyword_match'
            ];
        }

        // Método 4: Categoria padrão baseada no tipo (receita/despesa)
        $defaultCategory = $this->getDefaultCategory($type, $userCategories);
        if ($defaultCategory) {
            return [
                'category_id' => $defaultCategory->id,
                'category_name' => $defaultCategory->name,
                'confidence' => 0.3,
                'method' => 'default_by_type'
            ];
        }

        return [
            'category_id' => null,
            'category_name' => null,
            'confidence' => 0,
            'method' => 'none'
        ];
    }

    /**
     * Busca correspondência exata no histórico
     */
    private function findExactHistoricalMatch(string $description, $historicalTransactions): ?Transaction
    {
        $normalizedDescription = $this->normalizeDescription($description);
        
        foreach ($historicalTransactions as $historical) {
            $historicalNormalized = $this->normalizeDescription($historical->description);
            
            if ($normalizedDescription === $historicalNormalized) {
                return $historical;
            }
        }

        return null;
    }

    /**
     * Busca correspondência por similaridade no histórico
     */
    private function findSimilarHistoricalMatch(string $description, $historicalTransactions): ?array
    {
        $bestMatch = null;
        $bestSimilarity = 0;

        $normalizedDescription = $this->normalizeDescription($description);

        foreach ($historicalTransactions as $historical) {
            $historicalNormalized = $this->normalizeDescription($historical->description);
            
            $similarity = $this->calculateSimilarity($normalizedDescription, $historicalNormalized);
            
            if ($similarity > $bestSimilarity && $similarity >= 0.7) {
                $bestSimilarity = $similarity;
                $bestMatch = $historical;
            }
        }

        return $bestMatch ? [
            'transaction' => $bestMatch,
            'confidence' => $bestSimilarity
        ] : null;
    }

    /**
     * Busca correspondência por palavras-chave
     */
    private function findKeywordMatch(string $description, string $type, $userCategories): ?array
    {
        $keywords = $this->getKeywordMappings();
        $normalizedDescription = $this->normalizeDescription($description);
        
        $bestMatch = null;
        $bestScore = 0;

        foreach ($keywords as $keyword => $categoryInfo) {
            if (strpos($normalizedDescription, $keyword) !== false) {
                // Verificar se o usuário tem uma categoria compatível
                $matchingCategory = $userCategories->first(function($category) use ($categoryInfo, $type) {
                    return in_array(strtolower($category->name), $categoryInfo['categories']) &&
                           $category->type === $type;
                });

                if ($matchingCategory) {
                    $score = $categoryInfo['confidence'];
                    if ($score > $bestScore) {
                        $bestScore = $score;
                        $bestMatch = $matchingCategory;
                    }
                }
            }
        }

        return $bestMatch ? [
            'category' => $bestMatch,
            'confidence' => $bestScore
        ] : null;
    }

    /**
     * Obtém categoria padrão baseada no tipo
     */
    private function getDefaultCategory(string $type, $userCategories): ?Category
    {
        $defaultNames = $type === 'income' ? ['outros', 'receita', 'diversos'] : ['outros', 'despesa', 'diversos'];

        foreach ($defaultNames as $name) {
            $category = $userCategories->first(function($cat) use ($name, $type) {
                return strtolower($cat->name) === $name && $cat->type === $type;
            });
            
            if ($category) {
                return $category;
            }
        }

        return null;
    }

    /**
     * Mapeamento de palavras-chave para categorias
     */
    private function getKeywordMappings(): array
    {
        return [
            // Alimentação
            'restaurante' => ['categories' => ['alimentação', 'comida', 'restaurante'], 'confidence' => 0.9],
            'lanchonete' => ['categories' => ['alimentação', 'comida', 'lanche'], 'confidence' => 0.9],
            'supermercado' => ['categories' => ['alimentação', 'mercado', 'compras'], 'confidence' => 0.85],
            'padaria' => ['categories' => ['alimentação', 'comida', 'padaria'], 'confidence' => 0.85],
            
            // Transporte
            'uber' => ['categories' => ['transporte', 'uber', 'mobilidade'], 'confidence' => 0.95],
            'taxi' => ['categories' => ['transporte', 'taxi', 'mobilidade'], 'confidence' => 0.9],
            'combustivel' => ['categories' => ['transporte', 'combustível', 'gasolina'], 'confidence' => 0.9],
            'posto' => ['categories' => ['transporte', 'combustível', 'gasolina'], 'confidence' => 0.8],
            
            // Serviços
            'netflix' => ['categories' => ['entretenimento', 'streaming', 'assinatura'], 'confidence' => 0.95],
            'spotify' => ['categories' => ['entretenimento', 'música', 'assinatura'], 'confidence' => 0.95],
            'internet' => ['categories' => ['serviços', 'internet', 'telecomunicações'], 'confidence' => 0.9],
            'telefone' => ['categories' => ['serviços', 'telefone', 'telecomunicações'], 'confidence' => 0.9],
            
            // Saúde
            'farmacia' => ['categories' => ['saúde', 'farmácia', 'medicamentos'], 'confidence' => 0.9],
            'hospital' => ['categories' => ['saúde', 'hospital', 'médico'], 'confidence' => 0.9],
            'clinica' => ['categories' => ['saúde', 'clínica', 'médico'], 'confidence' => 0.85],
            
            // Salário e renda
            'salario' => ['categories' => ['salário', 'renda', 'trabalho'], 'confidence' => 0.95],
            'pagamento' => ['categories' => ['salário', 'renda', 'pagamento'], 'confidence' => 0.7],
        ];
    }

    /**
     * Normaliza descrição para comparação
     */
    private function normalizeDescription(string $description): string
    {
        $normalized = strtolower($description);
        $normalized = preg_replace('/[^a-z0-9\s]/', '', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        return trim($normalized);
    }

    /**
     * Calcula similaridade entre strings
     */
    private function calculateSimilarity(string $str1, string $str2): float
    {
        if (empty($str1) || empty($str2)) {
            return 0.0;
        }

        $maxLen = max(strlen($str1), strlen($str2));
        if ($maxLen === 0) {
            return 1.0;
        }

        $distance = levenshtein($str1, $str2);
        return 1.0 - ($distance / $maxLen);
    }

    /**
     * Converte valor para float
     */
    private function parseAmount($amount): float
    {
        if (is_numeric($amount)) {
            return (float) $amount;
        }

        $cleaned = preg_replace('/[^\d,.-]/', '', $amount);
        $cleaned = str_replace(',', '.', $cleaned);
        
        return (float) $cleaned;
    }
}
