<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CategoryTypeService;
use App\Models\Category;
use App\Models\User;

class FixCategoryTypes extends Command
{
    protected $signature = 'fix:category-types {--user= : ID do usuário específico} {--dry-run : Apenas mostrar o que seria corrigido}';
    protected $description = 'Corrige os tipos das categorias que estão incorretos';

    public function handle()
    {
        $userId = $this->option('user');
        $dryRun = $this->option('dry-run');
        
        $this->info("🔧 Corrigindo tipos de categorias");
        
        if ($userId) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("Usuário com ID {$userId} não encontrado");
                return 1;
            }
            $this->line("  Usuário: {$user->name} (ID: {$user->id})");
        } else {
            $this->line("  Escopo: Todos os usuários");
        }
        
        if ($dryRun) {
            $this->warn("  Modo: DRY RUN (apenas visualização)");
        }
        
        // Buscar categorias
        $query = Category::query();
        if ($userId) {
            $query->where('user_id', $userId);
        }
        
        $categories = $query->get();
        
        if ($categories->count() === 0) {
            $this->info("✅ Nenhuma categoria encontrada");
            return 0;
        }
        
        $this->info("\n📊 Analisando " . $categories->count() . " categorias...");
        
        $toCorrect = [];
        $correct = 0;
        
        foreach ($categories as $category) {
            $expectedType = CategoryTypeService::getCategoryType($category->name);
            
            if ($category->type !== $expectedType) {
                $toCorrect[] = [
                    'category' => $category,
                    'current_type' => $category->type,
                    'expected_type' => $expectedType
                ];
            } else {
                $correct++;
            }
        }
        
        $this->line("  ✅ Categorias corretas: {$correct}");
        $this->line("  ❌ Categorias incorretas: " . count($toCorrect));
        
        if (empty($toCorrect)) {
            $this->info("\n🎉 Todas as categorias estão com tipos corretos!");
            return 0;
        }
        
        // Mostrar categorias que serão corrigidas
        $this->info("\n🔍 Categorias que " . ($dryRun ? "seriam corrigidas" : "serão corrigidas") . ":");
        
        foreach ($toCorrect as $item) {
            $category = $item['category'];
            $currentType = $item['current_type'];
            $expectedType = $item['expected_type'];
            
            $this->line("  • {$category->name} (Usuário: {$category->user_id})");
            $this->line("    {$currentType} → {$expectedType}");
        }
        
        if ($dryRun) {
            $this->info("\n💡 Execute sem --dry-run para aplicar as correções");
            return 0;
        }
        
        // Confirmar correção
        if (!$this->confirm("\nDeseja corrigir " . count($toCorrect) . " categorias?")) {
            $this->info("Operação cancelada");
            return 0;
        }
        
        // Aplicar correções
        $this->info("\n🚀 Aplicando correções...");
        
        $corrected = 0;
        $errors = 0;
        
        foreach ($toCorrect as $item) {
            $category = $item['category'];
            $expectedType = $item['expected_type'];
            
            try {
                $category->type = $expectedType;
                $category->save();
                
                $this->line("  ✅ {$category->name}: corrigida para {$expectedType}");
                $corrected++;
                
            } catch (\Exception $e) {
                $this->error("  ❌ {$category->name}: erro - " . $e->getMessage());
                $errors++;
            }
        }
        
        // Resumo final
        $this->info("\n📊 Resumo da correção:");
        $this->line("  ✅ Categorias corrigidas: {$corrected}");
        
        if ($errors > 0) {
            $this->line("  ❌ Erros: {$errors}");
        }
        
        if ($corrected > 0) {
            $this->info("🎉 Correção concluída com sucesso!");
        }
        
        return 0;
    }
}
