<?php
/*
--------------------------------------------------------------------------
ATENÇÃO!
--------------------------------------------------------------------------
Este arquivo e seu conteúdo foram ajustados e corrigidos.
Qualquer alteração subsequente deve ser feita com autorização explícita
para evitar a quebra de funcionalidades implementadas.

Última modificação por: Assistente AI
Data da modificação: [DATA DA ALTERAÇÃO ATUAL]
--------------------------------------------------------------------------
*/

namespace App\Livewire\Transactions;

use Livewire\Component;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\Account;
use Livewire\WithPagination;
use Carbon\Carbon;

class Expenses extends Component
{
    use WithPagination;
    
    public $month;
    public $year;
    public $search = '';
    public $perPage = 20;
    public $sortField = 'date';
    public $sortDirection = 'desc';
    public $isAdmin = false;
    public $confirmingDeletion = false;
    public $transactionToDelete;
    
    public function mount()
    {
        $this->month = now()->month;
        $this->year = now()->year;
        $this->isAdmin = auth()->check() && auth()->user()->is_admin;
    }
    
    public function previousMonth()
    {
        $this->resetPage();
        $date = Carbon::createFromDate($this->year, $this->month, 1)->subMonth();
        $this->month = $date->month;
        $this->year = $date->year;
    }

    public function nextMonth()
    {
        $this->resetPage();
        $date = Carbon::createFromDate($this->year, $this->month, 1)->addMonth();
        $this->month = $date->month;
        $this->year = $date->year;
    }
    
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }
    
    public function confirmDelete($transactionId)
    {
        $this->transactionToDelete = $transactionId;
        $this->confirmingDeletion = true;
    }

    public function cancelDelete()
    {
        $this->confirmingDeletion = false;
        $this->transactionToDelete = null;
    }

    public function deleteTransaction()
    {
        if ($this->transactionToDelete) {
            $transaction = Transaction::find($this->transactionToDelete);
            
            // Verificar permissão: admin ou proprietário da transação
            if ($transaction && $transaction->type === 'expense' && 
                ($this->isAdmin || $transaction->user_id === auth()->id())) {
                $transaction->delete();
                session()->flash('message', 'Despesa excluída com sucesso!');
            } else {
                session()->flash('error', 'Você não tem permissão para excluir esta despesa.');
            }
        }
        $this->confirmingDeletion = false;
        $this->transactionToDelete = null;
    }
    
    public function markAsPaid($transactionId)
    {
        $transaction = Transaction::find($transactionId);
        // Verificar permissão: admin ou proprietário da transação
        if ($transaction && $transaction->type === 'expense' && 
            ($this->isAdmin || $transaction->user_id === auth()->id())) {
            $transaction->status = 'paid';
            $transaction->save();
            session()->flash('message', 'Despesa marcada como paga com sucesso!');
        } else {
            session()->flash('error', 'Você não tem permissão para alterar esta despesa.');
        }
    }
    
    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Transaction::query()
            ->where('type', 'expense');
            
        if (!$this->isAdmin) {
            $query->where('user_id', auth()->id());
        }
        
        if ($this->search) {
            $query->where('description', 'like', '%' . $this->search . '%');
        }
        
        if ($this->month && $this->year) {
            $query->whereMonth('date', $this->month)
                  ->whereYear('date', $this->year);
        }
        
        $transactions = $query->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
            
        // Calculate totals
        $totalQuery = clone $query;
        $pendingQuery = clone $query;
        $pendingQuery->where('status', '!=', 'paid');
        
        $total = $totalQuery->sum('amount');
        $totalPending = $pendingQuery->sum('amount');
            
        return view('livewire.transactions.expenses', [
            'transactions' => $transactions,
            'total' => $total,
            'totalPending' => $totalPending,
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
            'isAdmin' => $this->isAdmin,
            'confirmingDeletion' => $this->confirmingDeletion,
            'year' => $this->year,
            'month' => $this->month,
        ]);
    }
}
