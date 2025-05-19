<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Banking\BankAccount;
use App\Models\Company;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FinancialDataService
{
    /**
     * Obtém as transações recentes do usuário
     */
    public function getRecentTransactions(int $limit = 10): Collection
    {
        $user = Auth::user();
        
        if (!$user || !$user->currentCompany) {
            Log::warning('Usuário não tem empresa associada', [
                'user_id' => $user?->id
            ]);
            return collect([]);
        }

        return Transaction::with(['category', 'account'])
            ->where('company_id', $user->currentCompany->id)
            ->orderBy('date', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'date' => $transaction->date->format('d/m/Y'),
                    'description' => $transaction->description,
                    'amount' => $transaction->amount / 100, // Convertendo de centavos para reais
                    'type' => $transaction->type,
                    'category' => $transaction->category?->name,
                    'account' => $transaction->account?->name,
                    'contact' => $transaction->type === 'income' ? $transaction->cliente : $transaction->fornecedor,
                ];
            });
    }

    /**
     * Obtém o saldo atual de todas as contas bancárias
     */
    public function getBankAccountsBalance(): Collection
    {
        $user = Auth::user();
        
        if (!$user || !$user->currentCompany) {
            Log::warning('Usuário não tem empresa associada', [
                'user_id' => $user?->id
            ]);
            return collect([]);
        }

        return BankAccount::with('account')
            ->where('company_id', $user->currentCompany->id)
            ->where('enabled', true)
            ->get()
            ->map(function ($bankAccount) {
                return [
                    'id' => $bankAccount->id,
                    'name' => $bankAccount->account->name,
                    'mask' => $bankAccount->mask,
                    'type' => $bankAccount->type->value,
                    'balance' => $bankAccount->account->current_balance,
                    'currency' => $bankAccount->account->currency_code,
                ];
            });
    }

    /**
     * Obtém o resumo financeiro
     */
    public function getFinancialSummary(): array
    {
        $user = Auth::user();
        
        if (!$user || !$user->currentCompany) {
            Log::warning('Usuário não tem empresa associada', [
                'user_id' => $user?->id
            ]);
            return [
                'total_income' => 0,
                'total_expenses' => 0,
                'net_income' => 0,
                'period' => 'Últimos 30 dias',
            ];
        }

        $transactions = Transaction::where('company_id', $user->currentCompany->id)
            ->where('date', '>=', now()->subDays(30))
            ->get();

        $totalIncome = $transactions
            ->where('type', 'income')
            ->sum('amount');

        $totalExpenses = $transactions
            ->where('type', 'expense')
            ->sum('amount');

        return [
            'total_income' => $totalIncome / 100, // Convertendo de centavos para reais
            'total_expenses' => $totalExpenses / 100, // Convertendo de centavos para reais
            'net_income' => ($totalIncome - $totalExpenses) / 100, // Convertendo de centavos para reais
            'period' => 'Últimos 30 dias',
        ];
    }
} 