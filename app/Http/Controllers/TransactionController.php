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
        ]);

        // Debug: Verificar o valor recebido
        \Log::info('Valor amount recebido: ' . $validated['amount']);

        // Processamento do valor monetário - removendo formatação
        $amountStr = str_replace(['R$', '.'], '', $validated['amount']);
        $amountStr = str_replace(',', '.', $amountStr);
        $amount = (float) $amountStr;
        
        // Convertendo para centavos
        $amount = round($amount * 100);

        // Debug: Verificar o valor final
        \Log::info('Valor amount convertido para centavos: ' . $amount);

        $transaction = Transaction::create([
            'type' => $validated['type'],
            'status' => $validated['status'],
            'date' => $validated['date'],
            'description' => $validated['description'],
            'amount' => $amount,
            'category_id' => $validated['category_id'],
            'account_id' => $validated['account_id'],
            'notes' => $validated['notes'] ?? null,
            'user_id' => auth()->id(),
        ]);

        $redirectRoute = $validated['type'] === 'income' ? 'transactions.income' : 'transactions.expenses';
        return redirect()->route($redirectRoute)
            ->with('success', 'Transação criada com sucesso!');
    }

    public function edit(Transaction $transaction)
    {
        // Verifica se o usuário tem permissão para editar esta transação
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

        // Log do request para debug
        \Log::info('Request completo de update:', $request->all());

        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required',
            'date' => 'required|date',
            'type' => 'required|in:income,expense',
            'status' => 'required|in:pending,paid',
            'category_id' => 'required|exists:categories,id',
            'account_id' => 'required|exists:accounts,id',
            'notes' => 'nullable|string',
        ]);

        // Debug: Verificar o valor recebido
        \Log::info('Valor amount recebido na atualização: ' . $validated['amount']);

        // Processamento do valor monetário - removendo formatação
        $amountStr = str_replace(['R$', '.'], '', $validated['amount']);
        $amountStr = str_replace(',', '.', $amountStr);
        $amount = (float) $amountStr;
        
        // Convertendo para centavos
        $amount = round($amount * 100);

        // Debug: Verificar o valor final
        \Log::info('Valor amount convertido para centavos na atualização: ' . $amount);
        
        // Atualizar dados da transação
        $transaction->update([
            'type' => $validated['type'],
            'status' => $validated['status'],
            'date' => $validated['date'],
            'description' => $validated['description'],
            'amount' => $amount,
            'category_id' => $validated['category_id'],
            'account_id' => $validated['account_id'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $redirectRoute = $validated['type'] === 'income' ? 'transactions.income' : 'transactions.expenses';
        return redirect()->route($redirectRoute)
            ->with('success', 'Transação atualizada com sucesso!');
    }

    public function destroy(Transaction $transaction)
    {
        // Verifica se o usuário tem permissão para excluir esta transação
        if ($transaction->user_id !== auth()->id()) {
            abort(403);
        }

        try {
            $transaction->delete();
            return redirect()
                ->back()
                ->with('success', 'Transação excluída com sucesso!');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erro ao excluir transação: ' . $e->getMessage());
        }
    }

    public function markAsPaid(Transaction $transaction)
    {
        if ($transaction->user_id !== auth()->id()) {
            abort(403);
        }

        $transaction->update(['status' => 'paid']);

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
} 