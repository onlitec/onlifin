<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AICategorizationService;
use App\Models\User;

class TestFullCategorization extends Command
{
    protected $signature = 'ai:test-full-categorization {user_id=2}';
    protected $description = 'Testa o processo completo de categorização';

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
        
        $this->info("🔧 Testando categorização completa para usuário: {$user->name}");
        
        // Transação específica que está falhando
        $transactions = [
            [
                'date' => '2025-07-03',
                'description' => 'Compra no débito - PADARIA CAPRI',
                'amount' => 13.59,
                'type' => 'expense'
            ]
        ];
        
        $this->info("📋 Transação para teste:");
        $this->line("  Descrição: " . $transactions[0]['description']);
        $this->line("  Valor: R$ " . $transactions[0]['amount']);
        $this->line("  Tipo: " . $transactions[0]['type']);
        
        try {
            $this->info("\n🤖 Executando categorização com IA...");
            
            $aiService = new AICategorizationService();
            $result = $aiService->categorizeTransactions($transactions, 6);
            
            $this->info("\n✅ Resultado da categorização:");
            
            foreach ($result as $i => $transaction) {
                $this->line("\n  Transação {$i}:");
                $this->line("    Descrição: " . $transaction['description']);
                $this->line("    Tipo: " . $transaction['type']);
                
                if (!empty($transaction['suggested_category_name'])) {
                    $this->line("    ✅ Categoria: " . $transaction['suggested_category_name']);
                    $this->line("    ID da categoria: " . ($transaction['suggested_category_id'] ?? 'NULL'));
                    $this->line("    Nova categoria: " . ($transaction['is_new_category'] ? 'Sim' : 'Não'));
                    $this->line("    Confiança: " . round(($transaction['category_confidence'] ?? 0) * 100) . "%");
                    
                    if (!empty($transaction['ai_reasoning'])) {
                        $this->line("    Raciocínio: " . $transaction['ai_reasoning']);
                    }
                } else {
                    $this->error("    ❌ SEM CATEGORIA!");
                }
            }
            
            // Verificar se há transferências detectadas
            if (isset($result[0]['is_transfer']) && $result[0]['is_transfer']) {
                $this->info("\n🔄 Transferência detectada:");
                $this->line("  Conta origem: " . ($result[0]['origin_account_id'] ?? 'N/A'));
                $this->line("  Conta destino: " . ($result[0]['destination_account_id'] ?? 'N/A'));
            }
            
            $this->info("\n📊 Resumo:");
            $categorizedCount = 0;
            foreach ($result as $transaction) {
                if (!empty($transaction['suggested_category_name'])) {
                    $categorizedCount++;
                }
            }
            
            $this->line("  Total de transações: " . count($result));
            $this->line("  Transações categorizadas: " . $categorizedCount);
            $this->line("  Taxa de sucesso: " . round(($categorizedCount / count($result)) * 100, 1) . "%");
            
            if ($categorizedCount === count($result)) {
                $this->info("\n🎉 Teste concluído com sucesso! Todas as transações foram categorizadas.");
            } else {
                $this->error("\n❌ Algumas transações não foram categorizadas.");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Erro no teste: " . $e->getMessage());
            $this->line("Stack trace: " . $e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }
}
