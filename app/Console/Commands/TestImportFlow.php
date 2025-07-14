<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AICategorizationService;
use App\Models\User;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;

class TestImportFlow extends Command
{
    protected $signature = 'test:import-flow {user_id=1}';
    protected $description = 'Testa o fluxo completo de importação e categorização';

    public function handle()
    {
        $userId = $this->argument('user_id');
        
        $this->info("🧪 Testando Fluxo de Importação e Categorização");
        $this->info("👤 Usuário ID: {$userId}");
        
        // 1. Verificar usuário
        $user = User::find($userId);
        if (!$user) {
            $this->error("❌ Usuário não encontrado");
            return 1;
        }
        
        // Simular autenticação
        auth()->login($user);
        
        $this->info("✅ Usuário: {$user->name}");
        
        // 2. Verificar conta
        $account = Account::where('user_id', $userId)->first();
        if (!$account) {
            $this->error("❌ Nenhuma conta encontrada para o usuário");
            return 1;
        }
        
        $this->info("✅ Conta: {$account->name}");
        
        // 3. Criar transações de teste
        $testTransactions = [
            [
                'description' => 'PADARIA DO JOAO',
                'amount' => -15.50,
                'date' => now()->format('Y-m-d'),
                'type' => 'expense'
            ],
            [
                'description' => 'POSTO SHELL BR',
                'amount' => -80.00,
                'date' => now()->format('Y-m-d'),
                'type' => 'expense'
            ],
            [
                'description' => 'SALARIO EMPRESA XYZ',
                'amount' => 3500.00,
                'date' => now()->format('Y-m-d'),
                'type' => 'income'
            ],
            [
                'description' => 'FARMACIA POPULAR',
                'amount' => -25.90,
                'date' => now()->format('Y-m-d'),
                'type' => 'expense'
            ]
        ];
        
        $this->info("\n📊 Transações de teste criadas: " . count($testTransactions));
        
        // 4. Testar categorização com IA
        $this->info("\n🤖 Testando categorização com IA...");
        
        try {
            $aiService = new AICategorizationService();
            $categorizedTransactions = $aiService->categorizeTransactions($testTransactions, $account->id);
            
            $this->info("✅ Categorização concluída");
            $this->info("📈 Transações categorizadas: " . count($categorizedTransactions));
            
            // 5. Verificar resultados
            $this->info("\n📋 Resultados da categorização:");
            
            foreach ($categorizedTransactions as $index => $transaction) {
                $categoryName = $transaction['suggested_category_name'] ?? 'SEM CATEGORIA';
                $categoryId = $transaction['suggested_category_id'] ?? 'NULL';
                $confidence = $transaction['category_confidence'] ?? 0;
                $isNew = $transaction['is_new_category'] ?? false;
                
                $this->line("  {$index}: {$transaction['description']}");
                $this->line("    Categoria: {$categoryName} (ID: {$categoryId})");
                $this->line("    Confiança: " . number_format($confidence * 100, 1) . "%");
                $this->line("    Nova categoria: " . ($isNew ? 'SIM' : 'NÃO'));
                $this->line("");
            }
            
            // 6. Simular criação de transações
            $this->info("💾 Simulando criação de transações...");
            
            $createdCount = 0;
            $categoriesCreated = [];
            
            foreach ($categorizedTransactions as $transactionData) {
                // Verificar se categoria precisa ser criada
                $categoryId = null;
                $categoryName = $transactionData['suggested_category_name'] ?? null;
                
                if ($categoryName) {
                    if ($transactionData['is_new_category'] ?? false) {
                        // Criar nova categoria
                        $category = Category::firstOrCreate([
                            'user_id' => $userId,
                            'name' => $categoryName,
                            'type' => $transactionData['type']
                        ], [
                            'system' => false
                        ]);
                        
                        $categoryId = $category->id;
                        
                        if ($category->wasRecentlyCreated) {
                            $categoriesCreated[] = $categoryName;
                        }
                    } else {
                        $categoryId = $transactionData['suggested_category_id'];
                    }
                }
                
                // Criar transação (simulação - não salvar realmente)
                $this->line("  ✅ Transação: {$transactionData['description']} → Categoria: {$categoryName} (ID: {$categoryId})");
                $createdCount++;
            }
            
            $this->info("\n📊 Resumo:");
            $this->info("  Transações processadas: {$createdCount}");
            $this->info("  Categorias criadas: " . count($categoriesCreated));
            
            if (!empty($categoriesCreated)) {
                $this->info("  Novas categorias: " . implode(', ', $categoriesCreated));
            }
            
            // 7. Verificar problemas
            $this->info("\n🔍 Verificando problemas:");
            
            $problemsFound = false;
            
            foreach ($categorizedTransactions as $index => $transaction) {
                if (empty($transaction['suggested_category_name'])) {
                    $this->error("  ❌ Transação {$index} sem categoria: {$transaction['description']}");
                    $problemsFound = true;
                }
                
                if (empty($transaction['suggested_category_id']) && !($transaction['is_new_category'] ?? false)) {
                    $this->warn("  ⚠️  Transação {$index} sem category_id e não é nova categoria: {$transaction['description']}");
                    $problemsFound = true;
                }
            }
            
            if (!$problemsFound) {
                $this->info("  ✅ Nenhum problema encontrado!");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Erro na categorização: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }
}
