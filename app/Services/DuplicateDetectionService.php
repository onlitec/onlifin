<?php

namespace App\Services;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DuplicateDetectionService
{
    /**
     * Detecta duplicatas comparando transações do extrato com as existentes no banco
     * 
     * @param array $extractedTransactions Transações extraídas do extrato
     * @param int $accountId ID da conta
     * @return array Array com transações marcadas como duplicatas ou não
     */
    public function detectDuplicates(array $extractedTransactions, int $accountId): array
    {
        Log::info('Iniciando detecção de duplicatas', [
            'transactions_count' => count($extractedTransactions),
            'account_id' => $accountId
        ]);

        $result = [];
        
        // Buscar transações existentes dos últimos 90 dias para comparação
        $existingTransactions = Transaction::where('account_id', $accountId)
            ->where('date', '>=', Carbon::now()->subDays(90))
            ->get();

        Log::info('Transações existentes encontradas', [
            'existing_count' => $existingTransactions->count()
        ]);

        foreach ($extractedTransactions as $index => $transaction) {
            $isDuplicate = false;
            $duplicateInfo = null;

            // Converter data da transação para Carbon se necessário
            $transactionDate = $this->parseDate($transaction['date']);
            
            if (!$transactionDate) {
                Log::warning('Data inválida na transação', ['transaction' => $transaction]);
                continue;
            }

            // Verificar duplicatas
            foreach ($existingTransactions as $existing) {
                if ($this->isTransactionDuplicate($transaction, $existing, $transactionDate)) {
                    $isDuplicate = true;
                    $duplicateInfo = [
                        'existing_id' => $existing->id,
                        'existing_date' => $existing->date->format('d/m/Y'),
                        'existing_description' => $existing->description,
                        'existing_amount' => $existing->amount,
                        'match_score' => $this->calculateMatchScore($transaction, $existing, $transactionDate)
                    ];
                    break;
                }
            }

            $result[] = array_merge($transaction, [
                'original_index' => $index,
                'is_duplicate' => $isDuplicate,
                'duplicate_info' => $duplicateInfo,
                'should_import' => !$isDuplicate, // Por padrão, não importar duplicatas
                'force_import' => false // Usuário pode forçar importação
            ]);
        }

        $duplicatesCount = count(array_filter($result, fn($t) => $t['is_duplicate']));
        
        Log::info('Detecção de duplicatas concluída', [
            'total_transactions' => count($result),
            'duplicates_found' => $duplicatesCount,
            'new_transactions' => count($result) - $duplicatesCount
        ]);

        return $result;
    }

    /**
     * Verifica se uma transação é duplicata de uma existente
     */
    private function isTransactionDuplicate(array $transaction, Transaction $existing, Carbon $transactionDate): bool
    {
        // Critério 1: Data deve ser igual ou muito próxima (±2 dias)
        $dateDiff = abs($transactionDate->diffInDays($existing->date));
        if ($dateDiff > 2) {
            return false;
        }

        // Critério 2: Valor deve ser exatamente igual
        $transactionAmount = $this->parseAmount($transaction['amount']);
        if (abs($transactionAmount - $existing->amount) > 0.01) {
            return false;
        }

        // Critério 3: Descrição deve ter alta similaridade
        $similarity = $this->calculateDescriptionSimilarity(
            $transaction['description'] ?? '',
            $existing->description
        );

        return $similarity >= 0.8; // 80% de similaridade
    }

    /**
     * Calcula score de correspondência entre transações
     */
    private function calculateMatchScore(array $transaction, Transaction $existing, Carbon $transactionDate): float
    {
        $dateScore = 1.0 - (abs($transactionDate->diffInDays($existing->date)) / 7.0);
        $dateScore = max(0, min(1, $dateScore));

        $amountScore = abs($this->parseAmount($transaction['amount']) - $existing->amount) < 0.01 ? 1.0 : 0.0;

        $descriptionScore = $this->calculateDescriptionSimilarity(
            $transaction['description'] ?? '',
            $existing->description
        );

        return ($dateScore * 0.3) + ($amountScore * 0.4) + ($descriptionScore * 0.3);
    }

    /**
     * Calcula similaridade entre duas descrições
     */
    private function calculateDescriptionSimilarity(string $desc1, string $desc2): float
    {
        $desc1 = $this->normalizeDescription($desc1);
        $desc2 = $this->normalizeDescription($desc2);

        if (empty($desc1) || empty($desc2)) {
            return 0.0;
        }

        // Usar algoritmo de Levenshtein normalizado
        $maxLen = max(strlen($desc1), strlen($desc2));
        if ($maxLen === 0) {
            return 1.0;
        }

        $distance = levenshtein($desc1, $desc2);
        return 1.0 - ($distance / $maxLen);
    }

    /**
     * Normaliza descrição para comparação
     */
    private function normalizeDescription(string $description): string
    {
        // Converter para minúsculas
        $normalized = strtolower($description);
        
        // Remover caracteres especiais e espaços extras
        $normalized = preg_replace('/[^a-z0-9\s]/', '', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        
        return trim($normalized);
    }

    /**
     * Converte string de data para Carbon
     */
    private function parseDate($date): ?Carbon
    {
        if ($date instanceof Carbon) {
            return $date;
        }

        try {
            return Carbon::parse($date);
        } catch (\Exception $e) {
            Log::warning('Erro ao converter data', ['date' => $date, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Converte valor para float
     */
    private function parseAmount($amount): float
    {
        if (is_numeric($amount)) {
            return (float) $amount;
        }

        // Remover formatação monetária
        $cleaned = preg_replace('/[^\d,.-]/', '', $amount);
        $cleaned = str_replace(',', '.', $cleaned);
        
        return (float) $cleaned;
    }
}
