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
    
    public function mount($transactionId)
    {
        $this->transactionId = $transactionId;
    }
    
    public function confirmDelete()
    {
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
            
            if (class_exists('Jantinnerezo\\LivewireAlert\\Facades\\LivewireAlert')) {
                LivewireAlert::success('Excluído!', 'Transação excluída com sucesso.');
            }
            
            // Notifica todos os componentes
            // $this->dispatch('transactionDeleted'); // Comentado
            // $this->dispatch('$refresh');         // Comentado
            
            // Fecha o modal
            // $this->confirming = false; // Comentado - o redirecionamento cuidará disso

            // Força um recarregamento da página atual
            return redirect(request()->header('Referer')); // Descomentado
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            if (class_exists('Jantinnerezo\\LivewireAlert\\Facades\\LivewireAlert')) {
                LivewireAlert::error('Erro!', 'Erro ao excluir transação: ' . $e->getMessage());
            }
            
            \Log::error('Erro ao excluir transação: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.transactions.delete-button');
    }
}
