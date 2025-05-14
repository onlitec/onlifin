<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Category;
use App\Models\Account;
use Illuminate\Http\Request;

/*
 * ATENÇÃO: CORREÇÕES CRÍTICAS nas funções create(), edit() e no endpoint de categorias.
 * NÃO ALTERAR ESSA LÓGICA SEM AUTORIZAÇÃO EXPLÍCITA.
 */

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        // Filter and sorting parameters
        $filter = $request->query('filter', 'all');
        $sort = $request->query('sort', 'date');
        $direction = $request->query('direction', 'desc');
        $query = Transaction::with(['category', 'account']);

        // Verifica se o usuário é administrador - com verificação segura
        $isAdmin = auth()->check() && auth()->user()->is_admin;
        
        // Se não for admin, filtra pelas transações do próprio usuário
        if (!$isAdmin) {
            $query->where('user_id', auth()->id());
        }
        
        // Continua com os filtros
        if ($filter === 'income') {
            $query->where('type', 'income');
        } elseif ($filter === 'expense') {
            $query->where('type', 'expense');
        } elseif ($filter === 'paid') {
            $query->where('status', 'paid');
        } elseif ($filter === 'pending') {
            $query->where('status', 'pending');
        }
        
        // Ensure sort field and direction are valid
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

        return view('transactions.index', compact('transactions', 'filter', 'isAdmin', 'sort', 'direction'));
    }

    public function create(Request $request)
    {
        // Determina o tipo de transação padrão com base no parâmetro da URL
        $type = $request->type ?? 'expense';
        
        // Carrega todas as categorias pelo tipo
        $categories = Category::where('type', $type)
            ->orderBy('name')
            ->get();
        
        // Verifica se o usuário é administrador - com verificação segura
        $isAdmin = auth()->check() && auth()->user()->is_admin;
        
        // Se for admin, mostra todas as contas ativas; senão, filtra por usuário
        if ($isAdmin) {
            $accounts = Account::where('active', true)
                ->orderBy('name')
                ->get();
        } else {
            $accounts = Account::where('active', true)
                ->where('user_id', auth()->id())
                ->orderBy('name')
                ->get();
        }
        
        return view('transactions.create', compact('categories', 'accounts', 'type', 'isAdmin'));
    }

    public function store(Request $request)
    {
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

            // Debug: Verificar o valor recebido
            \Log::info('Valor amount recebido: ' . $validated['amount']);

            // O valor já está corretamente formatado do frontend
            $amount = $validated['amount'];
            
            // Debug: Verificar o valor final
            \Log::info('Valor amount a ser salvo: ' . $amount);

            if (($validated['type'] ?? null) === 'income') {
                $validated['cliente'] = $request->input('cliente', null);
                $validated['fornecedor'] = null; // Garantir que seja nulo para despesas
            } elseif (($validated['type'] ?? null) === 'expense') {
                $validated['fornecedor'] = $request->input('fornecedor', null);
                $validated['cliente'] = null; // Garantir que seja nulo para receitas
            } else {
                $validated['cliente'] = null;
                $validated['fornecedor'] = null;
            }

            // Preparar dados da transação
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
                'user_id' => auth()->id(),
            ];

            // Adicionar campos de recorrência se aplicável
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
        // Verifica se o usuário é administrador - com verificação segura
        $isAdmin = auth()->check() && auth()->user()->is_admin;
        
        // Se não for admin e a transação não pertencer ao usuário, aborta
        if (!$isAdmin && $transaction->user_id !== auth()->id()) {
            abort(403);
        }

        // Carrega todas as categorias pelo tipo
        $categories = Category::where('type', $transaction->type)
            ->orderBy('name')
            ->get();
        
        // Se for admin, mostra todas as contas ativas; senão, filtra por usuário
        if ($isAdmin) {
            $accounts = Account::where('active', true)
                ->orderBy('name')
                ->get();
        } else {
            $accounts = Account::where('active', true)
                ->where('user_id', auth()->id())
                ->orderBy('name')
                ->get();
        }

        return view('transactions.edit', compact('transaction', 'categories', 'accounts', 'isAdmin'));
    }

    public function update(Request $request, Transaction $transaction)
    {
        // Verifica se o usuário é administrador - com verificação segura
        $isAdmin = auth()->check() && auth()->user()->is_admin;
        
        // Se não for admin e a transação não pertencer ao usuário, aborta
        if (!$isAdmin && $transaction->user_id !== auth()->id()) {
            abort(403);
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

        // Log do valor que está sendo enviado para debug
        \Log::info('Valor amount original na transação: ' . $transaction->amount);
        \Log::info('Valor amount recebido no update: ' . $validatedData['amount']);

        /**
         * ATENÇÃO: CONFIGURAÇÃO CRÍTICA - NÃO MODIFICAR
         * ===============================================
         * 1. VALORES DEVEM SER PRESERVADOS EM CENTAVOS
         * 2. EXEMPLO: R$ 400,00 = 40000 CENTAVOS
         * 3. NUNCA ALTERAR ESTA REGRA
         * 4. Ver FINANCIAL_RULES.md para mais detalhes
         * 
         * Alterações neste código podem causar inconsistências financeiras em todo o sistema.
         */
        
        // Preserva o valor exato da transação sem manipulá-lo
        // O valor já deve estar em centavos (R$ 400,00 = 40000)
        $amount = $validatedData['amount'];
        
        // Atualiza manualmente um por um para evitar problemas
        $transaction->type = $validatedData['type'];
        $transaction->status = $validatedData['status'];
        $transaction->date = $validatedData['date'];
        $transaction->description = $validatedData['description'];
        $transaction->amount = $amount; // Usa o valor exato em centavos
        $transaction->category_id = $validatedData['category_id'];
        $transaction->account_id = $validatedData['account_id'];
        $transaction->notes = $validatedData['notes'] ?? null;
        $transaction->cliente = $validatedData['cliente'];
        $transaction->fornecedor = $validatedData['fornecedor'];
        
        $transaction->save();
        
        \Log::info('Valor amount após update: ' . $transaction->amount);

        $redirectRoute = $validatedData['type'] === 'income' ? 'transactions.income' : 'transactions.expenses';
        return redirect()->route($redirectRoute)
            ->with('success', 'Transação atualizada com sucesso!');
    }

    public function destroy(Transaction $transaction)
    {
        // Verifica se o usuário é administrador - com verificação segura
        $isAdmin = auth()->check() && auth()->user()->is_admin;
        
        // Se não for admin e a transação não pertencer ao usuário, aborta
        if (!$isAdmin && $transaction->user_id !== auth()->id()) {
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
        // Verifica se o usuário é administrador - com verificação segura
        $isAdmin = auth()->check() && auth()->user()->is_admin;
        
        // Se não for admin e a transação não pertencer ao usuário, aborta
        if (!$isAdmin && $transaction->user_id !== auth()->id()) {
            abort(403, 'Você não tem permissão para alterar esta transação.');
        }
        
        $transaction->status = 'paid';
        $transaction->save();

        return redirect()->back()->with('success', 'Transação marcada como paga!');
    }

    public function showIncome()
    {
        return view('transactions.income');
    }

    public function showExpenses()
    {
        return view('transactions.expenses');
    }

    public function createNext(Transaction $transaction)
    {
        // Verificar se o usuário tem permissão para criar a próxima transação
        if ($transaction->user_id !== auth()->id()) {
            abort(403);
        }

        // Verificar se a transação tem recorrência
        if (!$transaction->hasRecurrence() || !$transaction->next_date) {
            return back()->with('error', 'Esta transação não possui configuração de recorrência válida.');
        }

        // Criar a próxima transação com base na atual
        $newTransaction = $transaction->replicate();
        
        // Definir a data da nova transação como a próxima data da transação atual
        $newTransaction->date = $transaction->next_date;
        
        // Calcular a próxima data para a transação atual (um mês após a próxima data atual)
        $nextDate = (clone $transaction->next_date)->addMonth();
        
        // Se for uma transação parcelada, incrementar o número da parcela
        if ($transaction->isInstallmentRecurrence()) {
            // Verificar se já atingiu o número máximo de parcelas
            if ($transaction->installment_number >= $transaction->total_installments) {
                return back()->with('error', 'Todas as parcelas desta transação já foram criadas.');
            }
            
            $newTransaction->installment_number = $transaction->installment_number + 1;
            
            // Se for a última parcela, remover a recorrência
            if ($newTransaction->installment_number >= $newTransaction->total_installments) {
                $newTransaction->recurrence_type = 'none';
                $newTransaction->next_date = null;
            } else {
                $newTransaction->next_date = $nextDate;
            }
        } else {
            // Para recorrência fixa, apenas atualizar a próxima data
            $newTransaction->next_date = $nextDate;
        }
        
        // Definir o status como pendente para a nova transação
        $newTransaction->status = 'pending';
        
        // Salvar a nova transação
        $newTransaction->save();
        
        // Atualizar a próxima data da transação original
        $transaction->next_date = null;
        
        // Se for uma transação parcelada e já atingiu o número máximo de parcelas, remover a recorrência
        if ($transaction->isInstallmentRecurrence() && $newTransaction->installment_number >= $transaction->total_installments) {
            $transaction->recurrence_type = 'none';
        }
        
        $transaction->save();
        
        return back()->with('success', 'Próxima transação recorrente criada com sucesso!');
    }
} 