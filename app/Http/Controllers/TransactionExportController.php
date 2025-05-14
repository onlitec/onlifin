<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

class TransactionExportController extends Controller
{
    public function export(Request $request)
    {
        $user = auth()->user();
        $message = "Usuário {$user->name} (ID: {$user->id}) iniciou exportação de transações";
        
        Log::info($message, [
            'action' => 'export',
            'user_id' => $user->id,
            'filters' => $request->all()
        ]);

        $transactions = Transaction::with(['category', 'account'])
            ->when($request->filled('type'), function ($query) use ($request) {
                $query->where('type', $request->type);
            })
            ->when($request->filled('category'), function ($query) use ($request) {
                $query->where('category_id', $request->category);
            })
            ->when($request->filled('account'), function ($query) use ($request) {
                $query->where('account_id', $request->account);
            })
            ->when($request->filled('start_date'), function ($query) use ($request) {
                $query->whereDate('date', '>=', $request->start_date);
            })
            ->when($request->filled('end_date'), function ($query) use ($request) {
                $query->whereDate('date', '<=', $request->end_date);
            })
            ->orderBy('date', 'desc')
            ->get();

        // Preparar dados para exportação
        $exportData = [];
        foreach ($transactions as $transaction) {
            $exportData[] = [
                'Data' => $transaction->date->format('d/m/Y'),
                'Tipo' => $transaction->type === 'income' ? 'Receita' : 'Despesa',
                'Descrição' => $transaction->description,
                'Categoria' => $transaction->category->name,
                'Conta' => $transaction->account->name,
                'Valor' => 'R$ ' . number_format($transaction->amount / 100, 2, ',', '.'),
                'Status' => $transaction->status === 'paid' ? 'Pago' : 'Pendente',
                'Observações' => $transaction->notes ?? ''
            ];
        }

        // Gerar arquivo CSV
        $filename = 'transacoes_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $headers = array_keys($exportData[0] ?? []);

        $handle = fopen('php://memory', 'r+');
        fputcsv($handle, $headers, ';');

        foreach ($exportData as $row) {
            fputcsv($handle, $row, ';');
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        $message = "Usuário {$user->name} (ID: {$user->id}) concluiu exportação de transações com " . count($transactions) . " registros";
        Log::info($message, [
            'action' => 'export_completed',
            'user_id' => $user->id,
            'transaction_count' => count($transactions)
        ]);

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Content-Length', strlen($csv));
    }
}
