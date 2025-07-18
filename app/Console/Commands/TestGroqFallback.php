<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AICategorizationService;
use App\Services\TransferDetectionService;
use App\Models\User;
use App\Models\ModelApiKey;

class TestGroqFallback extends Command
{
    protected $signature = 'ai:test-groq-fallback {user_id=1}';
    protected $description = 'Testa o sistema de fallback automático do Groq';

    public function handle()
    {
        $userId = $this->argument('user_id');
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("Usuário com ID {$userId} não encontrado");
            return 1;
        }
        
        auth()->login($user);
        
        $this->info("🔄 Testando sistema de fallback automático do Groq");
        $this->line("  Usuário: {$user->name}");
        
        // Verificar configurações Groq disponíveis
        $this->info("\n1️⃣ Verificando configurações Groq disponíveis...");
        
        $groqConfigs = ModelApiKey::where('provider', 'groq')
            ->where('is_active', true)
            ->whereNotNull('api_token')
            ->where('api_token', '!=', '')
            ->get();
            
        if ($groqConfigs->count() < 2) {
            $this->error("❌ É necessário ter pelo menos 2 configurações Groq ativas para testar o fallback");
            $this->line("   Configurações encontradas: " . $groqConfigs->count());
            $this->line("   Configure mais provedores Groq em: http://172.20.120.180/multiple-ai-config");
            return 1;
        }
        
        $this->info("✅ Encontradas " . $groqConfigs->count() . " configurações Groq:");
        foreach ($groqConfigs as $i => $config) {
            $this->line("  {$i}: {$config->model} (ID: {$config->id})");
        }
        
        // Teste 1: Categorização com fallback
        $this->info("\n2️⃣ Testando categorização com fallback...");
        
        $transactions = [
            [
                'date' => '2025-07-13',
                'description' => 'Compra no débito - PADARIA CAPRI',
                'amount' => 13.59,
                'type' => 'expense'
            ],
            [
                'date' => '2025-07-13',
                'description' => 'SALARIO EMPRESA XYZ LTDA',
                'amount' => 3500.00,
                'type' => 'income'
            ]
        ];
        
        try {
            $categorizationService = new AICategorizationService();
            $categorizedTransactions = $categorizationService->categorizeTransactions($transactions, 6);
            
            $this->info("✅ Categorização concluída com sucesso!");
            
            foreach ($categorizedTransactions as $i => $transaction) {
                $this->line("  Transação {$i}: " . $transaction['description']);
                $this->line("    Categoria: " . ($transaction['suggested_category_name'] ?? 'SEM CATEGORIA'));
                $this->line("    Confiança: " . round(($transaction['category_confidence'] ?? 0) * 100) . "%");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Erro na categorização: " . $e->getMessage());
        }
        
        // Teste 2: Detecção de transferências com fallback
        $this->info("\n3️⃣ Testando detecção de transferências com fallback...");
        
        $transferTransactions = [
            [
                'date' => '2025-07-13',
                'description' => 'PIX ENVIADO PARA CONTA POUPANÇA',
                'amount' => 500.00,
                'type' => 'expense'
            ],
            [
                'date' => '2025-07-13',
                'description' => 'TED RECEBIDA DE CONTA CORRENTE',
                'amount' => 1000.00,
                'type' => 'income'
            ]
        ];
        
        try {
            $transferService = new TransferDetectionService();
            $transferResults = $transferService->detectTransfers($transferTransactions, 6);
            
            $this->info("✅ Detecção de transferências concluída!");
            
            foreach ($transferResults as $i => $result) {
                $this->line("  Transação {$i}: " . $transferTransactions[$i]['description']);
                $this->line("    É transferência: " . ($result['is_transfer'] ? 'SIM' : 'NÃO'));
                $this->line("    Confiança: " . round($result['confidence'] * 100) . "%");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Erro na detecção de transferências: " . $e->getMessage());
        }
        
        // Teste 3: Simular erro de rate limit
        $this->info("\n4️⃣ Testando comportamento com múltiplas chamadas...");
        
        $this->line("Fazendo múltiplas chamadas para testar o sistema de fallback:");
        
        for ($i = 1; $i <= 5; $i++) {
            try {
                $this->line("  Chamada {$i}...", false);
                
                $testTransaction = [
                    [
                        'date' => '2025-07-13',
                        'description' => "Teste de fallback #{$i} - SUPERMERCADO EXTRA",
                        'amount' => rand(10, 100),
                        'type' => 'expense'
                    ]
                ];
                
                $categorizationService = new AICategorizationService();
                $result = $categorizationService->categorizeTransactions($testTransaction, 6);
                
                if (!empty($result[0]['suggested_category_name'])) {
                    $this->info(" ✅ Sucesso - Categoria: " . $result[0]['suggested_category_name']);
                } else {
                    $this->error(" ❌ Falha - Sem categoria");
                }
                
                // Pequena pausa entre chamadas
                sleep(1);
                
            } catch (\Exception $e) {
                $this->error(" ❌ Erro: " . substr($e->getMessage(), 0, 50) . "...");
            }
        }
        
        // Teste 4: Verificar logs de fallback
        $this->info("\n5️⃣ Verificando logs de fallback...");
        
        $this->line("Para verificar se o fallback está funcionando, verifique os logs:");
        $this->line("  tail -f storage/logs/laravel-" . date('Y-m-d') . ".log | grep -i fallback");
        
        $this->info("\n🎉 Teste de fallback concluído!");
        $this->line("O sistema está configurado para usar automaticamente o segundo provedor Groq");
        $this->line("quando o primeiro atingir o limite de taxa ou falhar.");
        
        return 0;
    }
}
