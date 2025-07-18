<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\Category;
use App\Services\CategoryTypeService;

class ValidateImportFix extends Command
{
    protected $signature = 'validate:import-fix';
    protected $description = 'Valida se as correções de importação estão funcionando';

    public function handle()
    {
        $this->info("🔍 Validação das Correções de Importação");
        
        // 1. Verificar transações sem categoria
        $this->info("\n1️⃣ Verificando transações sem categoria...");
        $transactionsSemCategoria = Transaction::whereNull('category_id')->count();
        
        if ($transactionsSemCategoria === 0) {
            $this->info("✅ Nenhuma transação sem categoria encontrada");
        } else {
            $this->error("❌ {$transactionsSemCategoria} transações sem categoria encontradas");
            
            // Mostrar algumas transações sem categoria
            $exemplos = Transaction::whereNull('category_id')->take(5)->get();
            foreach ($exemplos as $t) {
                $this->line("  - ID: {$t->id} | {$t->description} | {$t->type}");
            }
        }
        
        // 2. Verificar consistência de tipos de categoria
        $this->info("\n2️⃣ Verificando consistência de tipos de categoria...");
        
        $inconsistencies = 0;
        $categories = Category::all();
        
        foreach ($categories as $category) {
            $expectedType = CategoryTypeService::getCategoryType($category->name);
            
            if ($category->type !== $expectedType && $category->name !== 'Não Categorizada') {
                $this->warn("⚠️  Categoria '{$category->name}': tipo atual '{$category->type}', esperado '{$expectedType}'");
                $inconsistencies++;
            }
        }
        
        if ($inconsistencies === 0) {
            $this->info("✅ Todos os tipos de categoria estão consistentes");
        } else {
            $this->warn("⚠️  {$inconsistencies} inconsistências de tipo encontradas");
        }
        
        // 3. Verificar transações com categorias de tipo incorreto
        $this->info("\n3️⃣ Verificando transações com categorias de tipo incorreto...");
        
        $wrongTypeTransactions = 0;
        $transactions = Transaction::with('category')->get();
        
        foreach ($transactions as $transaction) {
            if ($transaction->category) {
                $categoryName = $transaction->category->name;
                $categoryType = $transaction->category->type;
                $transactionType = $transaction->type;
                
                // Verificar se a categoria é apropriada para o tipo da transação
                if (!CategoryTypeService::validateCategoryForTransaction($categoryName, $transactionType)) {
                    $this->warn("⚠️  Transação ID {$transaction->id}: tipo '{$transactionType}' com categoria '{$categoryName}' (tipo '{$categoryType}')");
                    $wrongTypeTransactions++;
                }
            }
        }
        
        if ($wrongTypeTransactions === 0) {
            $this->info("✅ Todas as transações têm categorias apropriadas");
        } else {
            $this->warn("⚠️  {$wrongTypeTransactions} transações com categorias de tipo incorreto");
        }
        
        // 4. Estatísticas gerais
        $this->info("\n4️⃣ Estatísticas gerais...");
        
        $totalTransactions = Transaction::count();
        $totalCategories = Category::count();
        $incomeTransactions = Transaction::where('type', 'income')->count();
        $expenseTransactions = Transaction::where('type', 'expense')->count();
        $incomeCategories = Category::where('type', 'income')->count();
        $expenseCategories = Category::where('type', 'expense')->count();
        
        $this->line("📊 Total de transações: {$totalTransactions}");
        $this->line("📊 Total de categorias: {$totalCategories}");
        $this->line("💰 Transações de receita: {$incomeTransactions}");
        $this->line("💸 Transações de despesa: {$expenseTransactions}");
        $this->line("📈 Categorias de receita: {$incomeCategories}");
        $this->line("📉 Categorias de despesa: {$expenseCategories}");
        
        // 5. Verificar categorias mais usadas
        $this->info("\n5️⃣ Top 10 categorias mais usadas...");
        
        $topCategories = Category::withCount('transactions')
            ->orderBy('transactions_count', 'desc')
            ->take(10)
            ->get();
        
        foreach ($topCategories as $category) {
            $this->line("  📁 {$category->name} ({$category->type}): {$category->transactions_count} transações");
        }
        
        // 6. Verificar se há categorias órfãs
        $this->info("\n6️⃣ Verificando categorias órfãs...");
        
        $orphanCategories = Category::doesntHave('transactions')->count();
        
        if ($orphanCategories === 0) {
            $this->info("✅ Nenhuma categoria órfã encontrada");
        } else {
            $this->warn("⚠️  {$orphanCategories} categorias sem transações encontradas");
        }
        
        // 7. Resumo final
        $this->info("\n📋 RESUMO DA VALIDAÇÃO:");
        
        $issues = 0;
        
        if ($transactionsSemCategoria > 0) {
            $this->error("❌ Transações sem categoria: {$transactionsSemCategoria}");
            $issues++;
        }
        
        if ($inconsistencies > 0) {
            $this->warn("⚠️  Inconsistências de tipo: {$inconsistencies}");
            $issues++;
        }
        
        if ($wrongTypeTransactions > 0) {
            $this->warn("⚠️  Transações com categoria incorreta: {$wrongTypeTransactions}");
            $issues++;
        }
        
        if ($issues === 0) {
            $this->info("🎉 SUCESSO: Sistema de categorização está funcionando corretamente!");
            $this->info("✅ Todas as transações têm categoria");
            $this->info("✅ Todos os tipos estão consistentes");
            $this->info("✅ Nenhum problema crítico encontrado");
        } else {
            $this->warn("⚠️  {$issues} tipos de problemas encontrados");
            $this->info("💡 Execute os comandos de correção se necessário");
        }
        
        return $issues === 0 ? 0 : 1;
    }
}
