<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Account;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        
        // Saldo Atual Total (soma de todas as contas)
        // $currentBalance = Account::where('user_id', $userId)->sum('balance'); // Comentado: Coluna 'balance' não existe na tabela accounts
        // Calcular a partir das transações (mais preciso se balance da conta não for atualizado em tempo real)
        $currentBalance = Transaction::where('user_id', $userId)
                                ->where('status', 'paid')
                                ->sum(DB::raw('CASE WHEN type = \'income\' THEN amount ELSE -amount END'));
        
        // Totais do período selecionado (Apenas transações pagas)
        $queryPeriod = Transaction::where('user_id', $userId)
            ->where('status', 'paid')
            ->whereBetween('date', [$startDate, $endDate]);
            
        $totalIncomePeriod = (clone $queryPeriod)->where('type', 'income')->sum('amount');
        $totalExpensesPeriod = (clone $queryPeriod)->where('type', 'expense')->sum('amount');
        $balancePeriod = $totalIncomePeriod - $totalExpensesPeriod;
        
        // Variação Percentual (se aplicável)
        $incomeVariation = 0;
        $expensesVariation = 0;
        $balanceVariation = 0;
        if ($previousStartDate && $previousEndDate) {
             $queryPreviousPeriod = Transaction::where('user_id', $userId)
                 ->where('status', 'paid')
                 ->whereBetween('date', [$previousStartDate, $previousEndDate]);

            $previousIncome = (clone $queryPreviousPeriod)->where('type', 'income')->sum('amount');
            $previousExpenses = (clone $queryPreviousPeriod)->where('type', 'expense')->sum('amount');
            $previousBalance = $previousIncome - $previousExpenses;

            $incomeVariation = $previousIncome != 0 ? (($totalIncomePeriod - $previousIncome) / abs($previousIncome)) * 100 : ($totalIncomePeriod > 0 ? 100 : 0);
            $expensesVariation = $previousExpenses != 0 ? (($totalExpensesPeriod - $previousExpenses) / abs($previousExpenses)) * 100 : ($totalExpensesPeriod > 0 ? 100 : 0);
            $balanceVariation = $previousBalance != 0 ? (($balancePeriod - $previousBalance) / abs($previousBalance)) * 100 : ($balancePeriod != 0 ? 100 : 0);
        }
        
        // --- DADOS PARA GRÁFICOS --- 

        // 1. Despesas por Categoria (Período Selecionado)
        $expensesByCategory = Transaction::where('transactions.user_id', $userId)
            ->where('transactions.type', 'expense')
            ->where('status', 'paid')
            ->whereBetween('date', [$startDate, $endDate])
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->select('categories.name as category_name', DB::raw('SUM(transactions.amount) as total_amount'))
            ->groupBy('categories.name')
            ->orderBy('total_amount', 'desc')
            ->limit(10) // Limitar para não poluir o gráfico
            ->get();

        // Preparar dados para Chart.js (Despesas)
        $expenseChartLabels = $expensesByCategory->pluck('category_name');
        $expenseChartData = $expensesByCategory->pluck('total_amount')->map(fn($amount) => $amount / 100); // Converter centavos para reais

        // 2. Receitas por Categoria (Período Selecionado)
        $incomeByCategory = Transaction::where('transactions.user_id', $userId)
            ->where('transactions.type', 'income')
            ->where('status', 'paid')
            ->whereBetween('date', [$startDate, $endDate])
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->select('categories.name as category_name', DB::raw('SUM(transactions.amount) as total_amount'))
            ->groupBy('categories.name')
            ->orderBy('total_amount', 'desc')
            ->limit(10)
            ->get();
        
        // Preparar dados para Chart.js (Receitas)
        $incomeChartLabels = $incomeByCategory->pluck('category_name');
        $incomeChartData = $incomeByCategory->pluck('total_amount')->map(fn($amount) => $amount / 100);
        
        // 3. Saldo ao Longo do Tempo (Ex: Últimos 30 dias - pode ser ajustado pelo período)
        //   -> Calcular saldo diário pode ser pesado. Fazer para o mês atual como exemplo?
        $balanceOverTimeLabels = [];
        $balanceOverTimeData = [];
        // $runningBalance = Account::where('user_id', $userId)->sum('opening_balance'); // Coluna não existe
        // Calcular saldo inicial somando todas as transações ANTES do início do período do gráfico (mês atual)
        $startOfMonth = now()->startOfMonth();
        $runningBalance = Transaction::where('user_id', $userId)
                               ->where('status', 'paid')
                               ->where('date', '<', $startOfMonth)
                               ->sum(DB::raw('CASE WHEN type = \'income\' THEN amount ELSE -amount END'));
                               
        // Para simplificar, vamos calcular o fluxo do mês atual
        $today = now();
        $transactionsThisMonth = Transaction::where('user_id', $userId)
                                ->where('status', 'paid')
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

        // --- DADOS ADICIONAIS (Transações recentes, pendentes, etc.) --- 
        // Manter ou remover as buscas por transações de hoje/amanhã/pendentes?
        // Por enquanto, vamos manter.
        $todayIncomes = Transaction::with(['category', 'account'])->where('user_id', $userId)->where('type', 'income')->whereDate('date', now()->toDateString())->orderBy('status', 'asc')->orderBy('date')->get();
        $todayExpenses = Transaction::with(['category', 'account'])->where('user_id', $userId)->where('type', 'expense')->whereDate('date', now()->toDateString())->orderBy('status', 'asc')->orderBy('date')->get();
        $nextWeek = now()->addDays(7)->toDateString();
        $pendingIncomes = Transaction::with(['category', 'account'])->where('user_id', $userId)->where('type', 'income')->where('status', 'pending')->whereBetween('date', [now()->toDateString(), $nextWeek])->orderBy('date')->get();
        $pendingExpenses = Transaction::with(['category', 'account'])->where('user_id', $userId)->where('type', 'expense')->where('status', 'pending')->whereBetween('date', [now()->toDateString(), $nextWeek])->orderBy('date')->get();


        // Passar todos os dados para a view
        return view('dashboard', compact(
            'period',
            'currentBalance',
            'totalIncomePeriod',
            'totalExpensesPeriod',
            'balancePeriod',
            'incomeVariation',
            'expensesVariation',
            'balanceVariation',
            'expenseChartLabels',
            'expenseChartData',
            'incomeChartLabels',
            'incomeChartData',
            'balanceOverTimeLabels',
            'balanceOverTimeData',
            'todayIncomes',
            'todayExpenses',
            'pendingIncomes',
            'pendingExpenses'
        ));
    }
} 