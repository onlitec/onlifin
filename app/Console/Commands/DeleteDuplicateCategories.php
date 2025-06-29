<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteDuplicateCategories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'categories:delete-duplicates {--dry-run : Apenas mostra as categorias que seriam removidas sem excluí-las}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove categorias duplicadas mantendo apenas uma de cada nome/tipo por usuário';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando verificação de categorias duplicadas...');
        
        // Obter todas as categorias agrupadas por nome, tipo e user_id
        $categories = Category::all();
        
        // Agrupar categorias por nome, tipo e user_id
        $groupedCategories = [];
        foreach ($categories as $category) {
            $key = strtolower($category->name) . '_' . $category->type . '_' . ($category->user_id ?? 'null');
            if (!isset($groupedCategories[$key])) {
                $groupedCategories[$key] = [];
            }
            $groupedCategories[$key][] = $category;
        }
        
        $duplicatesCount = 0;
        $removedCount = 0;
        $dryRun = $this->option('dry-run');
        
        // Processar cada grupo
        foreach ($groupedCategories as $key => $group) {
            if (count($group) > 1) {
                $duplicatesCount++;
                
                // Ordenar por ID (manter o mais antigo)
                usort($group, function($a, $b) {
                    return $a->id - $b->id;
                });
                
                // Manter o primeiro e remover os demais
                $keep = array_shift($group);
                $this->info("Mantendo categoria: ID {$keep->id} - {$keep->name} ({$keep->type}) - Usuário: " . ($keep->user_id ?? 'Sistema'));
                
                foreach ($group as $duplicate) {
                    $this->warn("Categoria duplicada encontrada: ID {$duplicate->id} - {$duplicate->name} ({$duplicate->type}) - Usuário: " . ($duplicate->user_id ?? 'Sistema'));
                    
                    if (!$dryRun) {
                        try {
                            // Verificar se há transações usando esta categoria
                            $transactionCount = Transaction::where('category_id', $duplicate->id)->count();
                            if ($transactionCount > 0) {
                                // Atualizar transações para usar a categoria que será mantida
                                DB::table('transactions')
                                    ->where('category_id', $duplicate->id)
                                    ->update(['category_id' => $keep->id]);
                                
                                $this->line("  - {$transactionCount} transações atualizadas para usar a categoria ID {$keep->id}");
                            }
                            
                            // Remover a categoria duplicada
                            $duplicate->delete();
                            $removedCount++;
                            $this->info("  - Categoria ID {$duplicate->id} removida com sucesso");
                        } catch (\Exception $e) {
                            $this->error("  - Erro ao remover categoria ID {$duplicate->id}: {$e->getMessage()}");
                        }
                    } else {
                        $transactionCount = Transaction::where('category_id', $duplicate->id)->count();
                        if ($transactionCount > 0) {
                            $this->line("  - [DRY RUN] {$transactionCount} transações seriam atualizadas");
                        }
                        $this->line("  - [DRY RUN] A categoria ID {$duplicate->id} seria removida");
                    }
                }
            }
        }
        
        // Remover espaços de todas as categorias restantes
        if (!$dryRun) {
            $this->info("\nRemovendo espaços dos nomes das categorias restantes...");
            $updatedCount = 0;
            
            foreach (Category::all() as $category) {
                $nameWithoutSpaces = str_replace(' ', '', $category->name);
                if ($nameWithoutSpaces !== $category->name) {
                    $category->name = $nameWithoutSpaces;
                    $category->save();
                    $updatedCount++;
                    $this->line("Categoria ID {$category->id} atualizada: '{$category->name}'");
                }
            }
            
            $this->info("$updatedCount categorias tiveram espaços removidos do nome.");
        }
        
        if ($duplicatesCount === 0) {
            $this->info("\nNenhuma categoria duplicada encontrada.");
        } else {
            if ($dryRun) {
                $this->info("\nEncontradas $duplicatesCount categorias duplicadas que seriam removidas.");
                $this->info("Execute o comando sem a opção --dry-run para remover as duplicatas.");
            } else {
                $this->info("\nForam removidas $removedCount categorias duplicadas de um total de $duplicatesCount grupos de duplicatas.");
            }
        }
        
        return Command::SUCCESS;
    }
}
