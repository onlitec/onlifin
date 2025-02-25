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

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        $accounts = Account::where('active', true)->orderBy('name')->get();
        
        return view('transactions.create', compact('categories', 'accounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:income,expense',
            'status' => 'required|in:pending,paid',
            'date' => 'required|date',
            'description' => 'required|string|max:255',
            'amount' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'account_id' => 'required|exists:accounts,id',
            'notes' => 'nullable|string',
        ]);

        // Remove formatação e converte para centavos
        $amount = str_replace(['R$', '.', ','], ['', '', '.'], $validated['amount']);
        $amount = round((float) $amount * 100);

        $transaction = Transaction::create([
            'type' => $validated['type'],
            'status' => $validated['status'],
            'date' => $validated['date'],
            'description' => $validated['description'],
            'amount' => $amount,
            'category_id' => $validated['category_id'],
            'account_id' => $validated['account_id'],
            'notes' => $validated['notes'],
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('transactions')
            ->with('success', 'Transação criada com sucesso!');
    }

    public function edit(Transaction $transaction)
    {
        $categories = Category::where('type', $transaction->type)->get();
        $accounts = Account::where('active', true)->get();

        return view('transactions.edit', compact('transaction', 'categories', 'accounts'));
    }

    public function update(Request $request, Transaction $transaction)
    {
        $validatedData = $request->validate([
            'type' => 'required|in:income,expense',
            'status' => 'required|in:pending,paid',
            'date' => 'required|date',
            'description' => 'required|string|max:255',
            'amount' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'account_id' => 'required|exists:accounts,id',
            'notes' => 'nullable|string',
        ]);

        // Remove formatação e converte para centavos
        $amount = str_replace(['R$', '.', ','], ['', '', '.'], $validatedData['amount']);
        $amount = round((float) $amount * 100);
        $validatedData['amount'] = $amount;

        $transaction->update($validatedData);

        return redirect()->route('transactions')
            ->with('success', 'Transação atualizada com sucesso!');
    }

    public function destroy(Transaction $transaction)
    {
        try {
            $transaction->delete();
            return redirect()
                ->route('transactions')
                ->with('success', 'Transação excluída com sucesso!');
        } catch (\Exception $e) {
            return redirect()
                ->route('transactions')
                ->with('error', 'Erro ao excluir transação: ' . $e->getMessage());
        }
    }

    public function markAsPaid(Transaction $transaction)
    {
        try {
            $transaction->update(['status' => 'paid']);

            $message = $transaction->type === 'income' 
                ? 'Receita marcada como recebida!' 
                : 'Despesa marcada como paga!';

            return back()->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao atualizar o status da transação.');
        }
    }
} 