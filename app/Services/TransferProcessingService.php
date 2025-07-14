<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransferProcessingService
{
    /**
     * Processa transferências detectadas e cria transações correspondentes
     */
    public function processTransfers(array $transactions, int $currentAccountId): array
    {
        $processedTransactions = [];
        $transfersCreated = 0;
        
        foreach ($transactions as $transaction) {
            if (isset($transaction['is_transfer']) && $transaction['is_transfer']) {
                try {
                    $result = $this->processTransferTransaction($transaction, $currentAccountId);
                    $processedTransactions[] = $result['transaction'];
                    
                    if ($result['transfer_created']) {
                        $transfersCreated++;
                    }
                } catch (\Exception $e) {
                    Log::error('Erro ao processar transferência', [
                        'transaction' => $transaction,
                        'error' => $e->getMessage(),
                        'user_id' => auth()->id()
                    ]);
                    
                    // Manter transação original em caso de erro
                    $processedTransactions[] = $transaction;
                }
            } else {
                $processedTransactions[] = $transaction;
            }
        }
        
        if ($transfersCreated > 0) {
            Log::info('Transferências processadas com sucesso', [
                'transfers_created' => $transfersCreated,
                'user_id' => auth()->id()
            ]);
        }
        
        return $processedTransactions;
    }

    /**
     * Processa uma transação de transferência individual
     */
    private function processTransferTransaction(array $transaction, int $currentAccountId): array
    {
        $originAccountId = $transaction['origin_account_id'];
        $destinationAccountId = $transaction['destination_account_id'];
        
        // Verificar se conseguimos identificar ambas as contas
        if (!$originAccountId || !$destinationAccountId) {
            Log::warning('Transferência detectada mas contas não identificadas completamente', [
                'transaction_description' => $transaction['description'],
                'origin_account_id' => $originAccountId,
                'destination_account_id' => $destinationAccountId
            ]);
            
            return [
                'transaction' => $this->markAsIncompleteTransfer($transaction),
                'transfer_created' => false
            ];
        }

        // Verificar se as contas existem e pertencem ao usuário
        $originAccount = Account::where('id', $originAccountId)
            ->where('user_id', auth()->id())
            ->first();
            
        $destinationAccount = Account::where('id', $destinationAccountId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$originAccount || !$destinationAccount) {
            Log::warning('Uma ou ambas as contas da transferência não foram encontradas', [
                'origin_account_id' => $originAccountId,
                'destination_account_id' => $destinationAccountId,
                'origin_found' => !!$originAccount,
                'destination_found' => !!$destinationAccount
            ]);
            
            return [
                'transaction' => $this->markAsIncompleteTransfer($transaction),
                'transfer_created' => false
            ];
        }

        // Verificar se já existe uma transação correspondente
        if ($this->transferAlreadyExists($transaction, $originAccountId, $destinationAccountId)) {
            Log::info('Transferência já existe, pulando criação', [
                'description' => $transaction['description'],
                'amount' => $transaction['amount'],
                'date' => $transaction['date']
            ]);
            
            return [
                'transaction' => $this->markAsExistingTransfer($transaction),
                'transfer_created' => false
            ];
        }

        // Criar a transação correspondente
        return $this->createCorrespondingTransaction($transaction, $originAccount, $destinationAccount, $currentAccountId);
    }

    /**
     * Verifica se a transferência já existe
     */
    private function transferAlreadyExists(array $transaction, int $originAccountId, int $destinationAccountId): bool
    {
        $amount = abs((float) $transaction['amount']) * 100; // Converter para centavos
        $date = $transaction['date'];
        $description = $transaction['description'];
        
        // Procurar por transações com mesmo valor, data e descrição similar
        $existingTransactions = Transaction::where('user_id', auth()->id())
            ->where('amount', $amount)
            ->whereDate('date', $date)
            ->where(function($query) use ($description) {
                $query->where('description', 'LIKE', '%' . substr($description, 0, 20) . '%')
                      ->orWhere('description', $description);
            })
            ->whereIn('account_id', [$originAccountId, $destinationAccountId])
            ->count();
            
        return $existingTransactions >= 2; // Deve ter pelo menos 2 transações (origem e destino)
    }

    /**
     * Cria a transação correspondente na outra conta
     */
    private function createCorrespondingTransaction(array $transaction, Account $originAccount, Account $destinationAccount, int $currentAccountId): array
    {
        try {
            DB::beginTransaction();
            
            $amount = abs((float) $transaction['amount']) * 100; // Converter para centavos
            $transferCategory = $this->getOrCreateTransferCategory();
            
            // Determinar qual transação criar baseado na conta atual
            if ($currentAccountId == $originAccount->id) {
                // Conta atual é origem, criar transação de crédito no destino
                $correspondingTransaction = $this->createTransaction([
                    'account_id' => $destinationAccount->id,
                    'type' => 'income',
                    'amount' => $amount,
                    'date' => $transaction['date'],
                    'description' => $this->formatTransferDescription($transaction['description'], $originAccount->name, 'recebida'),
                    'category_id' => $transferCategory->id,
                    'status' => 'paid',
                    'notes' => "Transferência automática detectada de {$originAccount->name}"
                ]);
                
                $transferDirection = 'outgoing';
                $correspondingAccountName = $destinationAccount->name;
                
            } else {
                // Conta atual é destino, criar transação de débito na origem
                $correspondingTransaction = $this->createTransaction([
                    'account_id' => $originAccount->id,
                    'type' => 'expense',
                    'amount' => $amount,
                    'date' => $transaction['date'],
                    'description' => $this->formatTransferDescription($transaction['description'], $destinationAccount->name, 'enviada'),
                    'category_id' => $transferCategory->id,
                    'status' => 'paid',
                    'notes' => "Transferência automática detectada para {$destinationAccount->name}"
                ]);
                
                $transferDirection = 'incoming';
                $correspondingAccountName = $originAccount->name;
            }
            
            DB::commit();
            
            Log::info('Transação de transferência correspondente criada', [
                'original_account' => $currentAccountId,
                'corresponding_account' => $correspondingTransaction->account_id,
                'amount' => $amount,
                'direction' => $transferDirection
            ]);
            
            // Marcar transação original como processada
            $processedTransaction = $transaction;
            $processedTransaction['transfer_processed'] = true;
            $processedTransaction['corresponding_account_name'] = $correspondingAccountName;
            $processedTransaction['transfer_direction'] = $transferDirection;
            $processedTransaction['corresponding_transaction_id'] = $correspondingTransaction->id;
            
            return [
                'transaction' => $processedTransaction,
                'transfer_created' => true
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cria uma transação
     */
    private function createTransaction(array $data): Transaction
    {
        return Transaction::create(array_merge($data, [
            'user_id' => auth()->id(),
            'company_id' => auth()->user()->currentCompany?->id,
        ]));
    }

    /**
     * Obtém ou cria categoria de transferência
     */
    private function getOrCreateTransferCategory(): Category
    {
        return Category::firstOrCreate(
            [
                'user_id' => auth()->id(),
                'name' => 'Transferências',
                'type' => 'expense' // Categoria neutra
            ],
            [
                'system' => false
            ]
        );
    }

    /**
     * Formata descrição da transferência
     */
    private function formatTransferDescription(string $originalDescription, string $accountName, string $direction): string
    {
        return "Transferência {$direction} - {$accountName} - " . substr($originalDescription, 0, 50);
    }

    /**
     * Marca transação como transferência incompleta
     */
    private function markAsIncompleteTransfer(array $transaction): array
    {
        $transaction['transfer_status'] = 'incomplete';
        $transaction['transfer_note'] = 'Transferência detectada mas contas não identificadas completamente';
        return $transaction;
    }

    /**
     * Marca transação como transferência existente
     */
    private function markAsExistingTransfer(array $transaction): array
    {
        $transaction['transfer_status'] = 'existing';
        $transaction['transfer_note'] = 'Transferência correspondente já existe no sistema';
        return $transaction;
    }

    /**
     * Obtém estatísticas de transferências processadas
     */
    public function getTransferStats(array $transactions): array
    {
        $stats = [
            'total_transfers_detected' => 0,
            'transfers_processed' => 0,
            'transfers_incomplete' => 0,
            'transfers_existing' => 0,
            'high_confidence_transfers' => 0
        ];

        foreach ($transactions as $transaction) {
            if (isset($transaction['is_transfer']) && $transaction['is_transfer']) {
                $stats['total_transfers_detected']++;
                
                if (isset($transaction['transfer_confidence']) && $transaction['transfer_confidence'] > 0.8) {
                    $stats['high_confidence_transfers']++;
                }
                
                if (isset($transaction['transfer_processed']) && $transaction['transfer_processed']) {
                    $stats['transfers_processed']++;
                } elseif (isset($transaction['transfer_status'])) {
                    if ($transaction['transfer_status'] === 'incomplete') {
                        $stats['transfers_incomplete']++;
                    } elseif ($transaction['transfer_status'] === 'existing') {
                        $stats['transfers_existing']++;
                    }
                }
            }
        }

        return $stats;
    }
}
