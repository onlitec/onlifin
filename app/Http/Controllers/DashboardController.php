<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Account;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ATENÇÃO: CONFIGURAÇÃO CRÍTICA
 * 
 * Este controller contém lógica financeira essencial para o cálculo de saldos no dashboard.
 * NÃO MODIFICAR o código de cálculo de saldos sem consultar o documento FINANCIAL_RULES.md.
 * Alterações neste arquivo podem causar inconsistências financeiras na plataforma.
 */
class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();
        $period = $request->get('period', 'current_month');
        
        // Define o período baseado na seleção
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();
        $previousStartDate = now()->subMonthNoOverflow()->startOfMonth();
        $previousEndDate = now()->subMonthNoOverflow()->endOfMonth();

        switch ($period) {
            case 'last_month':
                $startDate = $previousStartDate;
                $endDate = $previousEndDate;
                $previousStartDate = now()->subMonthsNoOverflow(2)->startOfMonth();
                $previousEndDate = now()->subMonthsNoOverflow(2)->endOfMonth();
                break;
            case 'current_year':
                $startDate = now()->startOfYear();
                $endDate = now()->endOfYear();
                $previousStartDate = now()->subYearNoOverflow()->startOfYear();
                $previousEndDate = now()->subYearNoOverflow()->endOfYear();
                break;
            case 'last_year':
                $startDate = now()->subYearNoOverflow()->startOfYear();
                $endDate = now()->subYearNoOverflow()->endOfYear();
                $previousStartDate = now()->subYearsNoOverflow(2)->startOfYear();
                $previousEndDate = now()->subYearsNoOverflow(2)->endOfYear();
                break;
            case 'all_time':
                // Buscar a data da primeira transação para definir o início
                $firstTransactionDate = Transaction::where('user_id', $userId)->min('date');
                $startDate = $firstTransactionDate ? Carbon::parse($firstTransactionDate)->startOfDay() : now()->startOfMonth();
                $endDate = now()->endOfDay(); // Até hoje
                $previousStartDate = null; // Não aplicável para todo o período
                $previousEndDate = null;
                break;
            // default 'current_month' já está definido
        }

        // --- DADOS PARA CARDS DE RESUMO ---
        
        /**
         * ATENÇÃO: CONFIGURAÇÃO CRÍTICA - NÃO MODIFICAR
         * 
         * O código a seguir implementa o cálculo do Saldo Atual Total conforme FINANCIAL_RULES.md.
         * Modificar este código pode causar discrepâncias entre o saldo total e os saldos individuais.
         */
        // Saldo Atual Total (soma de todas as contas) será calculado após recálculo dos saldos individuais
        // (removido bloco summaryAccounts para usar a coleção $accounts recarregada abaixo)
        
        /**
         * ATENÇÃO: CONFIGURAÇÃO CRÍTICA - NÃO MODIFICAR
         * 
         * As consultas abaixo devem sempre manter o filtro 'status' = 'paid'
         * Apenas transações PAGAS devem ser consideradas para cálculos de saldo.
         * Remover este filtro causará inconsistências financeiras em todo o sistema.
         * Ver FINANCIAL_RULES.md para mais detalhes sobre esta regra.
         */
        // Totais do período selecionado (Apenas transações pagas)
        $queryPeriod = Transaction::where('user_id', $userId)
            ->where('status', 'paid') // CONFIGURAÇÃO CRÍTICA: Apenas transações pagas
            ->whereBetween('date', [$startDate, $endDate]);
            
        // CORREÇÃO: Não precisamos dividir por 100 no template, então multiplicamos por 100 aqui
        // para que os valores sejam exibidos corretamente
        $totalIncomePeriod = (clone $queryPeriod)->where('type', 'income')->sum('amount');
        $totalExpensesPeriod = (clone $queryPeriod)->where('type', 'expense')->sum('amount');
        $balancePeriod = $totalIncomePeriod - $totalExpensesPeriod;
        
        // Inicializar o total de receitas e despesas do período anterior para exibição
        $previousIncomePeriod = 0;
        $previousExpensesPeriod = 0;
        // Inicializar o saldo do período anterior para exibição
        $previousBalancePeriod = 0;

        // Variação Percentual (se aplicável)
        $incomeVariation = 0;
        $expensesVariation = 0;
        $balanceVariation = 0;
        if ($previousStartDate && $previousEndDate) {
             $queryPreviousPeriod = Transaction::where('user_id', $userId)
                 ->where('status', 'paid') // CONFIGURAÇÃO CRÍTICA: Apenas transações pagas
                 ->whereBetween('date', [$previousStartDate, $previousEndDate]);

            $previousIncome = (clone $queryPreviousPeriod)->where('type', 'income')->sum('amount');
            // Atribuir total de receitas do período anterior para view
            $previousIncomePeriod = $previousIncome;
            $previousExpenses = (clone $queryPreviousPeriod)->where('type', 'expense')->sum('amount');
            // Atribuir total de despesas do período anterior para view
            $previousExpensesPeriod = $previousExpenses;
            $previousBalance = $previousIncome - $previousExpenses;
            // Atribuir saldo do período anterior para view
            $previousBalancePeriod = $previousBalance;

            $incomeVariation = $previousIncome != 0 ? (($totalIncomePeriod - $previousIncome) / abs($previousIncome)) * 100 : ($totalIncomePeriod > 0 ? 100 : 0);
            $expensesVariation = $previousExpenses != 0 ? (($totalExpensesPeriod - $previousExpenses) / abs($previousExpenses)) * 100 : ($totalExpensesPeriod > 0 ? 100 : 0);
            $balanceVariation = $previousBalance != 0 ? (($balancePeriod - $previousBalance) / abs($previousBalance)) * 100 : ($balancePeriod != 0 ? 100 : 0);
        }
        
        // --- DADOS PARA GRÁFICOS --- 

        // 1. Despesas por Categoria (Período Selecionado) - incluir todas as categorias existentes
        $expenseCategories = Category::where('user_id', $userId)->where('type', 'expense')->get();
        $expenseChartLabels = $expenseCategories->pluck('name');
        $expenseChartData = $expenseCategories->map(function($cat) use($userId, $startDate, $endDate) {
            $sum = Transaction::where('user_id', $userId)
                ->where('status', 'paid')
                ->where('type', 'expense')
                ->where('category_id', $cat->id)
            ->whereBetween('date', [$startDate, $endDate])
                ->sum('amount');
            return ($sum ?? 0) / 100;
        });

        // 2. Receitas por Categoria (Período Selecionado) - incluir todas as categorias existentes
        $incomeCategories = Category::where('user_id', $userId)->where('type', 'income')->get();
        $incomeChartLabels = $incomeCategories->pluck('name');
        $incomeChartData = $incomeCategories->map(function($cat) use($userId, $startDate, $endDate) {
            $sum = Transaction::where('user_id', $userId)
                ->where('status', 'paid')
                ->where('type', 'income')
                ->where('category_id', $cat->id)
            ->whereBetween('date', [$startDate, $endDate])
                ->sum('amount');
            return ($sum ?? 0) / 100;
        });
        
        // 3. Saldo ao Longo do Tempo (Ex: Últimos 30 dias - pode ser ajustado pelo período)
        //   -> Calcular saldo diário pode ser pesado. Fazer para o mês atual como exemplo?
        $balanceOverTimeLabels = [];
        $balanceOverTimeData = [];
        // $runningBalance = Account::where('user_id', $userId)->sum('opening_balance'); // Coluna não existe
        // Calcular saldo inicial somando todas as transações ANTES do início do período do gráfico (mês atual)
        $startOfMonth = now()->startOfMonth();
        $runningBalance = Transaction::where('user_id', $userId)
                               ->where('status', 'paid') // CONFIGURAÇÃO CRÍTICA: Apenas transações pagas
                               ->where('date', '<', $startOfMonth)
                               ->sum(DB::raw('CASE WHEN type = \'income\' THEN amount ELSE -amount END'));
                               
        // Para simplificar, vamos calcular o fluxo do mês atual
        $today = now();
        $transactionsThisMonth = Transaction::where('user_id', $userId)
                                ->where('status', 'paid') // CONFIGURAÇÃO CRÍTICA: Apenas transações pagas
                                ->whereBetween('date', [$startOfMonth, $today])
                                ->orderBy('date')
                                ->select('date', 'amount', 'type')
                                ->get()
                                ->groupBy(fn($date) => Carbon::parse($date->date)->format('d/m')); // Agrupa por dia
        
        $currentDate = $startOfMonth->copy();
        while($currentDate->lte($today)) {
            $dayKey = $currentDate->format('d/m');
            $balanceOverTimeLabels[] = $dayKey;
            if(isset($transactionsThisMonth[$dayKey])) {
                foreach($transactionsThisMonth[$dayKey] as $t) {
                    $runningBalance += ($t->type === 'income' ? $t->amount : -$t->amount);
                }
            }
            $balanceOverTimeData[] = $runningBalance / 100; // Saldo no final do dia em Reais
            $currentDate->addDay();
        }

        // 4. NOVO: Previsão de Saldo (próximos 30 dias)
        $balanceForecastLabels = [];
        $balanceForecastData = [];
        
        // Último saldo conhecido (atual)
        $lastKnownBalance = end($balanceOverTimeData) ?: 0; // Valor padrão se não houver dados
        
        // Próximos 30 dias
        $forecastStartDate = now()->addDay(); // Começar amanhã
        $forecastEndDate = now()->addDays(30); // Próximos 30 dias
        
        // Buscar transações futuras (programadas/pendentes)
        $futurePendingTransactions = Transaction::where('user_id', $userId)
            ->where('status', 'pending')
            ->whereBetween('date', [$forecastStartDate, $forecastEndDate])
            ->orderBy('date')
            ->select('date', 'amount', 'type')
            ->get()
            ->groupBy(fn($date) => Carbon::parse($date->date)->format('d/m'));
        
        // Projetar o saldo para os próximos 30 dias
        $forecastDate = $forecastStartDate->copy();
        $forecastBalance = $lastKnownBalance;
        
        while($forecastDate->lte($forecastEndDate)) {
            $dayKey = $forecastDate->format('d/m');
            $balanceForecastLabels[] = $dayKey;
            
            // Adicionar transações pendentes programadas para este dia
            if(isset($futurePendingTransactions[$dayKey])) {
                foreach($futurePendingTransactions[$dayKey] as $t) {
                    $forecastBalance += ($t->type === 'income' ? $t->amount : -$t->amount) / 100;
                }
            }
            
            $balanceForecastData[] = $forecastBalance;
            $forecastDate->addDay();
        }
        
        // 5. NOVO: Despesas por Conta Bancária
        // Primeiro, buscar todas as contas do usuário
        $userAccounts = Account::where('user_id', $userId)->orderBy('name')->get();

        // Preparar dados para Chart.js (Despesas por Conta)
        // Mapear sobre cada conta para obter o total de despesas
        $accountExpenseLabels = $userAccounts->pluck('name');
        $accountExpenseData = $userAccounts->map(function($account) use ($userId, $startDate, $endDate) {
            $sum = Transaction::where('user_id', $userId)
                ->where('account_id', $account->id)
                ->where('type', 'expense')
                ->where('status', 'paid') // CONFIGURAÇÃO CRÍTICA: Apenas transações pagas
                ->whereBetween('date', [$startDate, $endDate])
                ->sum('amount');
            return ($sum ?? 0) / 100; // Retornar soma em Reais, ou 0 se não houver despesas
        });

        // 6. NOVO: Receitas por Conta Bancária
        // Preparar dados para Chart.js (Receitas por Conta)
        $accountIncomeLabels = $userAccounts->pluck('name');
        $accountIncomeData = $userAccounts->map(function($account) use ($userId, $startDate, $endDate) {
            $sum = Transaction::where('user_id', $userId)
                ->where('account_id', $account->id)
                ->where('type', 'income')
                ->where('status', 'paid') // CONFIGURAÇÃO CRÍTICA: Apenas transações pagas
                ->whereBetween('date', [$startDate, $endDate])
                ->sum('amount');
            return ($sum ?? 0) / 100; // Retornar soma em Reais, ou 0 se não houver receitas
        });

        // 6. NOVO: Receitas vs Despesas ao Longo do Período
        $incomeExpenseTrendLabels = [];
        $incomeTrendData = [];
        $expenseTrendData = [];

        Log::info('--- Debug Receitas vs Despesas Trend ---');
        Log::info('Start Date: ' . $startDate->toDateString());
        Log::info('End Date: ' . $endDate->toDateString());

        // Determinar a granularidade (diária ou mensal) baseado na duração do período
        $periodDurationInDays = $startDate->diffInDays($endDate);
        $granularityFormat = 'Y-m-d'; // Default: Diário
        $dbDateFormat = '%Y-%m-%d';
        if ($periodDurationInDays > 62) { // Se > ~2 meses, agrupar por mês
            $granularityFormat = 'Y-m';
            $dbDateFormat = '%Y-%m';
        }

        // Buscar transações pagas no período
        $periodTransactions = Transaction::where('user_id', $userId)
            ->where('status', 'paid') // CONFIGURAÇÃO CRÍTICA: Apenas transações pagas
            ->whereBetween('date', [$startDate, $endDate])
            ->select(
                DB::raw("DATE_FORMAT(date, '$dbDateFormat') as period_key"),
                'type',
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('period_key', 'type')
            ->orderBy('period_key')
            ->get();

        Log::info('Period Transactions Query Result:', $periodTransactions->toArray());

        // Agrupar por período (dia ou mês)
        $groupedTransactions = $periodTransactions->groupBy('period_key');

        // Iterar sobre o período para preencher os dados
        $trendDate = $startDate->copy();
        $endDateLoop = $endDate->copy(); // Usar cópia para não modificar o original

        while ($trendDate->lte($endDateLoop)) {
            $currentKey = $trendDate->format($granularityFormat);
            $incomeExpenseTrendLabels[] = $granularityFormat === 'Y-m-d' ? $trendDate->format('d/m') : $trendDate->format('m/Y'); // Formato do label

            $dailyIncome = 0;
            $dailyExpense = 0;

            if (isset($groupedTransactions[$currentKey])) {
                foreach ($groupedTransactions[$currentKey] as $transaction) {
                    if ($transaction->type === 'income') {
                        $dailyIncome = $transaction->total / 100; // Converter para Reais
                    } elseif ($transaction->type === 'expense') {
                        $dailyExpense = $transaction->total / 100; // Converter para Reais
                    }
                }
            }

            $incomeTrendData[] = $dailyIncome;
            $expenseTrendData[] = $dailyExpense;

            // Avançar para o próximo período
            if ($granularityFormat === 'Y-m-d') {
                $trendDate->addDay();
            } else {
                $trendDate->addMonthNoOverflow()->startOfMonth(); // Ir para o início do próximo mês
                 // Prevenir loop infinito se startDate e endDate estiverem no mesmo mês > 62 dias (caso raro)
                if ($trendDate->gt($endDateLoop) && $trendDate->format('Y-m') === $endDateLoop->format('Y-m')) {
                   break;
                }
            }
        }

        Log::info('IncomeExpenseTrendLabels:', $incomeExpenseTrendLabels);
        Log::info('IncomeTrendData:', $incomeTrendData);
        Log::info('ExpenseTrendData:', $expenseTrendData);
        Log::info('--- End Debug Receitas vs Despesas Trend ---');

        // --- DADOS ADICIONAIS (Transações recentes, pendentes, etc.) --- 
        // Manter ou remover as buscas por transações de hoje/amanhã/pendentes?
        // Por enquanto, vamos manter.
        $todayIncomes = Transaction::with(['category', 'account'])->where('user_id', $userId)->where('type', 'income')->whereDate('date', now()->toDateString())->orderBy('status', 'asc')->orderBy('date')->get();
        $todayExpenses = Transaction::with(['category', 'account'])->where('user_id', $userId)->where('type', 'expense')->whereDate('date', now()->toDateString())->orderBy('status', 'asc')->orderBy('date')->get();
        
        // NOVO: Buscar pendentes de hoje e amanhã
        $today = now()->toDateString();
        $tomorrow = now()->addDay()->toDateString();
        
        $pendingExpensesToday = Transaction::with(['category', 'account'])
            ->where('user_id', $userId)
            ->where('type', 'expense')
            ->where('status', 'pending')
            ->whereDate('date', $today)
            ->orderBy('date')
            ->get();
            
        $pendingExpensesTomorrow = Transaction::with(['category', 'account'])
            ->where('user_id', $userId)
            ->where('type', 'expense')
            ->where('status', 'pending')
            ->whereDate('date', $tomorrow)
            ->orderBy('date')
            ->get();
            
        $pendingIncomesToday = Transaction::with(['category', 'account'])
            ->where('user_id', $userId)
            ->where('type', 'income')
            ->where('status', 'pending')
            ->whereDate('date', $today)
            ->orderBy('date')
            ->get();
            
        $pendingIncomesTomorrow = Transaction::with(['category', 'account'])
            ->where('user_id', $userId)
            ->where('type', 'income')
            ->where('status', 'pending')
            ->whereDate('date', $tomorrow)
            ->orderBy('date')
            ->get();
        
        /**
         * ATENÇÃO: CONFIGURAÇÃO CRÍTICA - NÃO MODIFICAR
         * 
         * O código a seguir também recalcula os saldos das contas para exibição.
         * Isto deve ser mantido para garantir consistências nos dados exibidos.
         */
        // Buscar contas do usuário - se for admin, mostra todas as contas
        $isAdmin = auth()->check() && auth()->user()->is_admin;
        
        if ($isAdmin) {
            // Administradores veem todas as contas
            $accounts = Account::with('user')
                ->orderBy('name')
                ->get();
        } else {
            // Usuários normais veem apenas suas próprias contas
            $accounts = Account::where('user_id', $userId)
                ->orderBy('name')
                ->get();
        }
            
        // Certifica-se que cada conta tem seu saldo recalculado
        foreach ($accounts as $account) {
            $account->recalculateBalance();
        }
        
        // CORREÇÃO: Calcular Saldo Atual Total após recálculo dos saldos individuais
        $currentBalance = $accounts->sum(function($acct) {
            return ($acct->current_balance ?? $acct->initial_balance ?? 0);
        });
        
        // Verifica se as contas foram realmente carregadas para debug
        \Log::info('Contas carregadas: ' . $accounts->count() . ' (Admin: ' . ($isAdmin ? 'Sim' : 'Não') . ')');
        \Log::info('Usuário ID: ' . $userId);
        \Log::info('Saldo Atual Total recalculado: ' . $currentBalance);

        // Passar todos os dados para a view
        return view('dashboard', compact(
            'period',
            'currentBalance',
            'totalIncomePeriod',
            'previousIncomePeriod',
            'previousExpensesPeriod',
            'totalExpensesPeriod',
            'balancePeriod',
            'previousBalancePeriod',
            'incomeVariation',
            'expensesVariation',
            'balanceVariation',
            'expenseChartLabels',
            'expenseChartData',
            'incomeChartLabels',
            'incomeChartData',
            'balanceOverTimeLabels',
            'balanceOverTimeData',
            'balanceForecastLabels',
            'balanceForecastData',
            'accountExpenseLabels',
            'accountExpenseData',
            'accountIncomeLabels',
            'accountIncomeData',
            'todayIncomes',
            'todayExpenses',
            'incomeExpenseTrendLabels',
            'incomeTrendData',
            'expenseTrendData',
            'pendingExpensesToday',
            'pendingExpensesTomorrow',
            'pendingIncomesToday',
            'pendingIncomesTomorrow',
            'accounts'
        ));
    }
}