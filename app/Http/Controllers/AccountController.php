<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AccountController extends Controller
{
    public function index()
    {
        // Verifica se o usuário é administrador - com verificação segura
        $isAdmin = auth()->check() && auth()->user()->is_admin;
        
        // Query base
        $query = Account::orderBy('name');
        
        // Se não for admin, filtra por usuário
        if (!$isAdmin) {
            $query->where('user_id', auth()->id());
        }
        
        $accounts = $query->get();
        
        return view('accounts.index', compact('accounts', 'isAdmin'));
    }

    public function create()
    {
        // Verifica se o usuário é administrador - com verificação segura
        $isAdmin = auth()->check() && auth()->user()->is_admin;
        
        // Se for admin, obtém lista de usuários para associar conta
        if ($isAdmin) {
            $users = \App\Models\User::orderBy('name')->get();
            return view('accounts.create', compact('isAdmin', 'users'));
        }
        
        return view('accounts.create', compact('isAdmin'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'initial_balance' => 'required|numeric',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'user_id' => 'sometimes|exists:users,id',
        ]);

        // Verifica se o usuário é administrador - com verificação segura
        $isAdmin = auth()->check() && auth()->user()->is_admin;
        
        // Se for admin e tiver informado user_id, usa o valor informado
        if ($isAdmin && isset($validated['user_id'])) {
            // Mantém o user_id enviado
        } else {
            // Caso contrário, usa o ID do usuário autenticado
            $validated['user_id'] = auth()->id();
        }
        
        $validated['current_balance'] = $validated['initial_balance'];

        $account = Account::create($validated);

        return redirect()->route('accounts.index')
            ->with('success', 'Conta criada com sucesso!');
    }

    public function edit(Account $account)
    {
        // Verifica se o usuário é administrador - com verificação segura
        $isAdmin = auth()->check() && auth()->user()->is_admin;
        
        // Se não for admin e a conta não pertencer ao usuário, aborta
        if (!$isAdmin && $account->user_id !== auth()->id()) {
            abort(403);
        }
        
        // Se for admin, obtém lista de usuários para associar conta
        if ($isAdmin) {
            $users = \App\Models\User::orderBy('name')->get();
            return view('accounts.edit', compact('account', 'isAdmin', 'users'));
        }
        
        return view('accounts.edit', compact('account', 'isAdmin'));
    }

    public function update(Request $request, Account $account)
    {
        // Verifica se o usuário é administrador - com verificação segura
        $isAdmin = auth()->check() && auth()->user()->is_admin;
        
        // Se não for admin e a conta não pertencer ao usuário, aborta
        if (!$isAdmin && $account->user_id !== auth()->id()) {
            abort(403);
        }
        
        $validationRules = [
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'initial_balance' => 'required|numeric',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
        ];
        
        // Adiciona a validação de user_id apenas para admins
        if ($isAdmin) {
            $validationRules['user_id'] = 'sometimes|exists:users,id';
        }
        
        $validated = $request->validate($validationRules);
        
        $account->update($validated);

        return redirect()->route('accounts.index')
            ->with('success', 'Conta atualizada com sucesso!');
    }

    public function destroy(Account $account)
    {
        // Verifica se o usuário é administrador - com verificação segura
        $isAdmin = auth()->check() && auth()->user()->is_admin;
        
        // Se não for admin e a conta não pertencer ao usuário, aborta
        if (!$isAdmin && $account->user_id !== auth()->id()) {
            abort(403);
        }
        
        // Verificar se a conta tem transações
        $hasTransactions = $account->transactions()->count() > 0;
        
        // Verificar se é a última conta do usuário
        $userAccountsCount = Account::where('user_id', $account->user_id)->count();
        $isLastAccount = $userAccountsCount <= 1;
        
        // Verificar se é a "Conta Principal" criada automaticamente
        $isMainAccount = $account->name === 'Conta Principal';
        
        // Não permitir excluir a última conta ou a Conta Principal
        if ($isLastAccount) {
            return redirect()->route('accounts.index')
                ->with('error', 'Não é possível excluir a última conta do usuário.');
        }
        
        if ($isMainAccount && $hasTransactions) {
            return redirect()->route('accounts.index')
                ->with('error', 'Não é possível excluir a Conta Principal enquanto houver transações associadas a ela.');
        }
        
        try {
            $account->delete();
            return redirect()->route('accounts.index')
                ->with('success', 'Conta excluída com sucesso.');
        } catch (\Exception $e) {
            \Log::error('Erro ao excluir conta: ' . $e->getMessage());
            return redirect()->route('accounts.index')
                ->with('error', 'Não foi possível excluir a conta. Verifique se há transações ou outros registros associados.');
        }
    }
} 