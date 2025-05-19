<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Category;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\TransactionAIService;
use App\Services\FinanceSummaryAIService;

/*
 * ATENÇÃO: CORREÇÕES CRÍTICAS nas funções create(), edit() e no endpoint de categorias.
 * NÃO ALTERAR ESSA LÓGICA SEM AUTORIZAÇÃO EXPLÍCITA.
 */

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $filter = $request->query('filter', 'all');
        $sort = $request->query('sort', 'date');
        $direction = $request->query('direction', 'desc');
        $query = Transaction::with(['category', 'account']);

        if (!$user->hasPermission('view_all_transactions')) {
            if ($user->hasPermission('view_own_transactions')) {
                $query->where('user_id', $user->id);
            } else {
                abort(403, 'Você não tem permissão para visualizar transações.');
            }
        }
        
        // isAdmin variable for the view, if needed for UI elements not strictly for data access
        $isAdminView = $user->hasRole('Administrador'); 

        if ($filter === 'income') {
            $query->where('type', 'income');
        } elseif ($filter === 'expense') {
            $query->where('type', 'expense');
        } elseif ($filter === 'paid') {
            $query->where('status', 'paid');
        } elseif ($filter === 'pending') {
            $query->where('status', 'pending');
        }
        
        $allowedSorts = ['date', 'description', 'amount'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'date';
        }
        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'desc';
        }
        $query->orderBy($sort, $direction);
        $transactions = $query->paginate(10)
            ->appends(['filter' => $filter, 'sort' => $sort, 'direction' => $direction]);

        return view('transactions.index', compact('transactions', 'filter', 'isAdminView', 'sort', 'direction'));
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasPermission('create_transactions')) {
            abort(403, 'Você não tem permissão para criar transações.');
        }

        $type = $request->type ?? 'expense';
        $categories = Category::where('type', $type)->orderBy('name')->get();
        
        $accountsQuery = Account::where('active', true);
        if (!$user->hasPermission('view_all_accounts')) { // Assuming admin can see all accounts for selection
            $accountsQuery->where('user_id', $user->id);
        }
        $accounts = $accountsQuery->orderBy('name')->get();
        
        $isAdminView = $user->hasRole('Administrador');
        
        return view('transactions.create', compact('categories', 'accounts', 'type', 'isAdminView'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasPermission('create_transactions')) {
            abort(403, 'Você não tem permissão para criar transações.');
        }

        // Log do request para debug
        \Log::info('Request completo:', $request->all());

        try {
            $validated = $request->validate([
                'type' => 'required|in:income,expense',
                'status' => 'required|in:pending,paid',
                'date' => 'required|date',
                'description' => 'required|string|max:255',
                'amount' => 'required',
                'category_id' => 'required|exists:categories,id',
                'account_id' => 'required|exists:accounts,id',
                'notes' => 'nullable|string',
                'recurrence_type' => 'nullable|in:none,fixed,installment',
                'installment_number' => 'nullable|required_if:recurrence_type,installment|integer|min:1',
                'total_installments' => 'nullable|required_if:recurrence_type,installment|integer|min:1',
                'next_date' => 'nullable|required_unless:recurrence_type,none|date',
                'cliente' => 'nullable|string|max:255',
                'fornecedor' => 'nullable|string|max:255',
            ]);

            $amount = $validated['amount'];
            
            if (($validated['type'] ?? null) === 'income') {
                $validated['cliente'] = $request->input('cliente', null);
                $validated['fornecedor'] = null;
            } elseif (($validated['type'] ?? null) === 'expense') {
                $validated['fornecedor'] = $request->input('fornecedor', null);
                $validated['cliente'] = null;
            } else {
                $validated['cliente'] = null;
                $validated['fornecedor'] = null;
            }

            $transactionData = [
                'type' => $validated['type'],
                'status' => $validated['status'],
                'date' => $validated['date'],
                'description' => $validated['description'],
                'amount' => $amount,
                'category_id' => $validated['category_id'],
                'account_id' => $validated['account_id'],
                'notes' => $validated['notes'] ?? null,
                'cliente' => $validated['cliente'],
                'fornecedor' => $validated['fornecedor'],
                'user_id' => $user->id, // Assign to current user
            ];

            if (isset($validated['recurrence_type']) && $validated['recurrence_type'] !== 'none') {
                $transactionData['recurrence_type'] = $validated['recurrence_type'];
                $transactionData['next_date'] = $validated['next_date'];
                
                if ($validated['recurrence_type'] === 'installment') {
                    $transactionData['installment_number'] = $validated['installment_number'];
                    $transactionData['total_installments'] = $validated['total_installments'];
                }
            } else {
                $transactionData['recurrence_type'] = 'none';
            }

            $transaction = Transaction::create($transactionData);

            $redirectRoute = $validated['type'] === 'income' ? 'transactions.income' : 'transactions.expenses';
            return redirect()->route($redirectRoute)
                ->with('success', 'Transação criada com sucesso!');
        } catch (\Exception $e) {
            \Log::error('Erro ao salvar transação: ' . $e->getMessage());
            return redirect()->back()->withInput()->withErrors(['error' => 'Erro ao salvar a transação: ' . $e->getMessage()]);
        }
    }

    public function edit(Transaction $transaction)
    {
        $user = Auth::user();
        $canEditAll = $user->hasPermission('edit_all_transactions');
        $canEditOwn = $user->hasPermission('edit_own_transactions');

        if (!($canEditAll || ($canEditOwn && $transaction->user_id === $user->id))) {
            abort(403, 'Você não tem permissão para editar esta transação.');
        }

        $categories = Category::where('type', $transaction->type)->orderBy('name')->get();
        
        $accountsQuery = Account::where('active', true);
        // If user can't view all accounts, restrict to their own, or if the transaction's account is theirs.
        // This logic might need refinement based on whether admin editing another user's transaction should see all accounts or just the target user's accounts.
        // For now, if admin, show all. If not admin, show own.
        if (!$user->hasPermission('view_all_accounts')) { 
            $accountsQuery->where('user_id', $user->id);
        }
        $accounts = $accountsQuery->orderBy('name')->get();
        
        $isAdminView = $user->hasRole('Administrador');

        return view('transactions.edit', compact('transaction', 'categories', 'accounts', 'isAdminView'));
    }

    public function update(Request $request, Transaction $transaction)
    {
        $user = Auth::user();
        $canEditAll = $user->hasPermission('edit_all_transactions');
        $canEditOwn = $user->hasPermission('edit_own_transactions');

        if (!($canEditAll || ($canEditOwn && $transaction->user_id === $user->id))) {
            abort(403, 'Você não tem permissão para atualizar esta transação.');
        }

        $validatedData = $request->validate([
            'type' => 'required|in:income,expense',
            'status' => 'required|in:pending,paid',
            'date' => 'required|date',
            'description' => 'required|string|max:255',
            'amount' => 'required',
            'category_id' => 'required|exists:categories,id',
            'account_id' => 'required|exists:accounts,id',
            'notes' => 'nullable|string',
            'cliente' => 'nullable|string|max:255',
            'fornecedor' => 'nullable|string|max:255',
        ]);

        $validatedData['cliente'] = $request->input('cliente', null);
        $validatedData['fornecedor'] = $request->input('fornecedor', null);
        if (( $validatedData['type'] ?? null) === 'income') {
            $validatedData['cliente'] = $request->input('cliente', null);
            $validatedData['fornecedor'] = null;
        } elseif (( $validatedData['type'] ?? null) === 'expense') {
            $validatedData['fornecedor'] = $request->input('fornecedor', null);
            $validatedData['cliente'] = null;
        } else {
            $validatedData['cliente'] = null;
            $validatedData['fornecedor'] = null;
        }

        $amount = $validatedData['amount'];
        
        $transaction->type = $validatedData['type'];
        $transaction->status = $validatedData['status'];
        $transaction->date = $validatedData['date'];
        $transaction->description = $validatedData['description'];
        $transaction->amount = $amount;
        $transaction->category_id = $validatedData['category_id'];
        $transaction->account_id = $validatedData['account_id'];
        $transaction->notes = $validatedData['notes'] ?? null;
        $transaction->cliente = $validatedData['cliente'];
        $transaction->fornecedor = $validatedData['fornecedor'];
        
        // user_id is not changed on update by default unless specifically handled for admins re-assigning transactions.
        // For now, we assume user_id remains the same.

        $transaction->save();
        
        $redirectRoute = $validatedData['type'] === 'income' ? 'transactions.income' : 'transactions.expenses';
        return redirect()->route($redirectRoute)
            ->with('success', 'Transação atualizada com sucesso!');
    }

    public function destroy(Transaction $transaction)
    {
        $user = Auth::user();
        $canDeleteAll = $user->hasPermission('delete_all_transactions');
        $canDeleteOwn = $user->hasPermission('delete_own_transactions');

        if (!($canDeleteAll || ($canDeleteOwn && $transaction->user_id === $user->id))) {
            abort(403, 'Você não tem permissão para excluir esta transação.');
        }

        try {
            $transaction->delete();
            return redirect()->route('transactions.index')->with('success', 'Transação excluída com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erro ao excluir transação: ' . $e->getMessage());
        }
    }

    /**
     * ATENÇÃO: CONFIGURAÇÃO CRÍTICA
     * 
     * Marcar uma transação como paga afeta diretamente o saldo da conta.
     * Esta função altera o status da transação para 'paid', o que faz
     * com que ela seja incluída no cálculo de saldo da conta relacionada.
     * Ver FINANCIAL_RULES.md para mais detalhes sobre esta regra.
     */
    public function markAsPaid(Transaction $transaction)
    {
        $user = Auth::user();
        $canMarkAll = $user->hasPermission('mark_as_paid_all_transactions');
        $canMarkOwn = $user->hasPermission('mark_as_paid_own_transactions');

        if (!($canMarkAll || ($canMarkOwn && $transaction->user_id === $user->id))) {
            abort(403, 'Você não tem permissão para alterar o status desta transação.');
        }
        
        $transaction->status = 'paid';
        $transaction->save();

        return redirect()->back()->with('success', 'Transação marcada como paga!');
    }

    public function showIncome()
    {
        if (!Auth::user()->hasPermission('view_own_transactions') && !Auth::user()->hasPermission('view_all_transactions')) {
            abort(403, 'Acesso negado.');
        }
        return view('transactions.income');
    }

    public function showExpenses()
    {
        if (!Auth::user()->hasPermission('view_own_transactions') && !Auth::user()->hasPermission('view_all_transactions')) {
            abort(403, 'Acesso negado.');
        }
        return view('transactions.expenses');
    }

    public function createNext(Transaction $transaction)
    {
        $user = Auth::user();
        // Only the owner of the transaction should be able to create a recurring one from it.
        // And they need create_transactions permission.
        if (!($user->hasPermission('create_transactions') && $transaction->user_id === $user->id)) {
            abort(403, 'Você não tem permissão para criar uma transação recorrente a partir desta.');
        }

        if (!$transaction->hasRecurrence() || !$transaction->next_date) {
            return back()->with('error', 'Esta transação não possui configuração de recorrência válida.');
        }

        $newTransaction = $transaction->replicate();
        $newTransaction->date = $transaction->next_date;
        $nextDate = (clone $transaction->next_date)->addMonth();
        
        if ($transaction->isInstallmentRecurrence()) {
            if ($transaction->installment_number >= $transaction->total_installments) {
                return back()->with('error', 'Todas as parcelas desta transação já foram criadas.');
            }
            $newTransaction->installment_number = $transaction->installment_number + 1;
            if ($newTransaction->installment_number >= $newTransaction->total_installments) {
                $newTransaction->recurrence_type = 'none';
                $newTransaction->next_date = null;
            } else {
                $newTransaction->next_date = $nextDate;
            }
        } else {
            $newTransaction->next_date = $nextDate;
        }
        
        $newTransaction->status = 'pending';
        $newTransaction->user_id = $user->id; // Ensure new transaction is owned by current user
        $newTransaction->save();
        
        $transaction->next_date = null;
        if ($transaction->isInstallmentRecurrence() && $newTransaction->installment_number >= $transaction->total_installments) {
            $transaction->recurrence_type = 'none';
        }
        $transaction->save();
        
        return back()->with('success', 'Próxima transação recorrente criada com sucesso!');
    }

    /**
     * Endpoint para sugerir categoria de transação via IA
     */
    public function suggestCategory(Request $request, TransactionAIService $aiService)
    {
        $request->validate([
            'description' => 'required|string|min:3',
        ]);
        $category = $aiService->suggestCategory($request->description);
        return response()->json([
            'suggested_category' => $category
        ]);
    }

    /**
     * Exibe painel de resumo inteligente de despesas/receitas.
     */
    public function dashboardSummary(FinanceSummaryAIService $aiService)
    {
        $user = auth()->user();
        $transactions = $user->transactions()->select('description', 'amount', 'category_id', 'date')
            ->with('category:id,name')
            ->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])
            ->get()
            ->map(function($t) {
                return [
                    'descricao' => $t->description,
                    'valor' => $t->amount / 100,
                    'categoria' => $t->category ? $t->category->name : null,
                    'data' => $t->date->format('Y-m-d'),
                ];
            })->toArray();
        $summary = $aiService->generateSummary($transactions);
        return view('transactions.summary', compact('summary'));
    }
} 