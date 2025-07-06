<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Category;
use App\Models\User;

class CreateCategoriesFromSuggestions extends Command
{
    protected $signature = 'fix:create-categories-from-suggestions';
    protected $description = 'Cria categorias a partir de suggested_category que aparecem nos logs de erro';

    public function handle()
    {
        $this->info('Iniciando criação de categorias a partir de sugestões...');
        
        // Categorias sugeridas que aparecem nos logs
        $suggestedCategories = [
            ['name' => 'Despesa Geral', 'type' => 'expense'],
            ['name' => 'Receita Geral', 'type' => 'income'],
            ['name' => 'Outros', 'type' => 'expense'],
            ['name' => 'Outras Receitas', 'type' => 'income'],
            ['name' => 'Outras Despesas', 'type' => 'expense'],
        ];
        
        $users = User::all();
        $created = 0;
        
        foreach ($users as $user) {
            $this->info("Processando usuário: {$user->name} (ID: {$user->id})");
            
            foreach ($suggestedCategories as $categoryData) {
                // Verifica se a categoria já existe
                $existingCategory = Category::where('user_id', $user->id)
                    ->where('name', $categoryData['name'])
                    ->where('type', $categoryData['type'])
                    ->first();
                
                if (!$existingCategory) {
                    // Cria a categoria
                    $category = new Category();
                    $category->name = $categoryData['name'];
                    $category->type = $categoryData['type'];
                    $category->user_id = $user->id;
                    $category->color = '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
                    $category->icon = 'fa-solid fa-tag';
                    $category->description = 'Categoria criada automaticamente a partir de sugestões';
                    $category->save();
                    
                    $this->info("  → Categoria criada: {$category->name} ({$category->type}) - ID: {$category->id}");
                    $created++;
                } else {
                    $this->info("  → Categoria já existe: {$categoryData['name']} ({$categoryData['type']}) - ID: {$existingCategory->id}");
                }
            }
        }
        
        $this->info("Comando concluído! Total de categorias criadas: {$created}");
        return 0;
    }
} 