<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Category;
use App\Models\Account;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index()
    {
        $transactions = Transaction::with(['category', 'account'])
            ->orderBy('date', 'desc')
            ->paginate(10);

        return view('transactions.index', compact('transactions'));
    }

    public function create(Request $request)
    {
        // Determina o tipo de transação padrão com base no parâmetro da URL
        $type = $request->type ?? 'expense';
        
        // Filtra as categorias pelo tipo (receita ou despesa)
        $categories = Category::where('type', $type)->orderBy('name')->get();
        $accounts = Account::where('active', true)->orderBy('name')->get();
        
        return view('transactions.create', compact('categories', 'accounts', 'type'));
    }

    public function store(Request $request)
    {
        // Log do request para debug
        \Log::info('Request completo:', $request->all());

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

        if (( $validated['type'] ?? null) === 'income') {
            $validated['cliente'] = $request->input('cliente', null);
            $validated['fornecedor'] = null; // Garantir que seja nulo para despesas
        } elseif (( $validated['type'] ?? null) === 'expense') {
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
    }

    public function edit(Transaction $transaction)
    {
        if ($transaction->user_id !== auth()->id()) {
            abort(403);
        }

        $categories = Category::where('type', $transaction->type)->get();
        $accounts = Account::where('active', true)->get();

        return view('transactions.edit', compact('transaction', 'categories', 'accounts'));
    }

    public function update(Request $request, Transaction $transaction)
    {
        if ($transaction->user_id !== auth()->id()) {
            abort(403);
        }

        $validatedData = $request->validate([
            'type' => 'required|in:income,expense',
            'status' => 'required|in:pending,paid',
            'date' => 'required|date',
            'description' => 'required|string|max:255',
            'amount' => 'required|string|min:0.01',
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

        // O modelo já fará a conversão para centavos, então apenas passamos o valor como está
        // Isso evita a multiplicação dupla por 100

        // Atualizar dados da transação
        $transaction->update($validatedData);

        $redirectRoute = $validatedData['type'] === 'income' ? 'transactions.income' : 'transactions.expenses';
        return redirect()->route($redirectRoute)
            ->with('success', 'Transação atualizada com sucesso!');
    }

    public function destroy(Transaction $transaction)
    {
        if ($transaction->user_id !== auth()->id()) {
            abort(403);
        }

        $transaction->delete();
        return redirect('/transactions')
            ->with('success', 'Transação excluída com sucesso!');
    }

    public function markAsPaid(Transaction $transaction)
    {
        if ($transaction->user_id !== auth()->id()) {
            abort(403);
        }

        // Atualiza apenas o status, sem tocar no valor
        $transaction->status = 'paid';
        $transaction->save();

        $message = $transaction->type === 'income' 
            ? 'Receita marcada como recebida!' 
            : 'Despesa marcada como paga!';

        return back()->with('success', $message);
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