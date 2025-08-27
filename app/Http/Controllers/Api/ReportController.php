<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Account;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Dashboard - Resumo geral
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        // Período padrão: mês atual
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        // Resumo financeiro do mês
        $monthlyTransactions = Transaction::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('
                type,
                status,
                SUM(amount) as total_amount,
                COUNT(*) as count
            ')
            ->groupBy('type', 'status')
            ->get();

        $monthlyStats = [
            'income' => ['paid' => 0, 'pending' => 0, 'total' => 0],
            'expense' => ['paid' => 0, 'pending' => 0, 'total' => 0],
            'balance' => 0,
        ];

        foreach ($monthlyTransactions as $transaction) {
            $amount = $transaction->total_amount / 100; // Converter de centavos
            $monthlyStats[$transaction->type][$transaction->status] = $amount;
            $monthlyStats[$transaction->type]['total'] += $amount;
        }

        $monthlyStats['balance'] = $monthlyStats['income']['total'] - $monthlyStats['expense']['total'];

        // Saldo total das contas
        $accounts = Account::where('user_id', $user->id)
                          ->where('active', true)
                          ->get();

        $totalBalance = 0;
        foreach ($accounts as $account) {
            $totalBalance += $account->recalculateBalance();
        }

        // Transações recentes
        $recentTransactions = Transaction::with(['category', 'account'])
            ->where('user_id', $user->id)
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Categorias mais utilizadas no mês
        $topCategories = Category::where('user_id', $user->id)
            ->withCount(['transactions' => function ($q) use ($startDate, $endDate) {
                $q->whereBetween('date', [$startDate, $endDate]);
            }])
            ->having('transactions_count', '>', 0)
            ->orderBy('transactions_count', 'desc')
            ->limit(5)
            ->get();

        // Contas pendentes
        $pendingTransactions = Transaction::with(['category', 'account'])
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->orderBy('date', 'asc')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'monthly_stats' => $monthlyStats,
                'total_balance' => $totalBalance,
                'accounts_count' => $accounts->count(),
                'recent_transactions' => $recentTransactions,
                'top_categories' => $topCategories,
                'pending_transactions' => $pendingTransactions,
                'period' => [
                    'from' => $startDate->format('Y-m-d'),
                    'to' => $endDate->format('Y-m-d'),
                ]
            ]
        ]);
    }

    /**
     * Relatório de fluxo de caixa
     */
    public function cashFlow(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'group_by' => 'nullable|in:day,week,month',
            'account_id' => 'nullable|exists:accounts,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Parâmetros inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $groupBy = $request->get('group_by', 'month');
        $dateFormat = match($groupBy) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m'
        };

        $query = Transaction::where('user_id', $user->id)
            ->whereBetween('date', [$request->date_from, $request->date_to]);

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        // Usar strftime para SQLite ou DATE_FORMAT para MySQL
        $dbConnection = config('database.default');
        $dateFormatFunction = $dbConnection === 'sqlite'
            ? "strftime('{$dateFormat}', date)"
            : "DATE_FORMAT(date, '{$dateFormat}')";

        $cashFlow = $query->selectRaw("
            {$dateFormatFunction} as period,
            type,
            SUM(amount) as total_amount
        ")
        ->groupBy('period', 'type')
        ->orderBy('period')
        ->get();

        // Organizar dados por período
        $periods = [];
        foreach ($cashFlow as $item) {
            if (!isset($periods[$item->period])) {
                $periods[$item->period] = [
                    'period' => $item->period,
                    'income' => 0,
                    'expense' => 0,
                    'balance' => 0,
                ];
            }
            $periods[$item->period][$item->type] = $item->total_amount / 100;
        }

        // Calcular saldos
        foreach ($periods as &$period) {
            $period['balance'] = $period['income'] - $period['expense'];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'cash_flow' => array_values($periods),
                'group_by' => $groupBy,
                'period' => [
                    'from' => $request->date_from,
                    'to' => $request->date_to,
                ]
            ]
        ]);
    }

    /**
     * Relatório por categorias
     */
    public function byCategory(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'type' => 'nullable|in:income,expense',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Parâmetros inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = DB::table('transactions')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->where('transactions.user_id', $user->id)
            ->whereBetween('transactions.date', [$request->date_from, $request->date_to])
            ->selectRaw('
                categories.id,
                categories.name,
                categories.type,
                categories.color,
                categories.icon,
                SUM(transactions.amount) as total_amount,
                COUNT(transactions.id) as transactions_count,
                AVG(transactions.amount) as avg_amount
            ')
            ->groupBy('categories.id', 'categories.name', 'categories.type', 'categories.color', 'categories.icon');

        if ($request->filled('type')) {
            $query->where('categories.type', $request->type);
        }

        $limit = $request->get('limit', 20);
        $categories = $query->orderBy('total_amount', 'desc')
                           ->limit($limit)
                           ->get();

        // Converter valores de centavos para reais
        $categories = $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'type' => $category->type,
                'color' => $category->color,
                'icon' => $category->icon,
                'total_amount' => $category->total_amount / 100,
                'avg_amount' => $category->avg_amount / 100,
                'transactions_count' => $category->transactions_count,
            ];
        });

        // Calcular percentuais
        $totalAmount = $categories->sum('total_amount');
        $categories = $categories->map(function ($category) use ($totalAmount) {
            $category['percentage'] = $totalAmount > 0 ? ($category['total_amount'] / $totalAmount) * 100 : 0;
            return $category;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'categories' => $categories,
                'total_amount' => $totalAmount,
                'period' => [
                    'from' => $request->date_from,
                    'to' => $request->date_to,
                ]
            ]
        ]);
    }

    /**
     * Relatório por contas
     */
    public function byAccount(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'include_inactive' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Parâmetros inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = DB::table('transactions')
            ->join('accounts', 'transactions.account_id', '=', 'accounts.id')
            ->where('transactions.user_id', $user->id)
            ->whereBetween('transactions.date', [$request->date_from, $request->date_to])
            ->selectRaw('
                accounts.id,
                accounts.name,
                accounts.type,
                accounts.color,
                accounts.current_balance,
                SUM(CASE WHEN transactions.type = "income" THEN transactions.amount ELSE 0 END) as total_income,
                SUM(CASE WHEN transactions.type = "expense" THEN transactions.amount ELSE 0 END) as total_expense,
                COUNT(transactions.id) as transactions_count
            ')
            ->groupBy('accounts.id', 'accounts.name', 'accounts.type', 'accounts.color', 'accounts.current_balance');

        if (!$request->boolean('include_inactive')) {
            $query->where('accounts.active', true);
        }

        $accounts = $query->orderBy('transactions_count', 'desc')->get();

        // Converter valores e calcular saldos
        $accounts = $accounts->map(function ($account) {
            $totalIncome = $account->total_income / 100;
            $totalExpense = $account->total_expense / 100;
            
            return [
                'id' => $account->id,
                'name' => $account->name,
                'type' => $account->type,
                'color' => $account->color,
                'current_balance' => $account->current_balance,
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'net_flow' => $totalIncome - $totalExpense,
                'transactions_count' => $account->transactions_count,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'accounts' => $accounts,
                'period' => [
                    'from' => $request->date_from,
                    'to' => $request->date_to,
                ]
            ]
        ]);
    }
}
