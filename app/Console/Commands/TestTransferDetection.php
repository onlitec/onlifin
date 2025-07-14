<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TransferDetectionService;
use App\Services\TransferProcessingService;
use App\Models\User;
use App\Models\Account;

class TestTransferDetection extends Command
{
    protected $signature = 'transfer:test-detection {user_id=1}';
    protected $description = 'Testa a detecção de transferências com transações de exemplo';

    public function handle()
    {
        $userId = $this->argument('user_id');
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("Usuário com ID {$userId} não encontrado");
            return 1;
        }
        
        // Simular login do usuário
        auth()->login($user);
        
        $this->info("🔄 Testando detecção de transferências para usuário: {$user->name}");
        
        // Verificar contas do usuário
        $accounts = Account::where('user_id', $userId)->where('active', true)->get();
        
        if ($accounts->count() < 2) {
            $this->error("Usuário precisa ter pelo menos 2 contas ativas para testar transferências");
            return 1;
        }
        
        $this->info("📊 Contas encontradas:");
        foreach ($accounts as $account) {
            $this->line("  - {$account->name} (ID: {$account->id}) - {$account->type}");
        }
        
        // Transações de exemplo com transferências
        $currentAccountId = $accounts->first()->id;
        $otherAccountId = $accounts->skip(1)->first()->id;
        
        $transactions = [
            // Transferência TED
            [
                'date' => '2024-01-01',
                'description' => 'TED TRANSFERENCIA PARA CONTA POUPANCA',
                'amount' => 500.00,
                'type' => 'expense'
            ],
            // PIX
            [
                'date' => '2024-01-02',
                'description' => 'PIX RECEBIDO DE CONTA CORRENTE',
                'amount' => 200.00,
                'type' => 'income'
            ],
            // DOC
            [
                'date' => '2024-01-03',
                'description' => 'DOC PARA CONTA INVESTIMENTO',
                'amount' => 1000.00,
                'type' => 'expense'
            ],
            // Transação normal (não transferência)
            [
                'date' => '2024-01-04',
                'description' => 'SUPERMERCADO EXTRA',
                'amount' => 150.00,
                'type' => 'expense'
            ],
            // Saque/Depósito
            [
                'date' => '2024-01-05',
                'description' => 'DEPOSITO EM CONTA CORRENTE',
                'amount' => 300.00,
                'type' => 'income'
            ]
        ];
        
        try {
            $this->info("\n📝 Transações para análise:");
            foreach ($transactions as $i => $transaction) {
                $this->line("  {$i}: {$transaction['description']} - R$ {$transaction['amount']} ({$transaction['type']})");
            }
            
            $this->info("\n🤖 Executando detecção de transferências com IA...");
            
            $transferDetectionService = new TransferDetectionService();
            $transactionsWithTransfers = $transferDetectionService->detectAndProcessTransfers($transactions, $currentAccountId);
            
            $this->info("\n✅ Resultado da detecção:");
            
            $transfersFound = 0;
            foreach ($transactionsWithTransfers as $i => $transaction) {
                $this->line("\n  {$i}: {$transaction['description']}");
                
                if (isset($transaction['is_transfer']) && $transaction['is_transfer']) {
                    $transfersFound++;
                    $confidence = isset($transaction['transfer_confidence']) ? 
                        round($transaction['transfer_confidence'] * 100) . '%' : 'N/A';
                    
                    $this->line("     🔄 TRANSFERÊNCIA DETECTADA");
                    $this->line("     → Confiança: {$confidence}");
                    $this->line("     → Origem: " . ($transaction['origin_account_name'] ?? 'Não identificada'));
                    $this->line("     → Destino: " . ($transaction['destination_account_name'] ?? 'Não identificada'));
                    
                    if (isset($transaction['transfer_reasoning'])) {
                        $this->line("     → Raciocínio: {$transaction['transfer_reasoning']}");
                    }
                } else {
                    $this->line("     ➡️ Transação normal");
                }
            }
            
            $this->info("\n📊 Resumo:");
            $this->line("  • Total de transações: " . count($transactions));
            $this->line("  • Transferências detectadas: {$transfersFound}");
            
            if ($transfersFound > 0) {
                $this->info("\n🔧 Testando processamento de transferências...");
                
                $transferProcessingService = new TransferProcessingService();
                $processedTransactions = $transferProcessingService->processTransfers($transactionsWithTransfers, $currentAccountId);
                
                $stats = $transferProcessingService->getTransferStats($processedTransactions);
                
                $this->info("📈 Estatísticas de processamento:");
                $this->line("  • Transferências detectadas: {$stats['total_transfers_detected']}");
                $this->line("  • Transferências processadas: {$stats['transfers_processed']}");
                $this->line("  • Transferências incompletas: {$stats['transfers_incomplete']}");
                $this->line("  • Transferências existentes: {$stats['transfers_existing']}");
                $this->line("  • Alta confiança: {$stats['high_confidence_transfers']}");
            }
            
            $this->info("\n🎉 Teste concluído com sucesso!");
            
        } catch (\Exception $e) {
            $this->error("❌ Erro no teste: " . $e->getMessage());
            $this->line("Stack trace: " . $e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }
}
