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
    public $accountFilter = '';
    public $categoryFilter = '';
    public $statusFilter = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $supplierFilter = '';
    public $sortField = 'date';
    public $sortDirection = 'desc';
    public $isAdmin = false;
    
    // Filtro de recorrência vindo da URL (para o menu)
    public $recurrence; 

    // Filtro de recorrência da UI (dropdown)
    public $recurrenceFilter = '';

    public $confirmingDeletion = false;
    public $transactionToDelete;
    
    public function mount($recurrence = null)
    {
        $this->month = now()->month;
        $this->year = now()->year;
        $this->isAdmin = auth()->check() && auth()->user()->is_admin;
        
        // Se um filtro de recorrência for passado pela URL, aplica-o
        if ($recurrence) {
            $this->recurrenceFilter = $recurrence;
        }
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

    public function updatedAccountFilter() { $this->resetPage(); }
    public function updatedCategoryFilter() { $this->resetPage(); }
    public function updatedStatusFilter() { $this->resetPage(); }
    public function updatedDateFrom() { $this->resetPage(); }
    public function updatedDateTo() { $this->resetPage(); }
    public function updatedSupplierFilter() { $this->resetPage(); }
    public function updatedRecurrenceFilter() { $this->resetPage(); }

    public function render()
    {
        $query = Transaction::query()
            ->where('type', 'expense');
            
        // Aplica o filtro de recorrência a partir do dropdown da UI
        if ($this->recurrenceFilter === 'fixed') {
            $query->where('recurrence_type', 'fixed');
        } elseif ($this->recurrenceFilter === 'installment') {
            $query->where('recurrence_type', 'installment');
        }
        
        if (!$this->isAdmin) {
            $query->where('user_id', auth()->id());
        }
        
        // Filter by current company
        $currentCompany = auth()->user()->currentCompany;
        if ($currentCompany) {
            $query->where('company_id', $currentCompany->id);
        }
        
        $query->when($this->accountFilter, fn($q) => $q->where('account_id', $this->accountFilter))
               ->when($this->categoryFilter, fn($q) => $q->where('category_id', $this->categoryFilter))
               ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
               ->when($this->dateFrom, fn($q) => $q->whereDate('date', '>=', $this->dateFrom))
               ->when($this->dateTo, fn($q) => $q->whereDate('date', '<=', $this->dateTo))
               ->when($this->supplierFilter, fn($q) => $q->where('fornecedor', 'like', "%{$this->supplierFilter}%"));
        
        $query->when($this->search, function($q) {
            $q->where(function($qq) {
                $qq->where('description', 'like', "%{$this->search}%")
                   ->orWhere('fornecedor', 'like', "%{$this->search}%")
                   ->orWhereHas('category', fn($c) => $c->where('name', 'like', "%{$this->search}%"))
                   ->orWhereHas('account', fn($c) => $c->where('name', 'like', "%{$this->search}%"));
            });
        });
        
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
        
        // Calculate transaction count
        $transactionCount = $query->count();
            
        return view('livewire.transactions.expenses', [
            'transactions' => $transactions,
            'total' => $total,
            'totalPending' => $totalPending,
            'transactionCount' => $transactionCount,
            'accounts' => Account::where('active', true)->get(),
            'categories' => Category::where('type', 'expense')->get(),
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
            'isAdmin' => $this->isAdmin,
            'confirmingDeletion' => $this->confirmingDeletion,
            'year' => $this->year,
            'month' => $this->month,
        ]);
    }

    // ATENÇÃO: Filtros de transações implementados via solicitação do usuário. Não modificar sem autorização explícita.
    /**
     * Reseta todos os filtros para valores iniciais e volta à página 1
     */
    public function resetFilters()
    {
        $this->reset(['search', 'accountFilter', 'categoryFilter', 'statusFilter', 'dateFrom', 'dateTo', 'supplierFilter', 'recurrenceFilter']);
        $this->resetPage();
    }

    // ATENÇÃO: Filtros de transações implementados via solicitação do usuário. Não modificar sem autorização explícita.
    /**
     * Aplica os filtros manualmente e reseta a paginação
     */
    public function applyFilters()
    {
        $this->resetPage();
    }
}
