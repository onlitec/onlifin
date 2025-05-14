<?php

namespace App\Observers;

use App\Models\Account;

/**
 * ATENÇÃO: CONFIGURAÇÃO CRÍTICA
 * 
 * Este observer gerencia automaticamente o recálculo de saldos de contas.
 * NÃO MODIFICAR sem consultar o documento FINANCIAL_RULES.md.
 * Alterações neste arquivo podem causar inconsistências nos saldos das contas.
 */
class AccountObserver
{
    /**
     * ATENÇÃO: CONFIGURAÇÃO CRÍTICA - NÃO MODIFICAR
     *
     * Handle the Account "created" event.
     * Garante que contas novas tenham current_balance = initial_balance.
     */
    public function created(Account $account): void
    {
        // Ensure current_balance is set to initial_balance for new accounts
        if (is_null($account->current_balance)) {
            $account->current_balance = $account->initial_balance ?? 0;
            $account->save();
        }
    }

    /**
     * ATENÇÃO: CONFIGURAÇÃO CRÍTICA - NÃO MODIFICAR
     *
     * Handle the Account "updated" event.
     * Garante que o saldo atual seja recalculado quando o saldo inicial for alterado.
     */
    public function updated(Account $account): void
    {
        // If initial_balance was changed but current_balance wasn't
        if ($account->isDirty('initial_balance') && !$account->isDirty('current_balance')) {
            // Check if this account has no transactions, in which case current_balance should match initial_balance
            if ($account->transactions()->count() === 0) {
                $account->current_balance = $account->initial_balance;
                // Use saveQuietly to avoid triggering the updated event again
                $account->saveQuietly();
            } else {
                // If there are transactions, recalculate the balance
                $account->recalculateBalance();
                $account->saveQuietly();
            }
        }
    }

    /**
     * Handle the Account "deleted" event.
     */
    public function deleted(Account $account): void
    {
        //
    }

    /**
     * Handle the Account "restored" event.
     */
    public function restored(Account $account): void
    {
        //
    }

    /**
     * Handle the Account "force deleted" event.
     */
    public function forceDeleted(Account $account): void
    {
        //
    }
}
