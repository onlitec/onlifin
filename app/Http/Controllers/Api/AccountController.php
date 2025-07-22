<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    /**
     * Listar contas do usuário
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'active' => 'nullable|boolean',
            'type' => 'nullable|in:checking,savings,investment,credit_card,cash,other',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Parâmetros inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = Account::where('user_id', $user->id);

        if ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $accounts = $query->orderBy('name')->get();

        // Adicionar informações extras para cada conta
        $accounts->each(function ($account) {
            $account->current_balance = $account->recalculateBalance();
            $account->type_label = $account->type_label;
            $account->transactions_count = $account->transactions()->count();
        });

        return response()->json([
            'success' => true,
            'data' => [
                'accounts' => $accounts
            ]
        ]);
    }

    /**
     * Criar nova conta
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|in:checking,savings,investment,credit_card,cash,other',
            'initial_balance' => 'required|numeric',
            'description' => 'nullable|string|max:500',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verificar se já existe uma conta com o mesmo nome para o usuário
        $existingAccount = Account::where('user_id', $user->id)
                                 ->where('name', $request->name)
                                 ->first();

        if ($existingAccount) {
            return response()->json([
                'success' => false,
                'message' => 'Já existe uma conta com este nome'
            ], 422);
        }

        try {
            $accountData = [
                'name' => $request->name,
                'type' => $request->type,
                'initial_balance' => $request->initial_balance,
                'current_balance' => $request->initial_balance,
                'description' => $request->description,
                'color' => $request->get('color', '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT)),
                'active' => $request->get('active', true),
                'user_id' => $user->id,
            ];

            $account = Account::create($accountData);

            return response()->json([
                'success' => true,
                'message' => 'Conta criada com sucesso',
                'data' => [
                    'account' => $account
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar conta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exibir conta específica
     */
    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        $account = Account::where('id', $id)
                         ->where('user_id', $user->id)
                         ->first();

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Conta não encontrada'
            ], 404);
        }

        // Adicionar informações extras
        $account->current_balance = $account->recalculateBalance();
        $account->type_label = $account->type_label;
        $account->transactions_count = $account->transactions()->count();

        // Últimas transações
        $recentTransactions = $account->transactions()
                                    ->with('category')
                                    ->orderBy('date', 'desc')
                                    ->limit(10)
                                    ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'account' => $account,
                'recent_transactions' => $recentTransactions
            ]
        ]);
    }

    /**
     * Atualizar conta
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        $account = Account::where('id', $id)
                         ->where('user_id', $user->id)
                         ->first();

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Conta não encontrada'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|in:checking,savings,investment,credit_card,cash,other',
            'initial_balance' => 'sometimes|required|numeric',
            'description' => 'nullable|string|max:500',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verificar se o nome não conflita com outra conta
        if ($request->filled('name') && $request->name !== $account->name) {
            $existingAccount = Account::where('user_id', $user->id)
                                     ->where('name', $request->name)
                                     ->where('id', '!=', $id)
                                     ->first();

            if ($existingAccount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Já existe uma conta com este nome'
                ], 422);
            }
        }

        try {
            DB::beginTransaction();

            $oldInitialBalance = $account->initial_balance;
            
            $account->update($request->only([
                'name', 'type', 'initial_balance', 'description', 'color', 'active'
            ]));

            // Se o saldo inicial mudou, recalcular o saldo atual
            if ($request->filled('initial_balance') && $request->initial_balance != $oldInitialBalance) {
                $account->current_balance = $account->recalculateBalance();
                $account->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Conta atualizada com sucesso',
                'data' => [
                    'account' => $account
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar conta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Excluir conta
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        $account = Account::where('id', $id)
                         ->where('user_id', $user->id)
                         ->first();

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Conta não encontrada'
            ], 404);
        }

        // Verificar se a conta tem transações
        $transactionsCount = $account->transactions()->count();

        if ($transactionsCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Não é possível excluir a conta pois ela possui {$transactionsCount} transação(ões) associada(s)"
            ], 422);
        }

        try {
            $account->delete();

            return response()->json([
                'success' => true,
                'message' => 'Conta excluída com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir conta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resumo de todas as contas
     */
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();

        $accounts = Account::where('user_id', $user->id)
                          ->where('active', true)
                          ->get();

        $summary = [
            'total_accounts' => $accounts->count(),
            'total_balance' => 0,
            'by_type' => [],
        ];

        foreach ($accounts as $account) {
            $balance = $account->recalculateBalance();
            $summary['total_balance'] += $balance;

            if (!isset($summary['by_type'][$account->type])) {
                $summary['by_type'][$account->type] = [
                    'count' => 0,
                    'balance' => 0,
                    'label' => $account->type_label,
                ];
            }

            $summary['by_type'][$account->type]['count']++;
            $summary['by_type'][$account->type]['balance'] += $balance;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $summary
            ]
        ]);
    }
}
