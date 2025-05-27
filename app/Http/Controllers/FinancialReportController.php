<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;
use Carbon\Carbon;

class FinancialReportController extends Controller
{
    /**
     * Exibe a página principal de relatórios com gráficos.
     */
    public function index()
    {
        return view('reports.financial.index');
    }

    /**
     * Fornece dados para o gráfico de despesas por categoria.
     */
    public function expensesByCategory(Request $request)
    {
        $user = Auth::user();
        $startDate = $request->input('start_date', Carbon::now()->subMonth()->startOfDay());
        $endDate = $request->input('end_date', Carbon::now()->endOfDay());

        if (is_string($startDate)) {
            $startDate = Carbon::parse($startDate)->startOfDay();
        }
        if (is_string($endDate)) {
            $endDate = Carbon::parse($endDate)->endOfDay();
        }

        $expenses = Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->where('status', 'paid') // Considerar apenas transações pagas
            ->whereBetween('date', [$startDate, $endDate])
            ->with('category') // Carregar a relação com categoria
            ->selectRaw('category_id, SUM(amount) as total_amount')
            ->groupBy('category_id')
            ->orderBy('total_amount', 'desc')
            ->get();

        $labels = [];
        $data = [];
        $backgroundColors = [];

        // Cores predefinidas para os gráficos (pode adicionar mais ou gerar dinamicamente)
        $defaultColors = [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#C9CBCF',
            '#2ECC71', '#E74C3C', '#F1C40F', '#8E44AD', '#3498DB', '#1ABC9C', '#D35400'
        ];

        foreach ($expenses as $index => $expense) {
            $labels[] = $expense->category ? $expense->category->name : 'Sem Categoria';
            $data[] = $expense->total_amount / 100; // Converter centavos para reais
            $backgroundColors[] = $defaultColors[$index % count($defaultColors)];
        }

        return response()->json([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Gastos por Categoria (R$)',
                    'data' => $data,
                    'backgroundColor' => $backgroundColors
                ]
            ]
        ]);
    }
} 