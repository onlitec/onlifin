<?php

namespace App\Livewire;

use Livewire\Component;
use Carbon\Carbon;
use App\Models\Transaction;

class Dashboard extends Component
{
    public function render()
    {
        $currentMonth = Carbon::now()->month;
        
        $expenses = Transaction::where('user_id', auth()->id())
            ->where('type', 'expense')  // Filtra apenas despesas
            ->whereMonth('date', $currentMonth)
            ->orderBy('date', 'desc')
            ->get();
            
        $incomes = Transaction::where('user_id', auth()->id())
            ->where('type', 'income')   // Filtra apenas receitas
            ->whereMonth('date', $currentMonth)
            ->orderBy('date', 'desc')
            ->get();
            
        $totalExpenses = $expenses->sum('value');
        $totalIncomes = $incomes->sum('value');
        $balance = $totalIncomes - $totalExpenses;
        
        return view('livewire.dashboard', [
            'expenses' => $expenses,
            'incomes' => $incomes,
            'totalExpenses' => $totalExpenses,
            'totalIncomes' => $totalIncomes,
            'balance' => $balance
        ])->layout('layouts.app');
    }
} 