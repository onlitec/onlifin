<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FinancialChatbotService;
use App\Services\AIConfigService;
use App\Models\User;
use App\Models\Transaction;

class DebugFinancialData extends Command
{
    protected $signature = 'debug:financial-data {--user-id=2}';
    protected $description = 'Debug dos dados financeiros coletados pelo chatbot';

    public function handle()
    {
        $this->info("🔍 DEBUG DOS DADOS FINANCEIROS DO CHATBOT");
        
        $userId = $this->option('user-id');
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("❌ Usuário não encontrado");
            return 1;
        }
        
        $this->info("👤 Usuário: {$user->name} (ID: {$user->id})");
        
        // 1. Dados diretos do banco
        $this->info("\n📊 1. DADOS DIRETOS DO BANCO:");
        
        $allTransactions = Transaction::where('user_id', $userId)->get();
        $this->line("   Total de transações: {$allTransactions->count()}");
        
        $totalReceitas = $allTransactions->where('type', 'income')->sum('amount');
        $totalDespesas = abs($allTransactions->where('type', 'expense')->sum('amount'));
        
        $this->line("   Total receitas: R$ " . number_format($totalReceitas, 2, ',', '.'));
        $this->line("   Total despesas: R$ " . number_format($totalDespesas, 2, ',', '.'));
        $this->line("   Saldo líquido: R$ " . number_format($totalReceitas - $totalDespesas, 2, ',', '.'));
        
        // 2. Dados coletados pelo FinancialChatbotService
        $this->info("\n🤖 2. DADOS COLETADOS PELO CHATBOT:");
        
        try {
            $aiConfigService = new AIConfigService();
            $financialChatbotService = new FinancialChatbotService($aiConfigService);
            
            // Usar reflexão para acessar método privado
            $reflection = new \ReflectionClass($financialChatbotService);
            $analyzeIntentMethod = $reflection->getMethod('analyzeIntent');
            $analyzeIntentMethod->setAccessible(true);
            
            $getFinancialDataMethod = $reflection->getMethod('getFinancialData');
            $getFinancialDataMethod->setAccessible(true);
            
            // Analisar intenção
            $intent = $analyzeIntentMethod->invoke($financialChatbotService, "olá");
            $this->line("   Intenção detectada: {$intent['primary']}");
            $this->line("   Período: {$intent['period']['type']}");
            $this->line("   Data início: {$intent['period']['start_date']->format('Y-m-d')}");
            $this->line("   Data fim: {$intent['period']['end_date']->format('Y-m-d')}");
            
            // Coletar dados financeiros
            $financialData = $getFinancialDataMethod->invoke($financialChatbotService, $user, $intent);
            
            $this->line("   Transações coletadas: " . count($financialData['transactions']));
            $this->line("   Total receitas (chatbot): R$ " . number_format($financialData['summary']['total_income'], 2, ',', '.'));
            $this->line("   Total despesas (chatbot): R$ " . number_format($financialData['summary']['total_expenses'], 2, ',', '.'));
            $this->line("   Saldo líquido (chatbot): R$ " . number_format($financialData['summary']['net_balance'], 2, ',', '.'));
            
            // 3. Comparar períodos
            $this->info("\n📅 3. ANÁLISE DE PERÍODO:");
            
            $periodStart = $intent['period']['start_date'];
            $periodEnd = $intent['period']['end_date'];
            
            $transactionsInPeriod = Transaction::where('user_id', $userId)
                ->whereBetween('date', [$periodStart, $periodEnd])
                ->get();
                
            $this->line("   Transações no período ({$periodStart->format('Y-m-d')} a {$periodEnd->format('Y-m-d')}): {$transactionsInPeriod->count()}");
            
            $receitasPeriodo = $transactionsInPeriod->where('type', 'income')->sum('amount');
            $despesasPeriodo = abs($transactionsInPeriod->where('type', 'expense')->sum('amount'));
            
            $this->line("   Receitas no período: R$ " . number_format($receitasPeriodo, 2, ',', '.'));
            $this->line("   Despesas no período: R$ " . number_format($despesasPeriodo, 2, ',', '.'));
            
            // 4. Listar transações do período
            $this->info("\n📋 4. TRANSAÇÕES NO PERÍODO:");
            
            foreach ($transactionsInPeriod->take(10) as $transaction) {
                $amount = number_format(abs($transaction->amount), 2, ',', '.');
                $type = $transaction->type === 'income' ? '💰' : '💸';
                $this->line("   {$type} {$transaction->date->format('Y-m-d')} | {$transaction->description} | R$ {$amount}");
            }
            
            if ($transactionsInPeriod->count() > 10) {
                $remaining = $transactionsInPeriod->count() - 10;
                $this->line("   ... e mais {$remaining} transação(ões)");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Erro ao coletar dados do chatbot: {$e->getMessage()}");
            $this->line("📍 Arquivo: {$e->getFile()}:{$e->getLine()}");
        }
        
        // 5. Verificar se há problema de período padrão
        $this->info("\n⚠️  5. POSSÍVEIS PROBLEMAS:");
        
        $lastMonth = now()->subMonth();
        $transactionsLastMonth = Transaction::where('user_id', $userId)
            ->whereBetween('date', [$lastMonth, now()])
            ->count();
            
        if ($transactionsLastMonth === 0) {
            $this->warn("   ⚠️  Nenhuma transação no último mês (período padrão do chatbot)");
            $this->info("   💡 O chatbot usa o último mês como período padrão");
        }
        
        $this->info("\n💡 RECOMENDAÇÕES:");
        $this->line("1. Verifique se as transações têm datas recentes");
        $this->line("2. O chatbot usa período padrão de 1 mês atrás");
        $this->line("3. Pergunte especificamente sobre períodos (ex: 'gastos este ano')");
        
        return 0;
    }
}
