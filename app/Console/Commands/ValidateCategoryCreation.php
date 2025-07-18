<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\StatementImportService;
use App\Services\CategoryTypeService;
use App\Models\User;
use App\Models\Category;

class ValidateCategoryCreation extends Command
{
    protected $signature = 'validate:category-creation {user_id=2}';
    protected $description = 'Valida se as categorias de despesas estão sendo criadas corretamente';

    public function handle()
    {
        $userId = $this->argument('user_id');
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("Usuário com ID {$userId} não encontrado");
            return 1;
        }
        
        auth()->login($user);
        
        $this->info("🧪 Validando criação de categorias de despesas");
        $this->line("  Usuário: {$user->name} (ID: {$user->id})");
        
        // Verificar categorias existentes
        $this->info("\n📊 Verificando categorias existentes do usuário...");
        $existingCount = Category::where('user_id', $user->id)->count();
        $this->line("  Categorias existentes: {$existingCount}");
        
        // Teste 1: Verificar CategoryTypeService
        $this->info("\n1️⃣ Testando CategoryTypeService:");
        
        $testCategories = [
            'Alimentação' => 'expense',
            'Transporte' => 'expense', 
            'Saúde' => 'expense',
            'Lazer' => 'expense',
            'Outros Gastos' => 'expense',
            'Salário' => 'income',
            'Freelance' => 'income',
            'Outros Recebimentos' => 'income',
            'Transferências' => 'expense'
        ];
        
        foreach ($testCategories as $categoryName => $expectedType) {
            $actualType = CategoryTypeService::getCategoryType($categoryName);
            $correctType = CategoryTypeService::getCorrectCategoryType($categoryName, 'expense');
            
            $status = ($actualType === $expectedType) ? '✅' : '❌';
            $this->line("  {$status} {$categoryName}: esperado={$expectedType}, atual={$actualType}, correto={$correctType}");
        }
        
        // Teste 2: Simular importação de transações
        $this->info("\n2️⃣ Testando importação de transações:");
        
        $testTransactions = [
            [
                'date' => '2025-07-13',
                'description' => 'Compra no débito - PADARIA CAPRI',
                'amount' => 1359, // em centavos
                'type' => 'expense',
                'suggested_category' => 'Alimentação',
                'category_id' => 'new_Alimentação',
                'is_new_category' => true
            ],
            [
                'date' => '2025-07-13', 
                'description' => 'Compra no débito - POSTO SHELL',
                'amount' => 5000,
                'type' => 'expense',
                'suggested_category' => 'Transporte',
                'category_id' => 'new_Transporte',
                'is_new_category' => true
            ],
            [
                'date' => '2025-07-13',
                'description' => 'SALARIO EMPRESA XYZ',
                'amount' => 350000,
                'type' => 'income',
                'suggested_category' => 'Salário',
                'category_id' => 'new_Salário',
                'is_new_category' => true
            ]
        ];
        
        try {
            $importService = new StatementImportService();
            $result = $importService->importTransactions($testTransactions, 6); // account_id = 6
            
            $this->info("✅ Importação concluída!");
            $this->line("  Transações importadas: " . count($testTransactions));
            
        } catch (\Exception $e) {
            $this->error("❌ Erro na importação: " . $e->getMessage());
            $this->line("Stack trace: " . $e->getTraceAsString());
            return 1;
        }
        
        // Teste 3: Verificar categorias criadas
        $this->info("\n3️⃣ Verificando categorias criadas:");
        
        $createdCategories = Category::where('user_id', $user->id)->get();
        
        if ($createdCategories->count() === 0) {
            $this->error("❌ Nenhuma categoria foi criada!");
            return 1;
        }
        
        foreach ($createdCategories as $category) {
            $expectedType = CategoryTypeService::getCategoryType($category->name);
            $isCorrect = ($category->type === $expectedType);
            $status = $isCorrect ? '✅' : '❌';
            
            $this->line("  {$status} {$category->name}: tipo={$category->type}, esperado={$expectedType}");
            
            if (!$isCorrect) {
                $this->error("    PROBLEMA: Categoria criada com tipo incorreto!");
            }
        }
        
        // Teste 4: Verificar transações criadas
        $this->info("\n4️⃣ Verificando transações criadas:");
        
        $transactions = \App\Models\Transaction::where('user_id', $user->id)->with('category')->get();
        
        foreach ($transactions as $transaction) {
            $categoryName = $transaction->category ? $transaction->category->name : 'SEM CATEGORIA';
            $categoryType = $transaction->category ? $transaction->category->type : 'N/A';
            
            $this->line("  Transação: " . substr($transaction->description, 0, 30) . "...");
            $this->line("    Tipo da transação: {$transaction->type}");
            $this->line("    Categoria: {$categoryName} (tipo: {$categoryType})");
            
            // Verificar se tipo da categoria está correto
            if ($transaction->category) {
                $expectedCategoryType = CategoryTypeService::getCategoryType($transaction->category->name);
                $isCorrect = ($transaction->category->type === $expectedCategoryType);
                
                if (!$isCorrect) {
                    $this->error("    ❌ PROBLEMA: Categoria com tipo incorreto!");
                } else {
                    $this->line("    ✅ Categoria com tipo correto");
                }
            }
        }
        
        // Resumo final
        $this->info("\n📊 Resumo do teste:");
        $this->line("  Categorias criadas: " . $createdCategories->count());
        $this->line("  Transações criadas: " . $transactions->count());
        
        $correctCategories = $createdCategories->filter(function($cat) {
            return $cat->type === CategoryTypeService::getCategoryType($cat->name);
        });
        
        $this->line("  Categorias com tipo correto: " . $correctCategories->count() . "/" . $createdCategories->count());
        
        if ($correctCategories->count() === $createdCategories->count()) {
            $this->info("🎉 Teste concluído com sucesso! Todas as categorias foram criadas corretamente.");
        } else {
            $this->error("❌ Algumas categorias foram criadas com tipo incorreto.");
        }
        
        return 0;
    }
}
