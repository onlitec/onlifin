<?php

namespace App\Livewire\Transactions;

use Livewire\Component;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\Account;
use Livewire\Component;
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
    public $perPage = 10;
    public $sortField = 'date';
    public $sortDirection = 'desc';
    public $confirmingDeletion = false;
    public $transactionToDelete;

    protected $queryString = [
        'search' => ['except' => ''],
        'month' => ['except' => ''],
        'year' => ['except' => ''],
        'sortField' => ['except' => 'date'],
        'sortDirection' => ['except' => 'desc'],
    ];

    protected $listeners = ['delete' => 'deleteTransaction'];

    public function __construct()
    {
        $this->month = now()->month;
        $this->year = now()->year;
    }

    public function mount()
    {
        if (request()->has('month')) {
            $this->month = request()->query('month');
        }
        if (request()->has('year')) {
            $this->year = request()->query('year');
        }
    }

    public function updatingSearch()
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

    public function confirmDelete($transactionId)
    {
        $this->transactionToDelete = $transactionId;
        
        $this->dispatch('swal:confirm', [
            'transactionId' => $transactionId,
            'type' => 'receita'
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
                'title' => 'Receita excluída com sucesso!',
                'text' => 'A receita foi removida do sistema.',
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
                'title' => 'Erro ao excluir receita',
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
        // Verifica se o usuário tem permissão para excluir
        if (!auth()->user()->can('delete', $transaction)) {
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

    public function cancelDelete()
    {
        $this->transactionToDelete = null;
    }

    public function render()
    {
        $transactions = Transaction::with(['category', 'account'])
            ->where('type', 'income')
            ->where('user_id', auth()->id())
            ->whereMonth('date', $this->month)
            ->whereYear('date', $this->year)
            ->when($this->search, function($query) {
                $query->where(function($q) {
                    $q->where('description', 'like', '%' . $this->search . '%')
                      ->orWhereHas('category', function($q) {
                          $q->where('name', 'like', '%' . $this->search . '%');
                      })
                      ->orWhereHas('account', function($q) {
                          $q->where('name', 'like', '%' . $this->search . '%');
                      });
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        $total = Transaction::where('type', 'income')
            ->where('user_id', auth()->id())
            ->whereMonth('date', $this->month)
            ->whereYear('date', $this->year)
            ->sum('amount');

        return view('livewire.transactions.income', [
            'transactions' => $transactions,
            'total' => $total,
            'categories' => Category::where('type', 'income')->get(),
            'accounts' => Account::where('active', true)->get(),
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
} 