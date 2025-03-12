namespace App\Http\Livewire\Transactions;

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
    public $perPage = 10;
    public $sortField = 'date';
    public $sortDirection = 'desc';
    public $confirmingDeletion = false;
    public $transactionToDelete;

    protected $listeners = ['delete' => 'deleteTransaction'];

    public function mount()
    {
        $this->month = now()->month;
        $this->year = now()->year;
    }

    public function previousMonth()
    {
        $date = Carbon::createFromDate($this->year, $this->month, 1)->subMonth();
        $this->month = $date->month;
        $this->year = $date->year;
    }

    public function nextMonth()
    {
        $date = Carbon::createFromDate($this->year, $this->month, 1)->addMonth();
        $this->month = $date->month;
        $this->year = $date->year;
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
            if ($transaction && $transaction->type === 'expense') {
                $transaction->delete();
                session()->flash('message', 'Despesa excluída com sucesso!');
            }
        }
        $this->confirmingDeletion = false;
        $this->transactionToDelete = null;
    }

    public function markAsPaid($transactionId)
    {
        $transaction = Transaction::find($transactionId);
        if ($transaction && $transaction->type === 'expense') {
            $transaction->status = 'paid';
            $transaction->save();
            session()->flash('message', 'Despesa marcada como paga com sucesso!');
        }
    }

    public function render()
    {
        $transactions = Transaction::with(['category', 'account'])
            ->where('type', 'expense')
            ->whereMonth('date', $this->month)
            ->whereYear('date', $this->year)
            ->when($this->search, function($query) {
                $query->where('description', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        $total = Transaction::where('type', 'expense')
            ->whereMonth('date', $this->month)
            ->whereYear('date', $this->year)
            ->sum('amount');

        $totalPending = Transaction::where('type', 'expense')
            ->where('status', '!=', 'paid')
            ->whereMonth('date', $this->month)
            ->whereYear('date', $this->year)
            ->sum('amount');

        return view('livewire.transactions.expenses', [
            'transactions' => $transactions,
            'total' => $total,
            'totalPending' => $totalPending,
            'categories' => Category::where('type', 'expense')->get(),
            'accounts' => Account::all(),
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