<?php

namespace App\Livewire\Transactions;

use Livewire\Component;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\Account;
use Livewire\WithPagination;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

class Expenses extends Component
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

    public function mount()
    {
        $this->month = $this->month ?: now()->month;
        $this->year = $this->year ?: now()->year;
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
        $this->confirmingDeletion = true;
    }

    public function cancelDelete()
    {
        $this->confirmingDeletion = false;
        $this->transactionToDelete = null;
    }

    public function deleteTransaction()
    {
        try {
            DB::beginTransaction();

            $transaction = Transaction::findOrFail($this->transactionToDelete);
            
            if ($transaction->user_id !== auth()->id()) {
                throw new \Exception('Você não tem permissão para excluir esta transação.');
            }

            if ($transaction->type !== 'expense') {
                throw new \Exception('Esta transação não é uma despesa.');
            }

            $transaction->delete();
            
            DB::commit();

            LivewireAlert::success('Sucesso!', 'Despesa excluída com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            LivewireAlert::error('Erro!', 'Erro ao excluir despesa: ' . $e->getMessage());
        }

        $this->confirmingDeletion = false;
        $this->transactionToDelete = null;
    }

    public function render()
    {
        $transactions = Transaction::with(['category', 'account'])
            ->where('type', 'expense')
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

        $total = Transaction::where('type', 'expense')
            ->where('user_id', auth()->id())
            ->whereMonth('date', $this->month)
            ->whereYear('date', $this->year)
            ->sum('value');

        return view('livewire.transactions.expenses', [
            'transactions' => $transactions,
            'total' => $total,
            'categories' => Category::where('type', 'expense')->get(),
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