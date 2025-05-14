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
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

class Income extends TransactionBase
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
    public $clientFilter = '';
    public $sortField = 'date';
    public $sortDirection = 'desc';
    public $confirmingDeletion = false;
    public $transactionToDelete;
    public $deleteWarning = '';
    public $isAdmin = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'month' => ['except' => ''],
        'year' => ['except' => ''],
        'sortField' => ['except' => 'date'],
        'sortDirection' => ['except' => 'desc'],
    ];

    protected $listeners = [
        'swal:confirm' => 'confirmDelete',
        'swal:success' => '$refresh',
        'swal:error' => '$refresh',
        'refresh' => '$refresh',
        'transactionDeleted' => '$refresh'
    ];

    public function mount()
    {
        $this->month = $this->month ?: now()->month;
        $this->year = $this->year ?: now()->year;
        $this->isAdmin = auth()->check() && auth()->user()->is_admin;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function updatedAccountFilter()
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedDateFrom()
    {
        $this->resetPage();
    }

    public function updatedDateTo()
    {
        $this->resetPage();
    }

    public function updatedClientFilter()
    {
        $this->resetPage();
    }

    public function previousMonth()
    {
        $date = Carbon::createFromDate($this->year, $this->month, 1)->subMonth();
        $this->month = $date->month;
        $this->year = $date->year;
        $this->resetPage();
    }

    public function nextMonth()
    {
        $date = Carbon::createFromDate($this->year, $this->month, 1)->addMonth();
        $this->month = $date->month;
        $this->year = $date->year;
        $this->resetPage();
    }

    public function deleteTransaction($transactionId = null)
    {
        try {
            if ($transactionId === null) {
                $transactionId = $this->transactionToDelete;
            }
            
            if (!$transactionId) {
                throw new \Exception('Nenhuma transação selecionada para exclusão.');
            }
            
            DB::beginTransaction();
            
            $transaction = Transaction::findOrFail($transactionId);
            
            if (!$this->canDelete($transaction)) {
                throw new \Exception('Você não tem permissão para excluir esta transação.');
            }

            $transaction->delete();
            
            DB::commit();
            
            $this->dispatch('swal:success', [
                'title' => 'Receita excluída com sucesso!',
                'text' => 'A receita foi removida do sistema.',
                'toast' => true,
                'position' => 'top-right',
                'timer' => 3000,
                'showConfirmButton' => false
            ]);
            
            $this->confirmingDeletion = false;
            $this->transactionToDelete = null;
        } catch (\Exception $e) {
            DB::rollback();
            
            $this->dispatch('swal:error', [
                'title' => 'Erro ao excluir receita',
                'text' => $e->getMessage(),
                'toast' => true,
                'position' => 'top-right',
                'timer' => 3000,
                'showConfirmButton' => false
            ]);
        }
    }
    
    public function confirmDelete($data)
    {
        $this->confirmingDeletion = true;
        // suportar array ou ID direto
        if (is_array($data) && isset($data['transactionId'])) {
            $id = $data['transactionId'];
        } else {
            $id = $data;
        }
        $this->transactionToDelete = $id;
        // definir mensagem de aviso baseada no status da transação
        $transaction = Transaction::find($id);
        if ($transaction && $transaction->status === 'paid') {
            $this->deleteWarning = 'Esta receita já foi paga. Excluir pode afetar os cálculos do sistema. Deseja continuar?';
        } else {
            $this->deleteWarning = 'Tem certeza que deseja excluir esta receita? Esta ação não pode ser desfeita.';
        }
    }
    
    public function cancelDelete()
    {
        $this->confirmingDeletion = false;
        $this->transactionToDelete = null;
    }

    public function canDelete(Transaction $transaction)
    {
        // Verifica se o usuário tem permissão para excluir (proprietário ou admin)
        if (!$this->isAdmin && $transaction->user_id !== auth()->id()) {
            return false;
        }

        // Verifica se a transação já foi recebida
        if ($transaction->isPaid()) {
            return false;
        }

        return true;
    }

    public function loadTransactions()
    {
        $this->transactions = Transaction::where('user_id', auth()->id())
            ->where('type', 'income')
            ->whereYear('date', $this->year)
            ->whereMonth('date', $this->month)
            ->with(['category', 'account'])
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);
    }

    public function render()
    {
        $query = Transaction::with(['category', 'account'])
            ->where('type', 'income');
            
        if (!$this->isAdmin) {
            $query->where('user_id', auth()->id());
        }
        
        $query->whereMonth('date', $this->month)
            ->whereYear('date', $this->year)
            ->when($this->accountFilter, fn($q) => $q->where('account_id', $this->accountFilter))
            ->when($this->categoryFilter, fn($q) => $q->where('category_id', $this->categoryFilter))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->dateFrom, fn($q) => $q->whereDate('date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('date', '<=', $this->dateTo))
            ->when($this->clientFilter, fn($q) => $q->where('cliente', 'like', "%{$this->clientFilter}%"))
            ->when($this->search, function($query) {
                $query->where(function($q) {
                    $q->where('description', 'like', '%' . $this->search . '%')
                      ->orWhere('cliente', 'like', '%' . $this->search . '%')
                      ->orWhereHas('category', function($q) {
                          $q->where('name', 'like', '%' . $this->search . '%');
                      })
                      ->orWhereHas('account', function($q) {
                          $q->where('name', 'like', '%' . $this->search . '%');
                      });
                });
            });
            
        $transactions = $query->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        // Calculate totals
        $totalQuery = clone $query;
        $pendingQuery = clone $query;
        $pendingQuery->where('status', '!=', 'paid');
        
        $total = $totalQuery->sum('amount');
        $totalPending = $pendingQuery->sum('amount');

        return view('livewire.transactions.income', [
            'transactions' => $transactions,
            'total' => $total,
            'totalPending' => $totalPending,
            'categories' => Category::where('type', 'income')->get(),
            'accounts' => Account::where('active', true)->get(),
            'isAdmin' => $this->isAdmin,
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
            'year' => $this->year,
            'month' => $this->month,
            'confirmingDeletion' => $this->confirmingDeletion,
        ]);
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

    public function markAsPaid($transactionId)
    {
        try {
            $transaction = Transaction::findOrFail($transactionId);

            if (($transaction->user_id !== auth()->id() && !$this->isAdmin) || $transaction->type !== 'income') {
                return;
            }

            // Atualiza apenas o status, sem tocar no valor
            $transaction->status = 'paid';
            $transaction->save();

            // Notifica que houve uma atualização, mas sem exibir alerta
            $this->dispatch('refresh');
        } catch (\Exception $e) {
            \Log::error('Erro ao marcar receita como paga: ' . $e->getMessage());
        }
    }

    // ATENÇÃO: Filtros de transações implementados via solicitação do usuário. Não modificar sem autorização explícita.
    /**
     * Reseta todos os filtros para valores iniciais e volta à página 1
     */
    public function resetFilters()
    {
        $this->reset(['search', 'accountFilter', 'categoryFilter', 'statusFilter', 'dateFrom', 'dateTo', 'clientFilter']);
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