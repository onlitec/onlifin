<?php

namespace App\Livewire\Transactions;

use App\Models\Transaction;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class TransactionBase extends Component
{
    public $transactionToDelete;
    public $confirmingDeletion = false;

    protected $listeners = [
        'swal:confirm' => 'confirmDelete',
    ];

    public function confirmDelete($data)
    {
        $this->transactionToDelete = $data['transactionId'];
        $this->dispatch('swal:confirm', [
            'transactionId' => $data['transactionId'],
            'type' => $data['type']
        ]);
    }

    public function deleteTransaction($transactionId)
    {
        try {
            DB::beginTransaction();
            
            $transaction = Transaction::findOrFail($transactionId);
            
            if (!$this->canDelete($transaction)) {
                throw new \Exception('Você não tem permissão para excluir esta transação.');
            }

            $transaction->delete();
            
            DB::commit();
            
            $this->dispatch('swal:success', [
                'title' => ucfirst($transaction->type) . ' excluída com sucesso!',
                'text' => 'A ' . $transaction->type . ' foi removida do sistema.',
                'toast' => true,
                'position' => 'top-right',
                'timer' => 3000,
                'showConfirmButton' => false
            ]);
            
            $this->loadTransactions();
            $this->transactionToDelete = null;
        } catch (\Exception $e) {
            DB::rollback();
            
            $this->dispatch('swal:error', [
                'title' => 'Erro ao excluir ' . $transaction->type,
                'text' => $e->getMessage(),
                'toast' => true,
                'position' => 'top-right',
                'timer' => 3000,
                'showConfirmButton' => false
            ]);
        }
    }

    public function canDelete(Transaction $transaction)
    {
        if (!auth()->user()->can('delete', $transaction)) {
            return false;
        }

        if ($transaction->isPaid()) {
            return false;
        }

        return true;
    }

    public function cancelDelete()
    {
        $this->transactionToDelete = null;
    }
}