<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', 'current');
        
        // Define o período baseado na seleção
        switch ($period) {
            case 'last':
                $startDate = now()->subMonth()->startOfMonth();
                $endDate = now()->subMonth()->endOfMonth();
                break;
            case 'year':
                $startDate = now()->startOfYear();
                $endDate = now()->endOfYear();
                break;
            default: // current
                $startDate = now()->startOfMonth();
                $endDate = now()->endOfMonth();
                break;
        }

        // Totais apenas de transações pagas no período selecionado
        $totalIncome = Transaction::where('type', 'income')
            ->where('status', 'paid')
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('value');
        
        $totalExpenses = Transaction::where('type', 'expense')
            ->where('status', 'paid')
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('value');
        
        $balance = $totalIncome - $totalExpenses;

        // Cálculo da variação em relação ao período anterior
        $previousStartDate = $startDate->copy()->subMonth();
        $previousEndDate = $endDate->copy()->subMonth();

        $previousIncome = Transaction::where('type', 'income')
            ->where('status', 'paid')
            ->whereBetween('date', [$previousStartDate, $previousEndDate])
            ->sum('value');

        $previousExpenses = Transaction::where('type', 'expense')
            ->where('status', 'paid')
            ->whereBetween('date', [$previousStartDate, $previousEndDate])
            ->sum('value');

        // Calcula as variações percentuais
        $incomeVariation = $previousIncome > 0 ? (($totalIncome - $previousIncome) / $previousIncome) * 100 : 0;
        $expensesVariation = $previousExpenses > 0 ? (($totalExpenses - $previousExpenses) / $previousExpenses) * 100 : 0;
        $balanceVariation = $previousIncome - $previousExpenses != 0 ? 
            ((($totalIncome - $totalExpenses) - ($previousIncome - $previousExpenses)) / abs($previousIncome - $previousExpenses)) * 100 : 0;

        // Transações de hoje
        $today = now()->format('Y-m-d');
        $todayIncomes = Transaction::with(['category', 'account'])
            ->where('type', 'income')
            ->whereDate('date', $today)
            ->orderBy('status', 'asc')
            ->orderBy('date')
            ->get();

        $todayExpenses = Transaction::with(['category', 'account'])
            ->where('type', 'expense')
            ->whereDate('date', $today)
            ->orderBy('status', 'asc')
            ->orderBy('date')
            ->get();

        // Transações de amanhã
        $tomorrow = now()->addDay()->format('Y-m-d');
        $tomorrowIncomes = Transaction::with(['category', 'account'])
            ->where('type', 'income')
            ->whereDate('date', $tomorrow)
            ->orderBy('status', 'asc')
            ->orderBy('date')
            ->get();

        $tomorrowExpenses = Transaction::with(['category', 'account'])
            ->where('type', 'expense')
            ->whereDate('date', $tomorrow)
            ->orderBy('status', 'asc')
            ->orderBy('date')
            ->get();

        // Transações pendentes dos próximos 7 dias
        $nextWeek = now()->addDays(7)->format('Y-m-d');
        $pendingIncomes = Transaction::with(['category', 'account'])
            ->where('type', 'income')
            ->where('status', 'pending')
            ->whereBetween('date', [now()->format('Y-m-d'), $nextWeek])
            ->orderBy('date')
            ->get();

        $pendingExpenses = Transaction::with(['category', 'account'])
            ->where('type', 'expense')
            ->where('status', 'pending')
            ->whereBetween('date', [now()->format('Y-m-d'), $nextWeek])
            ->orderBy('date')
            ->get();

        return view('dashboard.index', compact(
            'period',
            'totalIncome',
            'totalExpenses',
            'balance',
            'incomeVariation',
            'expensesVariation',
            'balanceVariation',
            'todayIncomes',
            'todayExpenses',
            'tomorrowIncomes',
            'tomorrowExpenses',
            'pendingIncomes',
            'pendingExpenses'
        ));
    }
} 