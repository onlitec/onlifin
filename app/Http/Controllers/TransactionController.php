<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Category;
use App\Models\Account;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index()
    {
        $transactions = Transaction::with(['category', 'account'])
            ->orderBy('date', 'desc')
            ->paginate(10);

        return view('transactions.index', compact('transactions'));
    }

    public function create(Request $request)
    {
        // Determina o tipo de transação padrão com base no parâmetro da URL
        $type = $request->type ?? 'expense';
        
        // Filtra as categorias pelo tipo (receita ou despesa)
        $categories = Category::where('type', $type)->orderBy('name')->get();
        $accounts = Account::where('active', true)->orderBy('name')->get();
        
        return view('transactions.create', compact('categories', 'accounts', 'type'));
    }

    public function store(Request $request)
    {
        // Log do request para debug
        \Log::info('Request completo:', $request->all());

        $validated = $request->validate([
            'type' => 'required|in:income,expense',
            'status' => 'required|in:pending,paid',
            'date' => 'required|date',
            'description' => 'required|string|max:255',
            'amount' => 'required',
            'category_id' => 'required|exists:categories,id',
            'account_id' => 'required|exists:accounts,id',
            'notes' => 'nullable|string',
            'transaction_type' => 'required|in:regular,installment,fixed,recurring',
            'installments' => 'required_if:transaction_type,installment|integer|min:1',
            'installment_frequency' => 'required_if:transaction_type,installment|in:weekly,biweekly,monthly',
            'fixed_installments' => 'required_if:transaction_type,fixed|integer|min:1',
            'fixed_frequency' => 'required_if:transaction_type,fixed|in:weekly,biweekly,monthly,yearly',
            'fixed_end_date' => 'nullable|date|after:date',
            'recurrence_frequency' => 'required_if:transaction_type,recurring|in:daily,weekly,monthly,yearly',
            'recurrence_end_date' => 'required_if:transaction_type,recurring|date|after:date',
        ]);

        // Debug: Verificar o valor recebido
        \Log::info('Valor amount recebido: ' . $validated['amount']);

        // Processamento do valor monetário - removendo formatação
        $amountStr = str_replace(['R$', '.'], '', $validated['amount']);
        $amountStr = str_replace(',', '.', $amountStr);
        $amount = (float) $amountStr;
        
        // Convertendo para centavos
        $amount = round($amount * 100);

        // Debug: Verificar o valor final
        \Log::info('Valor amount convertido para centavos: ' . $amount);

        // Criar a transação principal
        $transaction = Transaction::create([
            'type' => $validated['type'],
            'status' => $validated['status'],
            'transaction_type' => $validated['transaction_type'],
            'date' => $validated['date'],
            'description' => $validated['description'],
            'amount' => $amount,
            'category_id' => $validated['category_id'],
            'account_id' => $validated['account_id'],
            'notes' => $validated['notes'] ?? null,
            'user_id' => auth()->id(),
        ]);

        // Processar transações adicionais de acordo com o tipo
        if ($validated['transaction_type'] === 'installment') {
            $this->createInstallments($transaction, $validated);
        } elseif ($validated['transaction_type'] === 'fixed') {
            $this->createFixedTransactions($transaction, $validated);
        } elseif ($validated['transaction_type'] === 'recurring') {
            $this->createRecurringTransactions($transaction, $validated);
        }

        $redirectRoute = $validated['type'] === 'income' ? 'transactions.income' : 'transactions.expenses';
        return redirect()->route($redirectRoute)
            ->with('success', 'Transação criada com sucesso!');
    }

    // Método para criar transações parceladas
    private function createInstallments($parentTransaction, $data)
    {
        // Atualiza a transação pai para ser a primeira parcela
        $parentTransaction->update([
            'installments' => $data['installments'],
            'current_installment' => 1,
            'installment_frequency' => $data['installment_frequency'] ?? 'monthly'
        ]);

        // Criar as outras parcelas
        $dateObj = \Carbon\Carbon::parse($data['date']);
        
        for ($i = 2; $i <= $data['installments']; $i++) {
            // Calcular a próxima data com base na frequência
            $dateObj = $this->addFrequencyInterval($dateObj, $data['installment_frequency'] ?? 'monthly');
            
            Transaction::create([
                'description' => $data['description'],
                'amount' => $parentTransaction->amount,
                'date' => $dateObj->format('Y-m-d'),
                'account_id' => $data['account_id'],
                'category_id' => $data['category_id'],
                'type' => $data['type'],
                'status' => $data['status'],
                'transaction_type' => 'installment',
                'installments' => $data['installments'],
                'current_installment' => $i,
                'installment_frequency' => $data['installment_frequency'] ?? 'monthly',
                'parent_transaction_id' => $parentTransaction->id,
                'notes' => $data['notes'] ?? null,
                'user_id' => auth()->id(),
            ]);
        }
    }

    // Método para criar transações fixas
    private function createFixedTransactions($parentTransaction, $data)
    {
        // Atualiza a transação pai
        $parentTransaction->update([
            'installments' => $data['fixed_installments'],
            'current_installment' => 1,
            'fixed_frequency' => $data['fixed_frequency'] ?? 'monthly',
            'fixed_end_date' => isset($data['fixed_end_date']) ? $data['fixed_end_date'] : null
        ]);

        // Criar as transações fixas com o mesmo valor
        $dateObj = \Carbon\Carbon::parse($data['date']);
        
        for ($i = 2; $i <= $data['fixed_installments']; $i++) {
            // Calcular a próxima data com base na frequência
            $dateObj = $this->addFrequencyInterval($dateObj, $data['fixed_frequency'] ?? 'monthly');
            
            Transaction::create([
                'description' => $data['description'],
                'amount' => $parentTransaction->amount,
                'date' => $dateObj->format('Y-m-d'),
                'account_id' => $data['account_id'],
                'category_id' => $data['category_id'],
                'type' => $data['type'],
                'status' => $data['status'],
                'transaction_type' => 'fixed',
                'installments' => $data['fixed_installments'],
                'current_installment' => $i,
                'fixed_frequency' => $data['fixed_frequency'] ?? 'monthly',
                'fixed_end_date' => isset($data['fixed_end_date']) ? $data['fixed_end_date'] : null,
                'parent_transaction_id' => $parentTransaction->id,
                'notes' => $data['notes'] ?? null,
                'user_id' => auth()->id(),
            ]);
        }
    }

    // Método para criar transações recorrentes
    private function createRecurringTransactions($parentTransaction, $data)
    {
        // Atualiza a transação pai
        $parentTransaction->update([
            'recurrence_frequency' => $data['recurrence_frequency'],
            'recurrence_end_date' => $data['recurrence_end_date']
        ]);

        // Define o intervalo baseado na frequência
        $interval = $this->getIntervalFromFrequency($data['recurrence_frequency']);
        
        // Criar transações recorrentes até a data final
        $startDate = \Carbon\Carbon::parse($data['date']);
        $endDate = \Carbon\Carbon::parse($data['recurrence_end_date']);
        $currentDate = $startDate->copy();
        
        while ($currentDate->lt($endDate)) {
            // Calcular a próxima data
            $currentDate = $this->addInterval($currentDate, $interval);
            
            // Se a data atual ultrapassou a data final, sair do loop
            if ($currentDate->gt($endDate)) {
                break;
            }
            
            Transaction::create([
                'description' => $data['description'],
                'amount' => $parentTransaction->amount,
                'date' => $currentDate->format('Y-m-d'),
                'account_id' => $data['account_id'],
                'category_id' => $data['category_id'],
                'type' => $data['type'],
                'status' => $data['status'],
                'transaction_type' => 'recurring',
                'recurrence_frequency' => $data['recurrence_frequency'],
                'parent_transaction_id' => $parentTransaction->id,
                'notes' => $data['notes'] ?? null,
                'user_id' => auth()->id(),
            ]);
        }
    }

    private function getIntervalFromFrequency($frequency)
    {
        switch ($frequency) {
            case 'daily':
                return 'day';
            case 'weekly':
                return 'week';
            case 'monthly':
                return 'month';
            case 'yearly':
                return 'year';
            default:
                return 'month';
        }
    }

    private function addInterval($date, $interval)
    {
        switch ($interval) {
            case 'day':
                return $date->copy()->addDay();
            case 'week':
                return $date->copy()->addWeek();
            case 'month':
                return $date->copy()->addMonth();
            case 'year':
                return $date->copy()->addYear();
            default:
                return $date->copy()->addMonth();
        }
    }

    // Função auxiliar para calcular o intervalo com base na frequência
    private function addFrequencyInterval($date, $frequency)
    {
        switch ($frequency) {
            case 'weekly':
                return $date->copy()->addWeek();
            case 'biweekly':
                return $date->copy()->addWeeks(2);
            case 'monthly':
                return $date->copy()->addMonth();
            case 'yearly':
                return $date->copy()->addYear();
            default:
                return $date->copy()->addMonth();
        }
    }

    public function edit(Transaction $transaction)
    {
        // Verifica se o usuário tem permissão para editar esta transação
        if ($transaction->user_id !== auth()->id()) {
            abort(403);
        }

        $categories = Category::where('type', $transaction->type)->get();
        $accounts = Account::where('active', true)->get();

        return view('transactions.edit', compact('transaction', 'categories', 'accounts'));
    }

    public function update(Request $request, Transaction $transaction)
    {
        if ($transaction->user_id !== auth()->id()) {
            abort(403);
        }

        // Log do request para debug
        \Log::info('Request completo de update:', $request->all());

        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required',
            'date' => 'required|date',
            'type' => 'required|in:income,expense',
            'status' => 'required|in:pending,paid',
            'category_id' => 'required|exists:categories,id',
            'account_id' => 'required|exists:accounts,id',
            'notes' => 'nullable|string',
            'transaction_type' => 'required|in:regular,installment,fixed,recurring',
            'installments' => 'required_if:transaction_type,installment|integer|min:1',
            'installment_frequency' => 'required_if:transaction_type,installment|in:weekly,biweekly,monthly',
            'fixed_installments' => 'required_if:transaction_type,fixed|integer|min:1',
            'fixed_frequency' => 'required_if:transaction_type,fixed|in:weekly,biweekly,monthly,yearly',
            'fixed_end_date' => 'nullable|date|after:date',
            'recurrence_frequency' => 'required_if:transaction_type,recurring|in:daily,weekly,monthly,yearly',
            'recurrence_end_date' => 'required_if:transaction_type,recurring|date|after:date',
        ]);

        // Debug: Verificar o valor recebido
        \Log::info('Valor amount recebido na atualização: ' . $validated['amount']);

        // Processamento do valor monetário - removendo formatação
        $amountStr = str_replace(['R$', '.'], '', $validated['amount']);
        $amountStr = str_replace(',', '.', $amountStr);
        $amount = (float) $amountStr;
        
        // Convertendo para centavos
        $amount = round($amount * 100);

        // Debug: Verificar o valor final
        \Log::info('Valor amount convertido para centavos na atualização: ' . $amount);
        
        // Atualizar dados da transação
        $transaction->update([
            'type' => $validated['type'],
            'status' => $validated['status'],
            'date' => $validated['date'],
            'description' => $validated['description'],
            'amount' => $amount,
            'category_id' => $validated['category_id'],
            'account_id' => $validated['account_id'],
            'notes' => $validated['notes'] ?? null,
            'transaction_type' => $validated['transaction_type'],
        ]);

        // Atualizar campos específicos de acordo com o tipo de transação
        if ($transaction->transaction_type === 'installment') {
            $transaction->update([
                'installments' => $validated['installments'],
                'current_installment' => $transaction->current_installment ?? 1,
                'installment_frequency' => $validated['installment_frequency']
            ]);
        } elseif ($transaction->transaction_type === 'fixed') {
            $transaction->update([
                'installments' => $validated['fixed_installments'],
                'current_installment' => $transaction->current_installment ?? 1,
                'fixed_frequency' => $validated['fixed_frequency'],
                'fixed_end_date' => isset($validated['fixed_end_date']) ? $validated['fixed_end_date'] : null
            ]);
        } elseif ($transaction->transaction_type === 'recurring') {
            $transaction->update([
                'recurrence_frequency' => $validated['recurrence_frequency'],
                'recurrence_end_date' => $validated['recurrence_end_date']
            ]);
        }

        $redirectRoute = $validated['type'] === 'income' ? 'transactions.income' : 'transactions.expenses';
        return redirect()->route($redirectRoute)
            ->with('success', 'Transação atualizada com sucesso!');
    }

    public function destroy(Transaction $transaction)
    {
        // Verifica se o usuário tem permissão para excluir esta transação
        if ($transaction->user_id !== auth()->id()) {
            abort(403);
        }

        try {
            $transaction->delete();
            return redirect()
                ->back()
                ->with('success', 'Transação excluída com sucesso!');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erro ao excluir transação: ' . $e->getMessage());
        }
    }

    public function markAsPaid(Transaction $transaction)
    {
        if ($transaction->user_id !== auth()->id()) {
            abort(403);
        }

        $transaction->update(['status' => 'paid']);

        $message = $transaction->type === 'income' 
            ? 'Receita marcada como recebida!' 
            : 'Despesa marcada como paga!';

        return back()->with('success', $message);
    }

    public function showIncome()
    {
        return view('transactions.income');
    }

    public function showExpenses()
    {
        return view('transactions.expenses');
    }
} 