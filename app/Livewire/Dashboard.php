<?php

namespace App\Livewire;

use Livewire\Component;
use Carbon\Carbon;
use App\Models\Transaction;

/**
 * ATENÇÃO: CONFIGURAÇÃO CRÍTICA
 * 
 * Este componente contém lógica financeira essencial para o cálculo de saldos.
 * NÃO MODIFICAR sem consultar o documento FINANCIAL_RULES.md.
 * 
 * REGRA IMPORTANTE: Apenas transações com status 'paid' devem ser consideradas
 * no cálculo de saldos e estatísticas financeiras. Transações pendentes não
 * devem afetar os saldos das contas.
 */
class Dashboard extends Component
{
    public function render()
    {
        $currentMonth = Carbon::now()->month;
        
        $expenses = Transaction::where('user_id', auth()->id())
            ->where('type', 'expense')  // Filtra apenas despesas
            ->where('status', 'paid')   // CONFIGURAÇÃO CRÍTICA: Apenas transações pagas afetam o saldo
            ->whereMonth('date', $currentMonth)
            ->orderBy('date', 'desc')
            ->get();
            
        $incomes = Transaction::where('user_id', auth()->id())
            ->where('type', 'income')   // Filtra apenas receitas
            ->where('status', 'paid')   // CONFIGURAÇÃO CRÍTICA: Apenas transações pagas afetam o saldo
            ->whereMonth('date', $currentMonth)
            ->orderBy('date', 'desc')
            ->get();
            
        $totalExpenses = $expenses->sum('amount');
        $totalIncomes = $incomes->sum('amount');
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