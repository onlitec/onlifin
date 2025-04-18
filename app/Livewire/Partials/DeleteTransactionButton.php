<?php

namespace App\Livewire\Partials;

use Livewire\Component;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

class DeleteTransactionButton extends Component
{
    public $transactionId;
    public $confirming = false;
    public $transaction = null;

    // Registra eventos que este componente escuta
    protected $listeners = [
        'deleteConfirmed' => 'deleteTransaction',
        'refreshComponent' => '$refresh',
    ];

    public function mount($transactionId)
    {
        $this->transactionId = $transactionId;
        // Carrega a transação para ter as informações disponíveis se necessário
        $this->loadTransaction();
    }

    /**
     * Carrega a transação do banco de dados
     */
    protected function loadTransaction()
    {
        try {
            $this->transaction = Transaction::find($this->transactionId);
        } catch (\Exception $e) {
            // Silenciosamente falha se a transação não existir
        }
    }

    /**
     * Exibe o modal de confirmação de exclusão
     */
    public function confirmDelete()
    {
        // Se a transação não foi carregada, tenta carregar novamente
        if (!$this->transaction) {
            $this->loadTransaction();
        }
        
        $this->confirming = true;
    }

    /**
     * Cancela a exclusão e fecha o modal
     */
    public function cancel()
    {
        $this->confirming = false;
    }

    /**
     * Exclui a transação e notifica o componente pai
     */
    public function deleteTransaction()
    {
        try {
            DB::beginTransaction();
            
            // Carrega a transação do banco de dados para garantir que ela ainda existe
            $transaction = Transaction::findOrFail($this->transactionId);
            
            // Exclui a transação
            $transaction->delete();
            
            // Confirma a transação no banco de dados
            DB::commit();
            
            // Notifica sobre a exclusão bem-sucedida
            if (class_exists('Jantinnerezo\\LivewireAlert\\Facades\\LivewireAlert')) {
                LivewireAlert::success('Excluído!', 'Transação excluída com sucesso.');
            }
            
            // Notifica a página pai que a transação foi excluída para atualizar a lista
            $this->dispatch('transactionDeleted', $this->transactionId);
            
            // Refresh para todos os componentes
            $this->dispatch('refresh')->to('transactions.income');
            $this->dispatch('refresh')->to('transactions.expenses');
            $this->dispatch('refresh')->to('transactions.index');
            
            // Dispatch um evento global para garantir que todos os componentes sejam atualizados
            $this->dispatch('transaction-deleted');
            
            // Fecha o modal
            $this->confirming = false;
            
        } catch (\Exception $e) {
            // Desfaz a transação no banco de dados em caso de erro
            DB::rollBack();
            
            // Exibe mensagem de erro
            if (class_exists('Jantinnerezo\\LivewireAlert\\Facades\\LivewireAlert')) {
                LivewireAlert::error('Erro!', 'Erro ao excluir transação: ' . $e->getMessage());
            }
            
            // Registra o erro no log
            \Log::error('Erro ao excluir transação: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.partials.delete-transaction-button');
    }
}
