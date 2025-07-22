<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TransactionController extends Controller
{
    /**
     * Listar transações do usuário
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'type' => 'nullable|in:income,expense',
            'status' => 'nullable|in:paid,pending',
            'account_id' => 'nullable|exists:accounts,id',
            'category_id' => 'nullable|exists:categories,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'search' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Parâmetros inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = Transaction::with(['category', 'account'])
            ->where('user_id', $user->id);

        // Aplicar filtros
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhere('cliente', 'like', "%{$search}%")
                  ->orWhere('fornecedor', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 15);
        $transactions = $query->orderBy('date', 'desc')
                             ->orderBy('created_at', 'desc')
                             ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'transactions' => $transactions->items(),
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'last_page' => $transactions->lastPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total(),
                    'from' => $transactions->firstItem(),
                    'to' => $transactions->lastItem(),
                ]
            ]
        ]);
    }

    /**
     * Criar nova transação
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:income,expense',
            'status' => 'required|in:paid,pending',
            'date' => 'required|date',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'category_id' => 'required|exists:categories,id',
            'account_id' => 'required|exists:accounts,id',
            'notes' => 'nullable|string|max:1000',
            'cliente' => 'nullable|string|max:255',
            'fornecedor' => 'nullable|string|max:255',
            'recurrence_type' => 'nullable|in:none,daily,weekly,monthly,yearly',
            'recurrence_period' => 'nullable|integer|min:1',
            'total_installments' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verificar se a categoria pertence ao usuário
        $category = Category::where('id', $request->category_id)
                           ->where('user_id', $user->id)
                           ->first();

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Categoria não encontrada'
            ], 404);
        }

        // Verificar se a conta pertence ao usuário
        $account = Account::where('id', $request->account_id)
                         ->where('user_id', $user->id)
                         ->first();

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Conta não encontrada'
            ], 404);
        }

        try {
            DB::beginTransaction();

            $transactionData = [
                'type' => $request->type,
                'status' => $request->status,
                'date' => $request->date,
                'description' => $request->description,
                'amount' => $request->amount,
                'category_id' => $request->category_id,
                'account_id' => $request->account_id,
                'user_id' => $user->id,
                'notes' => $request->notes,
                'recurrence_type' => $request->get('recurrence_type', 'none'),
            ];

            // Adicionar campos específicos por tipo
            if ($request->type === 'income' && $request->filled('cliente')) {
                $transactionData['cliente'] = $request->cliente;
            } elseif ($request->type === 'expense' && $request->filled('fornecedor')) {
                $transactionData['fornecedor'] = $request->fornecedor;
            }

            // Configurar recorrência se especificada
            if ($request->filled('recurrence_type') && $request->recurrence_type !== 'none') {
                $transactionData['recurrence_period'] = $request->get('recurrence_period', 1);
                
                if ($request->filled('total_installments')) {
                    $transactionData['total_installments'] = $request->total_installments;
                    $transactionData['installment_number'] = 1;
                }

                // Calcular próxima data
                $nextDate = Carbon::parse($request->date);
                switch ($request->recurrence_type) {
                    case 'daily':
                        $nextDate->addDays($transactionData['recurrence_period']);
                        break;
                    case 'weekly':
                        $nextDate->addWeeks($transactionData['recurrence_period']);
                        break;
                    case 'monthly':
                        $nextDate->addMonths($transactionData['recurrence_period']);
                        break;
                    case 'yearly':
                        $nextDate->addYears($transactionData['recurrence_period']);
                        break;
                }
                $transactionData['next_date'] = $nextDate;
            }

            $transaction = Transaction::create($transactionData);

            // Recalcular saldo da conta
            $account->recalculateBalance();
            $account->save();

            DB::commit();

            $transaction->load(['category', 'account']);

            return response()->json([
                'success' => true,
                'message' => 'Transação criada com sucesso',
                'data' => [
                    'transaction' => $transaction
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar transação: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exibir transação específica
     */
    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        $transaction = Transaction::with(['category', 'account'])
                                 ->where('id', $id)
                                 ->where('user_id', $user->id)
                                 ->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transação não encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'transaction' => $transaction
            ]
        ]);
    }

    /**
     * Atualizar transação
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        $transaction = Transaction::where('id', $id)
                                 ->where('user_id', $user->id)
                                 ->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transação não encontrada'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'sometimes|required|in:income,expense',
            'status' => 'sometimes|required|in:paid,pending',
            'date' => 'sometimes|required|date',
            'description' => 'sometimes|required|string|max:255',
            'amount' => 'sometimes|required|numeric|min:0.01',
            'category_id' => 'sometimes|required|exists:categories,id',
            'account_id' => 'sometimes|required|exists:accounts,id',
            'notes' => 'nullable|string|max:1000',
            'cliente' => 'nullable|string|max:255',
            'fornecedor' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $oldAccountId = $transaction->account_id;
            
            $transaction->update($request->only([
                'type', 'status', 'date', 'description', 'amount',
                'category_id', 'account_id', 'notes', 'cliente', 'fornecedor'
            ]));

            // Recalcular saldo das contas afetadas
            if ($oldAccountId !== $transaction->account_id) {
                // Conta antiga
                $oldAccount = Account::find($oldAccountId);
                if ($oldAccount) {
                    $oldAccount->recalculateBalance();
                    $oldAccount->save();
                }
            }

            // Conta atual
            $currentAccount = Account::find($transaction->account_id);
            if ($currentAccount) {
                $currentAccount->recalculateBalance();
                $currentAccount->save();
            }

            DB::commit();

            $transaction->load(['category', 'account']);

            return response()->json([
                'success' => true,
                'message' => 'Transação atualizada com sucesso',
                'data' => [
                    'transaction' => $transaction
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar transação: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Excluir transação
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        $transaction = Transaction::where('id', $id)
                                 ->where('user_id', $user->id)
                                 ->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transação não encontrada'
            ], 404);
        }

        try {
            DB::beginTransaction();

            $accountId = $transaction->account_id;
            $transaction->delete();

            // Recalcular saldo da conta
            $account = Account::find($accountId);
            if ($account) {
                $account->recalculateBalance();
                $account->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transação excluída com sucesso'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir transação: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resumo financeiro das transações
     */
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'account_id' => 'nullable|exists:accounts,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Parâmetros inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = Transaction::where('user_id', $user->id);

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        $summary = $query->selectRaw('
            type,
            status,
            SUM(amount) as total_amount,
            COUNT(*) as count
        ')
        ->groupBy('type', 'status')
        ->get();

        $result = [
            'income' => [
                'paid' => ['amount' => 0, 'count' => 0],
                'pending' => ['amount' => 0, 'count' => 0],
                'total' => ['amount' => 0, 'count' => 0],
            ],
            'expense' => [
                'paid' => ['amount' => 0, 'count' => 0],
                'pending' => ['amount' => 0, 'count' => 0],
                'total' => ['amount' => 0, 'count' => 0],
            ],
            'balance' => [
                'paid' => 0,
                'pending' => 0,
                'total' => 0,
            ]
        ];

        foreach ($summary as $item) {
            $result[$item->type][$item->status] = [
                'amount' => $item->total_amount / 100, // Converter de centavos para reais
                'count' => $item->count
            ];
        }

        // Calcular totais
        foreach (['income', 'expense'] as $type) {
            $result[$type]['total']['amount'] =
                $result[$type]['paid']['amount'] + $result[$type]['pending']['amount'];
            $result[$type]['total']['count'] =
                $result[$type]['paid']['count'] + $result[$type]['pending']['count'];
        }

        // Calcular saldos
        $result['balance']['paid'] =
            $result['income']['paid']['amount'] - $result['expense']['paid']['amount'];
        $result['balance']['pending'] =
            $result['income']['pending']['amount'] - $result['expense']['pending']['amount'];
        $result['balance']['total'] =
            $result['income']['total']['amount'] - $result['expense']['total']['amount'];

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $result,
                'period' => [
                    'from' => $request->date_from,
                    'to' => $request->date_to,
                ]
            ]
        ]);
    }
}
