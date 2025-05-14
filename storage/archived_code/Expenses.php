namespace App\Http\Livewire\Transactions;

use Livewire\Component;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\Account;
use Livewire\WithPagination;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class Expenses extends Component
{
    use WithPagination;

    public $month;
    public $year;
    public $search = '';
    public $perPage = 20;
    public $sortField = 'date';
    public $sortDirection = 'desc';
    public $confirmingDeletion = false;
    public $transactionToDelete;
    public $isAdmin = false;

    protected $listeners = [
        'swal:confirm' => 'confirmDelete',
        'swal:success' => '$refresh',
        'swal:error' => '$refresh'
    ];

    public function mount()
    {
        $this->month = now()->month;
        $this->year = now()->year;
        $this->isAdmin = auth()->check() && auth()->user()->is_admin;
        
        // Debug para verificar o valor
        \Log::info("Expenses component initialized. isAdmin: " . ($this->isAdmin ? 'true' : 'false'));
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

    public function render()
    {
        try {
            $query = Transaction::with(['category', 'account']);
            
            // Se for admin, também carrega os dados do usuário
            if ($this->isAdmin) {
                $query->with('user');
            }
            
            // Aplicar filtros básicos
            $query->where('type', 'expense')
                ->whereMonth('date', $this->month)
                ->whereYear('date', $this->year);
                
            // Filtrar por usuário se não for administrador
            if (!$this->isAdmin) {
                $query->where('user_id', auth()->id());
            }
            
            // Aplicar filtro de pesquisa
            if ($this->search) {
                $query->where(function($q) {
                    $q->where('description', 'like', '%' . $this->search . '%')
                      ->orWhere('fornecedor', 'like', '%' . $this->search . '%')
                      ->orWhereHas('category', function($q) {
                          $q->where('name', 'like', '%' . $this->search . '%');
                      })
                      ->orWhereHas('account', function($q) {
                          $q->where('name', 'like', '%' . $this->search . '%');
                      });
                });
            }
            
            // Ordenar e paginar resultados
            $transactions = $query->orderBy($this->sortField, $this->sortDirection)
                ->paginate($this->perPage);

            // Inicializar as variáveis com valores padrão
            $total = 0;
            $totalPending = 0;
            
            // Calcular totais (também aplicando filtro de usuário)
            $totalQuery = Transaction::where('type', 'expense')
                ->whereMonth('date', $this->month)
                ->whereYear('date', $this->year);
                
            $pendingQuery = Transaction::where('type', 'expense')
                ->where('status', '!=', 'paid')
                ->whereMonth('date', $this->month)
                ->whereYear('date', $this->year);
                
            // Aplicar filtro de usuário aos totais
            if (!$this->isAdmin) {
                $totalQuery->where('user_id', auth()->id());
                $pendingQuery->where('user_id', auth()->id());
            }
            
            $total = $totalQuery->sum('amount');
            $totalPending = $pendingQuery->sum('amount');

            // Obter contas filtradas por usuário se não for admin
            $accounts = $this->isAdmin 
                ? Account::orderBy('name')->get() 
                : Account::where('user_id', auth()->id())->orderBy('name')->get();
                
            // Log para debug
            \Log::info("Expenses total: {$total}, totalPending: {$totalPending}");

            return view('livewire.transactions.expenses', [
                'transactions' => $transactions,
                'total' => $total,
                'totalPending' => $totalPending,
                'categories' => Category::where('type', 'expense')->get(),
                'accounts' => $accounts,
                'isAdmin' => $this->isAdmin,
                'year' => $this->year,
                'month' => $this->month,
            ]);
        } catch (\Exception $e) {
            \Log::error("Error in Expenses component: " . $e->getMessage());
            return view('livewire.transactions.expenses', [
                'transactions' => collect(),
                'total' => 0,
                'totalPending' => 0,
                'categories' => collect(),
                'accounts' => collect(),
                'isAdmin' => $this->isAdmin,
                'year' => $this->year,
                'month' => $this->month,
            ]);
        }
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

    /**
     * Reset pagination when per-page count changes.
     */
    public function updatedPerPage()
    {
        $this->resetPage();
    }
} 