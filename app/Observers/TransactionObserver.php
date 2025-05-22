<?php

namespace App\Observers;

use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransactionObserver
{
    /**
     * Handle the Transaction "created" event.
     */
    public function created(Transaction $transaction): void
    {
        $user = Auth::user();
        $message = "Usuário {$user->name} (ID: {$user->id}) criou uma nova transação: " .
                  "{$transaction->type} de R$ " . number_format($transaction->amount / 100, 2, ',', '.') . 
                  " na categoria {$transaction->category->name} na conta {$transaction->account->name} " .
                  "em {$transaction->date->format('d/m/Y')}";
        
        Log::info($message, [
            'action' => 'create',
            'user_id' => $user->id,
            'transaction_id' => $transaction->id,
            'amount' => $transaction->amount,
            'category' => $transaction->category->name,
            'account' => $transaction->account->name,
            'date' => $transaction->date->format('Y-m-d H:i:s')
        ]);

        $this->updateAccountBalance($transaction);
    }

    /**
     * Handle the Transaction "updated" event.
     */
    public function updated(Transaction $transaction): void
    {
        $user = Auth::user();
        $message = "Usuário {$user->name} (ID: {$user->id}) atualizou a transação ID: {$transaction->id}";
        
        Log::info($message, [
            'action' => 'update',
            'user_id' => $user->id,
            'transaction_id' => $transaction->id,
            'changes' => $transaction->getChanges(),
            'original' => $transaction->getOriginal()
        ]);

        $this->updateAccountBalance($transaction);
    }

    /**
     * Handle the Transaction "deleted" event.
     */
    public function deleted(Transaction $transaction): void
    {
        $user = Auth::user();
        $message = "Usuário {$user->name} (ID: {$user->id}) excluiu a transação ID: {$transaction->id}";
        
        Log::info($message, [
            'action' => 'delete',
            'user_id' => $user->id,
            'transaction_id' => $transaction->id,
            'transaction_data' => [
                'type' => $transaction->type,
                'amount' => $transaction->amount,
                'category' => $transaction->category->name,
                'account' => $transaction->account->name,
                'date' => $transaction->date->format('Y-m-d H:i:s')
            ]
        ]);

        $this->reverseAccountBalance($transaction);
    }

    /**
     * Handle the Transaction "restored" event.
     */
    public function restored(Transaction $transaction): void
    {
        $user = Auth::user();
        $message = "Usuário {$user->name} (ID: {$user->id}) restaurou a transação ID: {$transaction->id}";
        
        Log::info($message, [
            'action' => 'restore',
            'user_id' => $user->id,
            'transaction_id' => $transaction->id
        ]);
    }

    /**
     * Handle the Transaction "force deleted" event.
     */
    public function forceDeleted(Transaction $transaction): void
    {
        $user = Auth::user();
        $message = "Usuário {$user->name} (ID: {$user->id}) excluiu permanentemente a transação ID: {$transaction->id}";
        
        Log::info($message, [
            'action' => 'force_delete',
            'user_id' => $user->id,
            'transaction_id' => $transaction->id
        ]);
    }

    private function updateAccountBalance(Transaction $transaction)
    {
        $account = $transaction->account;
        
        // Convertendo de centavos para reais e aplicando sinal
        $amountReais = ($transaction->type === 'expense' ? -$transaction->amount : $transaction->amount) / 100;
        
        DB::transaction(function () use ($account, $amountReais) {
            $account->current_balance = $account->current_balance + $amountReais;
            $account->save();
        });
    }

    private function reverseAccountBalance(Transaction $transaction)
    {
        $account = $transaction->account;
        
        // Convertendo de centavos para reais e invertendo sinal
        $amountReais = ($transaction->type === 'expense' ? $transaction->amount : -$transaction->amount) / 100;
        
        DB::transaction(function () use ($account, $amountReais) {
            $account->current_balance = $account->current_balance + $amountReais;
            $account->save();
        });
    }
}
