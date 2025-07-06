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
                // Buscar contas através dos grupos do usuário
                $groupIds = $user->groups()->pluck('groups.id');
                $query->whereIn('group_id', $groupIds);
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
            'group_id' => 'sometimes|exists:groups,id',
        ]);

        if ($user->hasRole('Administrador') && isset($validated['group_id'])) {
            // Administradores podem especificar o grupo
        } else {
            // Usuários normais usam o primeiro grupo ao qual pertencem
            $firstGroup = $user->groups()->first();
            if (!$firstGroup) {
                abort(403, 'Usuário não pertence a nenhum grupo.');
            }
            $validated['group_id'] = $firstGroup->id;
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

        if (!$canEditAll && $canEditOwn) {
            // Verificar se o usuário pertence ao grupo da conta
            $userGroupIds = $user->groups()->pluck('groups.id');
            if (!$userGroupIds->contains($account->group_id)) {
                abort(403, 'Você não tem permissão para editar esta conta.');
            }
        } elseif (!$canEditAll) {
            abort(403, 'Você não tem permissão para editar esta conta.');
        }
        
        $isAdminView = $user->hasRole('Administrador');
        $groupsForSelect = null;
        if ($isAdminView) {
            $groupsForSelect = \App\Models\Group::orderBy('name')->get();
        }
        
        return view('accounts.edit', compact('account', 'isAdminView', 'groupsForSelect'));
    }

    public function update(Request $request, Account $account)
    {
        $user = Auth::user();
        $canEditAll = $user->hasPermission('edit_all_accounts');
        $canEditOwn = $user->hasPermission('edit_own_accounts');

        if (!$canEditAll && $canEditOwn) {
            // Verificar se o usuário pertence ao grupo da conta
            $userGroupIds = $user->groups()->pluck('groups.id');
            if (!$userGroupIds->contains($account->group_id)) {
                abort(403, 'Você não tem permissão para atualizar esta conta.');
            }
        } elseif (!$canEditAll) {
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
            $validationRules['group_id'] = 'sometimes|exists:groups,id';
        }
        
        $validated = $request->validate($validationRules);
        
        if (!$user->hasRole('Administrador') && isset($validated['group_id'])) {
            unset($validated['group_id']);
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

        if (!$canDeleteAll && $canDeleteOwn) {
            // Verificar se o usuário pertence ao grupo da conta
            $userGroupIds = $user->groups()->pluck('groups.id');
            if (!$userGroupIds->contains($account->group_id)) {
                abort(403, 'Você não tem permissão para excluir esta conta.');
            }
        } elseif (!$canDeleteAll) {
            abort(403, 'Você não tem permissão para excluir esta conta.');
        }
        
        $hasTransactions = $account->transactions()->count() > 0;
        $groupAccountsCount = Account::where('group_id', $account->group_id)->count();
        $isLastAccount = $groupAccountsCount <= 1;
        $isMainAccount = $account->name === 'Conta Principal';
        
        if ($isLastAccount) {
            return redirect()->route('accounts.index')
                ->with('error', 'Não é possível excluir a última conta do grupo.');
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