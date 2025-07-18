<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AICategorizationService;
use App\Models\User;

class TestCategorizationRefinement extends Command
{
    protected $signature = 'ai:test-categorization-refinement {user_id=1}';
    protected $description = 'Testa a categorização refinada com exemplos específicos';

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
        
        $this->info("🧪 Testando categorização refinada para usuário: {$user->name}");
        
        // Transações de exemplo baseadas no problema relatado
        $transactions = [
            [
                'date' => '2025-07-03',
                'description' => 'Compra no débito - PADARIA CAPRI',
                'amount' => 13.59,
                'type' => 'expense'
            ],
            [
                'date' => '2025-07-03',
                'description' => 'Compra no débito - SELMINHO AUTO POSTO',
                'amount' => 30.00,
                'type' => 'expense'
            ],
            [
                'date' => '2025-07-03',
                'description' => 'Transferência enviada pelo Pix - CARLOS CORREA MACHADO - •••.604.218-•• - BCO BRADESCO S.A. (0237) Agência: 1960 Conta: 11567-3',
                'amount' => 7.00,
                'type' => 'expense'
            ],
            [
                'date' => '2025-07-02',
                'description' => 'Transferência enviada pelo Pix - Márcia Aparecida Domingos Freire - •••.569.868-•• - NU PAGAMENTOS - IP (0260) Agência: 1 Conta: 15572782-4',
                'amount' => 100.00,
                'type' => 'expense'
            ],
            [
                'date' => '2025-07-03',
                'description' => 'SUPERMERCADO EXTRA COMPRAS',
                'amount' => 127.45,
                'type' => 'expense'
            ],
            [
                'date' => '2025-07-03',
                'description' => 'FARMACIA DROGA RAIA MEDICAMENTOS',
                'amount' => 32.90,
                'type' => 'expense'
            ],
            [
                'date' => '2025-07-03',
                'description' => 'NETFLIX ASSINATURA MENSAL',
                'amount' => 29.90,
                'type' => 'expense'
            ],
            [
                'date' => '2025-07-03',
                'description' => 'SALARIO EMPRESA XYZ LTDA',
                'amount' => 3500.00,
                'type' => 'income'
            ]
        ];
        
        try {
            $this->info("📝 Transações para categorizar:");
            foreach ($transactions as $i => $transaction) {
                $this->line("  {$i}: {$transaction['description']} - R$ {$transaction['amount']} ({$transaction['type']})");
            }
            
            $this->info("\n🤖 Executando categorização com IA refinada...");
            
            $aiService = new AICategorizationService();
            $categorizedTransactions = $aiService->categorizeTransactions($transactions, 6); // Usando conta ID 6
            
            $this->info("\n✅ Resultado da categorização:");
            
            $categorizedCount = 0;
            $highConfidenceCount = 0;
            $newCategoriesCount = 0;
            
            foreach ($categorizedTransactions as $i => $transaction) {
                $confidence = isset($transaction['category_confidence']) ? 
                    round($transaction['category_confidence'] * 100) . '%' : 'N/A';
                
                $this->line("\n  {$i}: " . substr($transaction['description'], 0, 50) . "...");
                
                if (!empty($transaction['suggested_category_name'])) {
                    $categorizedCount++;
                    
                    if (isset($transaction['category_confidence']) && $transaction['category_confidence'] > 0.8) {
                        $highConfidenceCount++;
                    }
                    
                    if (isset($transaction['is_new_category']) && $transaction['is_new_category']) {
                        $newCategoriesCount++;
                    }
                    
                    $this->line("     ✅ Categoria: {$transaction['suggested_category_name']}");
                    $this->line("     → Confiança: {$confidence}");
                    $this->line("     → Nova categoria: " . ($transaction['is_new_category'] ? 'Sim' : 'Não'));
                    
                    if (isset($transaction['ai_reasoning'])) {
                        $this->line("     → Raciocínio: {$transaction['ai_reasoning']}");
                    }
                    
                    if (isset($transaction['is_transfer']) && $transaction['is_transfer']) {
                        $this->line("     🔄 TRANSFERÊNCIA DETECTADA");
                        if (isset($transaction['origin_account_name'])) {
                            $this->line("     → Origem: {$transaction['origin_account_name']}");
                        }
                        if (isset($transaction['destination_account_name'])) {
                            $this->line("     → Destino: {$transaction['destination_account_name']}");
                        }
                    }
                } else {
                    $this->error("     ❌ SEM CATEGORIA - PROBLEMA DETECTADO!");
                }
            }
            
            $this->info("\n📊 Resumo:");
            $this->line("  • Total de transações: " . count($transactions));
            $this->line("  • Transações categorizadas: {$categorizedCount}");
            $this->line("  • Alta confiança (80%+): {$highConfidenceCount}");
            $this->line("  • Novas categorias: {$newCategoriesCount}");
            
            $successRate = round(($categorizedCount / count($transactions)) * 100, 1);
            $this->line("  • Taxa de sucesso: {$successRate}%");
            
            if ($categorizedCount === count($transactions)) {
                $this->info("\n🎉 Teste concluído com sucesso! Todas as transações foram categorizadas.");
            } else {
                $this->error("\n❌ Algumas transações não foram categorizadas. Verifique os logs para mais detalhes.");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Erro no teste: " . $e->getMessage());
            $this->line("Stack trace: " . $e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }
}
