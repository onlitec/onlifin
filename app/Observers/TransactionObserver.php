<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Models\Account;

class TransactionObserver
{
    /**
     * Recalcula o saldo da conta após criar ou atualizar uma transação.
     */
    public function saved(Transaction $transaction): void
    {
        $account = $transaction->account;
        if ($account) {
            $account->recalculateBalance();
            $account->saveQuietly();
        }
    }

    /**
     * Recalcula o saldo da conta após excluir uma transação.
     */
    public function deleted(Transaction $transaction): void
    {
        $account = Account::find($transaction->account_id);
        if ($account) {
            $account->recalculateBalance();
            $account->saveQuietly();
        }
    }
}
