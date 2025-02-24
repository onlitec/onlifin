<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Totais apenas de transações pagas
        $totalIncome = Transaction::where('type', 'income')
            ->where('status', 'paid')
            ->sum('amount');
        
        $totalExpenses = Transaction::where('type', 'expense')
            ->where('status', 'paid')
            ->sum('amount');
        
        $balance = $totalIncome - $totalExpenses;

        // Transações de hoje
        $today = now()->format('Y-m-d');
        $todayIncomes = Transaction::with(['category', 'account'])
            ->where('type', 'income')
            ->whereDate('date', $today)
            ->orderBy('status', 'asc') // Pendentes primeiro
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
            ->whereBetween('date', [now()->addDays(2)->format('Y-m-d'), $nextWeek])
            ->orderBy('date')
            ->get();

        $pendingExpenses = Transaction::with(['category', 'account'])
            ->where('type', 'expense')
            ->where('status', 'pending')
            ->whereBetween('date', [now()->addDays(2)->format('Y-m-d'), $nextWeek])
            ->orderBy('date')
            ->get();

        return view('dashboard.index', compact(
            'totalIncome',
            'totalExpenses',
            'balance',
            'todayIncomes',
            'todayExpenses',
            'tomorrowIncomes',
            'tomorrowExpenses',
            'pendingIncomes',
            'pendingExpenses'
        ));
    }
} 