<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    /**
     * Listar categorias do usuário
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'type' => 'nullable|in:income,expense',
            'with_stats' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Parâmetros inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = Category::where('user_id', $user->id);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $categories = $query->orderBy('name')->get();

        // Adicionar estatísticas se solicitado
        if ($request->boolean('with_stats')) {
            $categories->each(function ($category) {
                $transactions = $category->transactions();
                $category->transactions_count = $transactions->count();
                $category->total_amount = $transactions->sum('amount') / 100; // Converter de centavos
                $category->last_used = $transactions->max('date');
            });
        }

        return response()->json([
            'success' => true,
            'data' => [
                'categories' => $categories
            ]
        ]);
    }

    /**
     * Criar nova categoria
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|in:income,expense',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verificar se já existe uma categoria com o mesmo nome e tipo para o usuário
        $existingCategory = Category::where('user_id', $user->id)
                                   ->where('name', $request->name)
                                   ->where('type', $request->type)
                                   ->first();

        if ($existingCategory) {
            return response()->json([
                'success' => false,
                'message' => 'Já existe uma categoria com este nome para este tipo'
            ], 422);
        }

        try {
            $categoryData = [
                'name' => $request->name,
                'type' => $request->type,
                'color' => $request->get('color', '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT)),
                'icon' => $request->get('icon', 'fa-solid fa-tag'),
                'description' => $request->description,
                'user_id' => $user->id,
            ];

            $category = Category::create($categoryData);

            return response()->json([
                'success' => true,
                'message' => 'Categoria criada com sucesso',
                'data' => [
                    'category' => $category
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar categoria: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exibir categoria específica
     */
    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        $category = Category::where('id', $id)
                           ->where('user_id', $user->id)
                           ->first();

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Categoria não encontrada'
            ], 404);
        }

        // Adicionar estatísticas
        $transactions = $category->transactions();
        $category->transactions_count = $transactions->count();
        $category->total_amount = $transactions->sum('amount') / 100; // Converter de centavos
        $category->last_used = $transactions->max('date');

        // Últimas transações desta categoria
        $recentTransactions = $category->transactions()
                                      ->with('account')
                                      ->orderBy('date', 'desc')
                                      ->limit(10)
                                      ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'category' => $category,
                'recent_transactions' => $recentTransactions
            ]
        ]);
    }

    /**
     * Atualizar categoria
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        $category = Category::where('id', $id)
                           ->where('user_id', $user->id)
                           ->first();

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Categoria não encontrada'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|in:income,expense',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verificar se o nome não conflita com outra categoria
        if ($request->filled('name') || $request->filled('type')) {
            $name = $request->get('name', $category->name);
            $type = $request->get('type', $category->type);

            if ($name !== $category->name || $type !== $category->type) {
                $existingCategory = Category::where('user_id', $user->id)
                                           ->where('name', $name)
                                           ->where('type', $type)
                                           ->where('id', '!=', $id)
                                           ->first();

                if ($existingCategory) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Já existe uma categoria com este nome para este tipo'
                    ], 422);
                }
            }
        }

        try {
            $category->update($request->only([
                'name', 'type', 'color', 'icon', 'description'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Categoria atualizada com sucesso',
                'data' => [
                    'category' => $category
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar categoria: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Excluir categoria
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        $category = Category::where('id', $id)
                           ->where('user_id', $user->id)
                           ->first();

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Categoria não encontrada'
            ], 404);
        }

        // Verificar se a categoria tem transações
        $transactionsCount = $category->transactions()->count();

        if ($transactionsCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Não é possível excluir a categoria pois ela possui {$transactionsCount} transação(ões) associada(s)"
            ], 422);
        }

        try {
            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Categoria excluída com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir categoria: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Estatísticas das categorias
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'type' => 'nullable|in:income,expense',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Parâmetros inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = Category::where('user_id', $user->id)
                        ->withCount(['transactions' => function ($q) use ($request) {
                            if ($request->filled('date_from')) {
                                $q->whereDate('date', '>=', $request->date_from);
                            }
                            if ($request->filled('date_to')) {
                                $q->whereDate('date', '<=', $request->date_to);
                            }
                        }])
                        ->with(['transactions' => function ($q) use ($request) {
                            $q->selectRaw('category_id, SUM(amount) as total_amount')
                              ->groupBy('category_id');
                            
                            if ($request->filled('date_from')) {
                                $q->whereDate('date', '>=', $request->date_from);
                            }
                            if ($request->filled('date_to')) {
                                $q->whereDate('date', '<=', $request->date_to);
                            }
                        }]);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $limit = $request->get('limit', 10);
        $categories = $query->orderBy('name')->get();

        $stats = $categories->map(function ($category) {
            $totalAmount = $category->transactions->sum('total_amount') / 100; // Converter de centavos
            
            return [
                'id' => $category->id,
                'name' => $category->name,
                'type' => $category->type,
                'color' => $category->color,
                'icon' => $category->icon,
                'transactions_count' => $category->transactions_count,
                'total_amount' => $totalAmount,
            ];
        })->sortByDesc('total_amount')->take($limit)->values();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'period' => [
                    'from' => $request->date_from,
                    'to' => $request->date_to,
                ]
            ]
        ]);
    }
}
