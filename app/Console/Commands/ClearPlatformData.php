<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\Account;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClearPlatformData extends Command
{
    protected $signature = 'platform:clear-data 
                            {--transactions : Apagar apenas transações}
                            {--categories : Apagar apenas categorias}
                            {--all : Apagar transações e categorias}
                            {--user= : Apagar dados de usuário específico}
                            {--force : Pular confirmação}';
    
    protected $description = 'Apaga transações e/ou categorias da plataforma com segurança';

    public function handle()
    {
        $this->info("🗑️  Comando de Limpeza da Plataforma Onlifin");
        $this->line("   Data/Hora: " . now()->format('d/m/Y H:i:s'));
        
        $transactions = $this->option('transactions');
        $categories = $this->option('categories');
        $all = $this->option('all');
        $userId = $this->option('user');
        $force = $this->option('force');
        
        // Validar opções
        if (!$transactions && !$categories && !$all) {
            $this->error("❌ Especifique o que deseja apagar:");
            $this->line("   --transactions  : Apagar apenas transações");
            $this->line("   --categories    : Apagar apenas categorias");
            $this->line("   --all          : Apagar transações e categorias");
            $this->line("   --user=ID      : Apagar dados de usuário específico");
            return 1;
        }
        
        // Verificar usuário específico
        $targetUser = null;
        if ($userId) {
            $targetUser = User::find($userId);
            if (!$targetUser) {
                $this->error("❌ Usuário com ID {$userId} não encontrado");
                return 1;
            }
            $this->line("🎯 Usuário alvo: {$targetUser->name} (ID: {$targetUser->id})");
        } else {
            $this->line("🌐 Escopo: Todos os usuários");
        }
        
        // Mostrar estatísticas atuais
        $this->showCurrentStats($userId);
        
        // Confirmação de segurança
        if (!$force) {
            $this->warn("\n⚠️  ATENÇÃO: Esta operação é IRREVERSÍVEL!");
            
            if ($all || $transactions) {
                $this->warn("   • Todas as transações serão PERMANENTEMENTE apagadas");
            }
            if ($all || $categories) {
                $this->warn("   • Todas as categorias serão PERMANENTEMENTE apagadas");
            }
            
            $confirmation = $this->ask("Digite 'CONFIRMAR' para prosseguir (qualquer outra coisa cancela)");
            
            if ($confirmation !== 'CONFIRMAR') {
                $this->info("✅ Operação cancelada pelo usuário");
                return 0;
            }
        }
        
        // Executar limpeza
        $this->info("\n🚀 Iniciando limpeza...");
        
        try {
            DB::beginTransaction();
            
            $deletedCounts = [
                'transactions' => 0,
                'categories' => 0
            ];
            
            // Apagar transações
            if ($all || $transactions) {
                $deletedCounts['transactions'] = $this->clearTransactions($userId);
            }
            
            // Apagar categorias
            if ($all || $categories) {
                $deletedCounts['categories'] = $this->clearCategories($userId);
            }
            
            DB::commit();
            
            // Log da operação
            Log::info('Limpeza da plataforma executada', [
                'user_id' => auth()->id(),
                'target_user_id' => $userId,
                'deleted_transactions' => $deletedCounts['transactions'],
                'deleted_categories' => $deletedCounts['categories'],
                'timestamp' => now()
            ]);
            
            // Mostrar resultados
            $this->info("\n✅ Limpeza concluída com sucesso!");
            $this->line("   Transações apagadas: {$deletedCounts['transactions']}");
            $this->line("   Categorias apagadas: {$deletedCounts['categories']}");
            
            // Mostrar estatísticas finais
            $this->showCurrentStats($userId, "Estatísticas após limpeza:");
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->error("❌ Erro durante a limpeza: " . $e->getMessage());
            Log::error('Erro na limpeza da plataforma', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 1;
        }
        
        return 0;
    }
    
    /**
     * Mostra estatísticas atuais da plataforma
     */
    private function showCurrentStats(?string $userId, string $title = "Estatísticas atuais:"): void
    {
        $this->info("\n📊 {$title}");
        
        $transactionQuery = Transaction::query();
        $categoryQuery = Category::query();
        
        if ($userId) {
            $transactionQuery->where('user_id', $userId);
            $categoryQuery->where('user_id', $userId);
        }
        
        $transactionCount = $transactionQuery->count();
        $categoryCount = $categoryQuery->count();
        $userCount = User::count();
        $accountCount = Account::when($userId, function($q, $userId) {
            return $q->where('user_id', $userId);
        })->count();
        
        $this->line("   👥 Usuários: {$userCount}");
        $this->line("   🏦 Contas: {$accountCount}");
        $this->line("   💰 Transações: {$transactionCount}");
        $this->line("   📂 Categorias: {$categoryCount}");
        
        if ($transactionCount > 0) {
            $totalValue = $transactionQuery->sum('amount') / 100; // Converter centavos para reais
            $this->line("   💵 Valor total: R$ " . number_format($totalValue, 2, ',', '.'));
        }
    }
    
    /**
     * Apaga transações
     */
    private function clearTransactions(?string $userId): int
    {
        $this->line("🗑️  Apagando transações...");
        
        $query = Transaction::query();
        
        if ($userId) {
            $query->where('user_id', $userId);
        }
        
        $count = $query->count();
        
        if ($count > 0) {
            // Apagar em lotes para evitar problemas de memória
            $batchSize = 1000;
            $deleted = 0;
            
            while (true) {
                $batch = $query->limit($batchSize)->get();
                
                if ($batch->isEmpty()) {
                    break;
                }
                
                foreach ($batch as $transaction) {
                    $transaction->delete();
                    $deleted++;
                }
                
                $this->line("   Progresso: {$deleted}/{$count} transações apagadas");
            }
        }
        
        return $count;
    }
    
    /**
     * Apaga categorias
     */
    private function clearCategories(?string $userId): int
    {
        $this->line("🗑️  Apagando categorias...");

        $query = Category::query();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $count = $query->count();

        if ($count > 0) {
            // Verificar se a coluna 'system' existe
            $hasSystemColumn = \Schema::hasColumn('categories', 'system');

            if ($hasSystemColumn) {
                // Apagar categorias não-sistema primeiro
                $nonSystemQuery = clone $query;
                $nonSystemQuery->where('system', false);
                $nonSystemCount = $nonSystemQuery->delete();

                // Apagar categorias sistema se especificado
                $systemQuery = clone $query;
                $systemQuery->where('system', true);
                $systemCount = $systemQuery->delete();

                $this->line("   Categorias não-sistema apagadas: {$nonSystemCount}");
                $this->line("   Categorias sistema apagadas: {$systemCount}");
            } else {
                // Se não há coluna system, apagar todas as categorias
                $deletedCount = $query->delete();
                $this->line("   Categorias apagadas: {$deletedCount}");
            }
        }

        return $count;
    }
}
