<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AICategorizationService;
use App\Models\User;

class TestAICategorization extends Command
{
    protected $signature = 'ai:test-categorization {user_id=1}';
    protected $description = 'Testa a categorização de IA com transações de exemplo';

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
        
        $this->info("🧪 Testando categorização de IA para usuário: {$user->name}");
        
        // Transações de exemplo
        $transactions = [
            [
                'date' => '2024-01-01',
                'description' => 'PADARIA SAO JOSE - COMPRA',
                'amount' => 15.50,
                'type' => 'expense'
            ],
            [
                'date' => '2024-01-02',
                'description' => 'POSTO SHELL - GASOLINA',
                'amount' => 85.00,
                'type' => 'expense'
            ],
            [
                'date' => '2024-01-03',
                'description' => 'SALARIO EMPRESA XYZ',
                'amount' => 3500.00,
                'type' => 'income'
            ]
        ];
        
        try {
            $aiService = new AICategorizationService();
            
            $this->info("📝 Transações para categorizar:");
            foreach ($transactions as $i => $transaction) {
                $this->line("  {$i}: {$transaction['description']} - R$ {$transaction['amount']} ({$transaction['type']})");
            }
            
            $this->info("\n🤖 Executando categorização com IA...");
            
            $categorizedTransactions = $aiService->categorizeTransactions($transactions);
            
            $this->info("\n✅ Resultado da categorização:");
            
            foreach ($categorizedTransactions as $i => $transaction) {
                $confidence = isset($transaction['category_confidence']) ? 
                    round($transaction['category_confidence'] * 100) . '%' : 'N/A';
                
                $this->line("  {$i}: {$transaction['description']}");
                $this->line("     → Categoria: {$transaction['suggested_category_name']}");
                $this->line("     → Confiança: {$confidence}");
                $this->line("     → Nova categoria: " . ($transaction['is_new_category'] ? 'Sim' : 'Não'));
                
                if (isset($transaction['ai_reasoning'])) {
                    $this->line("     → Raciocínio: {$transaction['ai_reasoning']}");
                }
                $this->line("");
            }
            
            $this->info("🎉 Teste concluído com sucesso!");
            
        } catch (\Exception $e) {
            $this->error("❌ Erro no teste: " . $e->getMessage());
            $this->line("Stack trace: " . $e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }
}
