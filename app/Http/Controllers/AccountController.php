<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index()
    {
        $accounts = Account::orderBy('name')->paginate(10);
        return view('accounts.index', compact('accounts'));
    }

    public function create()
    {
        return view('accounts.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'initial_balance' => 'required|numeric',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
        ]);

        $validated['user_id'] = auth()->id();
        
        $account = Account::create($validated);

        return redirect()->route('accounts.index')
            ->with('success', 'Conta criada com sucesso!');
    }

    public function edit(Account $account)
    {
        return view('accounts.edit', compact('account'));
    }

    public function update(Request $request, Account $account)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'initial_balance' => 'required|numeric',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
        ]);

        $account->update($validated);

        return redirect()->route('accounts.index')
            ->with('success', 'Conta atualizada com sucesso!');
    }

    public function destroy(Account $account)
    {
        try {
            $account->delete();
            return redirect()->route('accounts.index')
                ->with('success', 'Conta excluída com sucesso!');
        } catch (\Exception $e) {
            return redirect()->route('accounts.index')
                ->with('error', 'Não foi possível excluir esta conta. Ela pode estar em uso em transações.');
        }
    }
} 