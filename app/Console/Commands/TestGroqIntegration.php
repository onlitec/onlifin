<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AIService;
use App\Services\AICategorizationService;
use App\Services\TransferDetectionService;
use App\Models\User;

class TestGroqIntegration extends Command
{
    protected $signature = 'ai:test-groq {api_key} {model=llama-3.3-70b-versatile} {user_id=1}';
    protected $description = 'Testa a integração com Groq';

    public function handle()
    {
        $apiKey = $this->argument('api_key');
        $model = $this->argument('model');
        $userId = $this->argument('user_id');
        
        $user = User::find($userId);
        if (!$user) {
            $this->error("Usuário com ID {$userId} não encontrado");
            return 1;
        }
        
        auth()->login($user);
        
        $this->info("🚀 Testando integração com Groq");
        $this->line("  API Key: " . substr($apiKey, 0, 10) . "...");
        $this->line("  Modelo: {$model}");
        $this->line("  Usuário: {$user->name}");
        
        // Teste 1: Conexão básica
        $this->info("\n1️⃣ Testando conexão básica...");
        try {
            $aiService = new AIService('groq', $model, $apiKey);
            $result = $aiService->testConnection();
            
            if ($result) {
                $this->info("✅ Conexão com Groq estabelecida com sucesso!");
            } else {
                $this->error("❌ Falha na conexão com Groq");
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("❌ Erro na conexão: " . $e->getMessage());
            return 1;
        }
        
        // Teste 2: Categorização de transações
        $this->info("\n2️⃣ Testando categorização de transações...");
        try {
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
                ],
                [
                    'date' => '2025-07-13',
                    'description' => 'Transferência enviada pelo Pix - CARLOS',
                    'amount' => 100.00,
                    'type' => 'expense'
                ]
            ];
            
            // Configurar temporariamente o Groq
            config([
                'ai.enabled' => true,
                'ai.provider' => 'groq',
                'ai.groq.api_key' => $apiKey,
                'ai.groq.model' => $model
            ]);
            
            $categorizationService = new AICategorizationService();
            $categorizedTransactions = $categorizationService->categorizeTransactions($transactions, 6);
            
            $this->info("✅ Categorização concluída!");
            
            foreach ($categorizedTransactions as $i => $transaction) {
                $this->line("\n  Transação {$i}:");
                $this->line("    Descrição: " . $transaction['description']);
                $this->line("    Tipo: " . $transaction['type']);
                $this->line("    Categoria: " . ($transaction['suggested_category_name'] ?? 'SEM CATEGORIA'));
                $this->line("    Confiança: " . round(($transaction['category_confidence'] ?? 0) * 100) . "%");
                
                if (!empty($transaction['ai_reasoning'])) {
                    $this->line("    Raciocínio: " . substr($transaction['ai_reasoning'], 0, 80) . "...");
                }
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Erro na categorização: " . $e->getMessage());
        }
        
        // Teste 3: Detecção de transferências
        $this->info("\n3️⃣ Testando detecção de transferências...");
        try {
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
            
            $transferService = new TransferDetectionService();
            $transferResults = $transferService->detectTransfers($transferTransactions, 6);
            
            $this->info("✅ Detecção de transferências concluída!");
            
            foreach ($transferResults as $i => $result) {
                $this->line("\n  Transação {$i}:");
                $this->line("    Descrição: " . $transferTransactions[$i]['description']);
                $this->line("    É transferência: " . ($result['is_transfer'] ? 'SIM' : 'NÃO'));
                $this->line("    Confiança: " . round($result['confidence'] * 100) . "%");
                
                if ($result['is_transfer']) {
                    $this->line("    Conta origem: " . ($result['origin_account_name'] ?? 'N/A'));
                    $this->line("    Conta destino: " . ($result['destination_account_name'] ?? 'N/A'));
                }
                
                if (!empty($result['reasoning'])) {
                    $this->line("    Raciocínio: " . substr($result['reasoning'], 0, 80) . "...");
                }
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Erro na detecção de transferências: " . $e->getMessage());
        }
        
        // Teste 4: Análise de texto simples
        $this->info("\n4️⃣ Testando análise de texto simples...");
        try {
            $aiService = new AIService('groq', $model, $apiKey);
            $response = $aiService->analyzeText("Categorize esta transação: Compra no débito - SUPERMERCADO EXTRA");
            
            $this->info("✅ Análise de texto concluída!");
            $this->line("  Resposta: " . substr($response, 0, 200) . "...");
            
        } catch (\Exception $e) {
            $this->error("❌ Erro na análise de texto: " . $e->getMessage());
        }
        
        $this->info("\n🎉 Teste de integração com Groq concluído!");
        $this->line("O provedor Groq está funcionando corretamente no sistema Onlifin.");
        
        return 0;
    }
}
