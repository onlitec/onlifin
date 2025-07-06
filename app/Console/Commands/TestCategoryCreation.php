<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\User;
use Illuminate\Console\Command;

class TestCategoryCreation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:create-categories {--user-id=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testa a criação de categorias básicas para um usuário, verificando a prevenção de duplicatas.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $userId = $this->option('user-id');
        $user = User::find($userId);

        if (!$user) {
            $this->error("Usuário com ID {$userId} não encontrado.");
            return 1;
        }

        $this->info("Iniciando teste de criação de categorias para o usuário: {$user->name}");

        // Limpa as categorias existentes do usuário para um teste limpo
        $this->info("Limpando categorias antigas do usuário...");
        Category::where('user_id', $user->id)->delete();
        $this->info("Categorias antigas removidas.");

        // Tenta criar as categorias básicas
        $this->info("Tentando criar categorias básicas pela primeira vez...");
        Category::createBasicCategoriesForUser($user);
        $count1 = Category::where('user_id', $user->id)->count();
        $this->info(">> Foram criadas {$count1} categorias.");

        // Tenta criar as categorias novamente para testar a prevenção de duplicatas
        $this->info("Tentando criar as mesmas categorias uma segunda vez...");
        Category::createBasicCategoriesForUser($user);
        $count2 = Category::where('user_id', $user->id)->count();
        $this->info(">> Após a segunda tentativa, o total de categorias é: {$count2}.");

        if ($count1 === $count2) {
            $this->info("✅ SUCESSO: O sistema de prevenção de duplicatas funcionou corretamente!");
        } else {
            $this->error("❌ FALHA: Foram criadas categorias duplicadas. Total antes: {$count1}, Total depois: {$count2}.");
        }
        
        // Testa o método createOrGet
        $this->info("Testando o método createOrGet com a categoria 'Alimentação'...");
        $cat1 = Category::createOrGet('Alimentação', 'expense', $user->id);
        $cat2 = Category::createOrGet('Alimentação', 'expense', $user->id);
        
        if ($cat1->id === $cat2->id) {
            $this->info("✅ SUCESSO: createOrGet retornou a mesma categoria (ID: {$cat1->id}).");
        } else {
            $this->error("❌ FALHA: createOrGet criou categorias duplicadas (ID1: {$cat1->id}, ID2: {$cat2->id}).");
        }
        
        $this->info("Teste concluído.");
        return 0;
    }
} 