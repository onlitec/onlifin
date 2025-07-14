<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AICategorizationService;
use App\Services\CategoryTypeService;
use App\Models\User;

class TestCategoryTypeFix extends Command
{
    protected $signature = 'ai:test-category-type-fix {user_id=1}';
    protected $description = 'Testa a correção dos tipos de categoria';

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
        
        $this->info("🔧 Testando correção de tipos de categoria para usuário: {$user->name}");
        
        // Transações problemáticas baseadas no relato
        $transactions = [
            // DESPESAS que devem ter categorias de DESPESA
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
            // RECEITAS que devem ter categorias de RECEITA
            [
                'date' => '2025-07-03',
                'description' => 'SALARIO EMPRESA XYZ LTDA',
                'amount' => 3500.00,
                'type' => 'income'
            ],
            [
                'date' => '2025-07-03',
                'description' => 'FREELANCE PROJETO WEB',
                'amount' => 800.00,
                'type' => 'income'
            ],
            // TRANSFERÊNCIAS (podem ser ambos)
            [
                'date' => '2025-07-03',
                'description' => 'Transferência enviada pelo Pix - CARLOS CORREA',
                'amount' => 7.00,
                'type' => 'expense'
            ]
        ];
        
        $this->info("\n📋 Testando mapeamento de tipos de categoria:");
        
        // Testar o serviço de tipos de categoria
        foreach ($transactions as $i => $transaction) {
            $this->line("\n  Transação {$i}: {$transaction['description']}");
            $this->line("    Tipo da transação: {$transaction['type']}");
            
            // Simular categorização
            $expectedCategories = [
                'PADARIA CAPRI' => 'Alimentação',
                'AUTO POSTO' => 'Transporte',
                'SUPERMERCADO' => 'Alimentação',
                'FARMACIA' => 'Saúde',
                'NETFLIX' => 'Lazer',
                'SALARIO' => 'Salário',
                'FREELANCE' => 'Freelance',
                'Transferência' => 'Transferências'
            ];
            
            $categoryName = null;
            foreach ($expectedCategories as $keyword => $category) {
                if (strpos($transaction['description'], $keyword) !== false) {
                    $categoryName = $category;
                    break;
                }
            }
            
            if ($categoryName) {
                $categoryType = CategoryTypeService::getCategoryType($categoryName);
                $correctType = CategoryTypeService::getCorrectCategoryType($categoryName, $transaction['type']);
                $isValid = CategoryTypeService::validateCategoryForTransaction($categoryName, $transaction['type']);
                
                $this->line("    Categoria sugerida: {$categoryName}");
                $this->line("    Tipo da categoria: {$categoryType}");
                $this->line("    Tipo correto: {$correctType}");
                $this->line("    Validação: " . ($isValid ? '✅ VÁLIDA' : '❌ INVÁLIDA'));
                
                if (!$isValid) {
                    $suggested = CategoryTypeService::suggestCategoryForTransaction($transaction['type']);
                    $this->line("    Sugestão alternativa: {$suggested}");
                }
            }
        }
        
        $this->info("\n🤖 Testando categorização completa com IA:");
        
        try {
            $aiService = new AICategorizationService();
            $categorizedTransactions = $aiService->categorizeTransactions($transactions, 6);
            
            $this->info("\n✅ Resultado da categorização:");
            
            $correctCount = 0;
            $totalCount = count($categorizedTransactions);
            
            foreach ($categorizedTransactions as $i => $transaction) {
                $confidence = isset($transaction['category_confidence']) ? 
                    round($transaction['category_confidence'] * 100) . '%' : 'N/A';
                
                $this->line("\n  {$i}: " . substr($transaction['description'], 0, 40) . "...");
                $this->line("     Tipo da transação: {$transaction['type']}");
                
                if (!empty($transaction['suggested_category_name'])) {
                    $categoryName = $transaction['suggested_category_name'];
                    $categoryType = CategoryTypeService::getCategoryType($categoryName);
                    $isValid = CategoryTypeService::validateCategoryForTransaction($categoryName, $transaction['type']);
                    
                    $this->line("     Categoria: {$categoryName}");
                    $this->line("     Tipo da categoria: {$categoryType}");
                    $this->line("     Confiança: {$confidence}");
                    $this->line("     Validação: " . ($isValid ? '✅ CORRETA' : '❌ INCORRETA'));
                    
                    if ($isValid) {
                        $correctCount++;
                    }
                    
                    if (isset($transaction['ai_reasoning'])) {
                        $this->line("     Raciocínio: {$transaction['ai_reasoning']}");
                    }
                } else {
                    $this->error("     ❌ SEM CATEGORIA - PROBLEMA DETECTADO!");
                }
            }
            
            $this->info("\n📊 Resumo dos testes:");
            $this->line("  • Total de transações: {$totalCount}");
            $this->line("  • Categorizações corretas: {$correctCount}");
            $this->line("  • Taxa de acerto: " . round(($correctCount / $totalCount) * 100, 1) . "%");
            
            if ($correctCount === $totalCount) {
                $this->info("\n🎉 Teste concluído com sucesso! Todos os tipos de categoria estão corretos.");
            } else {
                $this->error("\n❌ Algumas categorizações estão incorretas. Verifique os logs para mais detalhes.");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Erro no teste: " . $e->getMessage());
            $this->line("Stack trace: " . $e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }
}
