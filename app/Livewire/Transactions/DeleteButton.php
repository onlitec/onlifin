<?php

namespace App\Livewire\Transactions;

use Livewire\Component;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

class DeleteButton extends Component
{
    public $transactionId;
    public $confirming = false;
    
    protected $listeners = [
        'swal:confirm' => 'confirmDelete',
    ];

    public function mount($transactionId)
    {
        $this->transactionId = $transactionId;
    }
    
    public function confirmDelete($data)
    {
        $this->transactionId = $data['transactionId'];
        $this->confirming = true;
    }
    
    public function cancelDelete()
    {
        $this->confirming = false;
    }
    
    public function deleteTransaction()
    {
        try {
            DB::beginTransaction();
            
            $transaction = Transaction::findOrFail($this->transactionId);
            $transaction->delete();
            
            DB::commit();
            
            $this->dispatch('swal:success', [
                'title' => 'Excluído!',
                'text' => 'Transação excluída com sucesso.',
                'toast' => true,
                'position' => 'top-right',
                'timer' => 3000,
                'showConfirmButton' => false
            ]);
            
            $this->confirming = false;
            $this->transactionId = null;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->dispatch('swal:error', [
                'title' => 'Erro!',
                'text' => 'Erro ao excluir transação: ' . $e->getMessage(),
                'toast' => true,
                'position' => 'top-right',
                'timer' => 3000,
                'showConfirmButton' => false
            ]);
        }
    }

    public function render()
    {
        return view('livewire.transactions.delete-button');
    }
}
