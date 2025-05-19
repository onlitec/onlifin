<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $query = Account::orderBy('name');

        if (!$user->hasPermission('view_all_accounts')) {
            if ($user->hasPermission('view_own_accounts')) {
                $query->where('user_id', $user->id);
            } else {
                abort(403, 'Você não tem permissão para visualizar contas.');
            }
        }
        
        $accounts = $query->get();
        $isAdminView = $user->hasRole('Administrador');
        
        return view('accounts.index', compact('accounts', 'isAdminView'));
    }

    public function create()
    {
        $user = Auth::user();
        if (!$user->hasPermission('create_accounts')) {
            abort(403, 'Você não tem permissão para criar contas.');
        }
        
        $isAdminView = $user->hasRole('Administrador');
        $usersForSelect = null;
        if ($isAdminView) {
            $usersForSelect = User::orderBy('name')->get();
        }
        
        return view('accounts.create', compact('isAdminView', 'usersForSelect'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasPermission('create_accounts')) {
            abort(403, 'Você não tem permissão para criar contas.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'initial_balance' => 'required|numeric',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'user_id' => 'sometimes|exists:users,id',
        ]);

        if ($user->hasRole('Administrador') && isset($validated['user_id'])) {
            // Keep $validated['user_id']
        } else {
            $validated['user_id'] = $user->id;
        }
        
        $validated['current_balance'] = $validated['initial_balance'];

        Account::create($validated);

        return redirect()->route('accounts.index')
            ->with('success', 'Conta criada com sucesso!');
    }

    public function edit(Account $account)
    {
        $user = Auth::user();
        $canEditAll = $user->hasPermission('edit_all_accounts');
        $canEditOwn = $user->hasPermission('edit_own_accounts');

        if (!($canEditAll || ($canEditOwn && $account->user_id === $user->id))) {
            abort(403, 'Você não tem permissão para editar esta conta.');
        }
        
        $isAdminView = $user->hasRole('Administrador');
        $usersForSelect = null;
        if ($isAdminView) {
            $usersForSelect = User::orderBy('name')->get();
        }
        
        return view('accounts.edit', compact('account', 'isAdminView', 'usersForSelect'));
    }

    public function update(Request $request, Account $account)
    {
        $user = Auth::user();
        $canEditAll = $user->hasPermission('edit_all_accounts');
        $canEditOwn = $user->hasPermission('edit_own_accounts');

        if (!($canEditAll || ($canEditOwn && $account->user_id === $user->id))) {
            abort(403, 'Você não tem permissão para atualizar esta conta.');
        }
        
        $validationRules = [
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'initial_balance' => 'required|numeric',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
        ];
        
        if ($user->hasRole('Administrador')) {
            $validationRules['user_id'] = 'sometimes|exists:users,id';
        }
        
        $validated = $request->validate($validationRules);
        
        if (!$user->hasRole('Administrador') && isset($validated['user_id'])) {
            unset($validated['user_id']);
        }

        $account->update($validated);

        return redirect()->route('accounts.index')
            ->with('success', 'Conta atualizada com sucesso!');
    }

    public function destroy(Account $account)
    {
        $user = Auth::user();
        $canDeleteAll = $user->hasPermission('delete_all_accounts');
        $canDeleteOwn = $user->hasPermission('delete_own_accounts');

        if (!($canDeleteAll || ($canDeleteOwn && $account->user_id === $user->id))) {
            abort(403, 'Você não tem permissão para excluir esta conta.');
        }
        
        $hasTransactions = $account->transactions()->count() > 0;
        $userAccountsCount = Account::where('user_id', $account->user_id)->count();
        $isLastAccount = $userAccountsCount <= 1;
        $isMainAccount = $account->name === 'Conta Principal';
        
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
            Log::error('Erro ao excluir conta: ' . $e->getMessage());
            return redirect()->route('accounts.index')
                ->with('error', 'Não foi possível excluir a conta. Verifique se há transações ou outros registros associados.');
        }
    }
} 