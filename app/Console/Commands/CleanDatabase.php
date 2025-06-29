<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:clean {--confirm : Confirma a exclusão sem perguntar} {--transactions : Limpa apenas transações} {--categories : Limpa apenas categorias}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpa transações e categorias do banco de dados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $onlyTransactions = $this->option('transactions');
        $onlyCategories = $this->option('categories');
        $confirmed = $this->option('confirm');
        
        if (!$onlyTransactions && !$onlyCategories) {
            // Se nenhuma opção específica for fornecida, limpa ambos
            $onlyTransactions = true;
            $onlyCategories = true;
        }
        
        if (!$confirmed) {
            $what = [];
            if ($onlyTransactions) $what[] = 'transações';
            if ($onlyCategories) $what[] = 'categorias';
            
            $confirm = $this->confirm('Tem certeza que deseja excluir todas as ' . implode(' e ', $what) . ' do banco de dados? Esta ação não pode ser desfeita.');
            
            if (!$confirm) {
                $this->info('Operação cancelada pelo usuário.');
                return Command::FAILURE;
            }
        }
        
        try {
            // Desabilitar verificação de chaves estrangeiras temporariamente
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            
            if ($onlyTransactions) {
                $count = Transaction::count();
                Transaction::truncate();
                $this->info("Todas as $count transações foram removidas com sucesso.");
            }
            
            if ($onlyCategories) {
                $count = Category::count();
                Category::truncate();
                $this->info("Todas as $count categorias foram removidas com sucesso.");
            }
            
            // Reabilitar verificação de chaves estrangeiras
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            // Garantir que a verificação de chaves estrangeiras seja reabilitada mesmo em caso de erro
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            
            $this->error("Erro ao limpar o banco de dados: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
