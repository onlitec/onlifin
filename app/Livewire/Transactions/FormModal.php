<?php

namespace App\Livewire\Transactions;

use Livewire\Component;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Carbon\Carbon;

class FormModal extends Component
{
    public $type = 'expense';
    public $description = '';
    public $amount = '';
    public $date;
    public $account_id = '';
    public $category_id = '';
    public $note = '';
    
    // Novos campos
    public $transaction_type = 'regular';
    public $installments = 1;
    public $recurrence_frequency = 'monthly';
    public $recurrence_end_date = '';
    
    // Estados de visibilidade
    public $showType = false;
    public $showRepeat = false;
    public $showNote = false;
    public $showAttachment = false;
    public $showTags = false;

    protected $rules = [
        'description' => 'required|min:3',
        'amount' => 'required|numeric|min:0.01',
        'date' => 'required|date',
        'account_id' => 'required|exists:accounts,id',
        'category_id' => 'required|exists:categories,id',
        'transaction_type' => 'required|in:regular,recurring,fixed,installment',
        'installments' => 'required_if:transaction_type,installment|integer|min:1',
        'recurrence_frequency' => 'required_if:transaction_type,recurring|in:daily,weekly,monthly,yearly',
        'recurrence_end_date' => 'nullable|date|after:date',
    ];

    public function mount($type = null)
    {
        if ($type) {
            $this->type = $type;
        }
        $this->date = now()->format('Y-m-d');
        
        // Definindo uma data padrão para o fim da recorrência (1 ano)
        $this->recurrence_end_date = now()->addYear()->format('Y-m-d');
    }

    public function updatedTransactionType()
    {
        if ($this->transaction_type === 'regular') {
            $this->showRepeat = false;
        } else {
            $this->showRepeat = true;
        }
    }

    public function toggleType()
    {
        $this->showType = !$this->showType;
    }

    public function toggleRepeat()
    {
        $this->showRepeat = !$this->showRepeat;
    }

    public function toggleNote()
    {
        $this->showNote = !$this->showNote;
    }

    public function toggleAttachment()
    {
        $this->showAttachment = !$this->showAttachment;
    }

    public function toggleTags()
    {
        $this->showTags = !$this->showTags;
    }

    public function save()
    {
        $this->validate();

        // Criar a transação principal
        $transaction = Transaction::create([
            'description' => $this->description,
            'amount' => $this->amount,
            'date' => $this->date,
            'account_id' => $this->account_id,
            'category_id' => $this->category_id,
            'type' => $this->type,
            'transaction_type' => $this->transaction_type,
            'note' => $this->note,
            'user_id' => auth()->id(),
        ]);

        // Processar de acordo com o tipo de transação
        if ($this->transaction_type === 'installment') {
            $this->createInstallments($transaction);
        } elseif ($this->transaction_type === 'recurring') {
            $this->createRecurringTransactions($transaction);
        } elseif ($this->transaction_type === 'fixed') {
            $this->createFixedTransactions($transaction);
        }

        $this->reset();
        $this->dispatch('transactionSaved');
        $this->closeModal();
    }

    private function createInstallments($parentTransaction)
    {
        // Atualiza a transação pai para ser a primeira parcela
        $parentTransaction->update([
            'installments' => $this->installments,
            'current_installment' => 1
        ]);

        // Criar as outras parcelas
        $dateObj = Carbon::parse($this->date);
        
        for ($i = 2; $i <= $this->installments; $i++) {
            $dateObj = $dateObj->addMonth();
            
            Transaction::create([
                'description' => $this->description,
                'amount' => $this->amount,
                'date' => $dateObj->format('Y-m-d'),
                'account_id' => $this->account_id,
                'category_id' => $this->category_id,
                'type' => $this->type,
                'transaction_type' => 'installment',
                'installments' => $this->installments,
                'current_installment' => $i,
                'parent_transaction_id' => $parentTransaction->id,
                'note' => $this->note,
                'user_id' => auth()->id(),
            ]);
        }
    }

    private function createRecurringTransactions($parentTransaction)
    {
        // Atualiza a transação pai
        $parentTransaction->update([
            'recurrence_frequency' => $this->recurrence_frequency,
            'recurrence_end_date' => $this->recurrence_end_date
        ]);

        // Define o intervalo baseado na frequência
        $interval = $this->getIntervalFromFrequency();
        
        // Criar transações recorrentes até a data final
        $startDate = Carbon::parse($this->date);
        $endDate = Carbon::parse($this->recurrence_end_date);
        $currentDate = $startDate->copy();
        
        while ($currentDate->lt($endDate)) {
            // Calcular a próxima data
            $currentDate = $this->addInterval($currentDate, $interval);
            
            // Se a data atual ultrapassou a data final, sair do loop
            if ($currentDate->gt($endDate)) {
                break;
            }
            
            Transaction::create([
                'description' => $this->description,
                'amount' => $this->amount,
                'date' => $currentDate->format('Y-m-d'),
                'account_id' => $this->account_id,
                'category_id' => $this->category_id,
                'type' => $this->type,
                'transaction_type' => 'recurring',
                'recurrence_frequency' => $this->recurrence_frequency,
                'parent_transaction_id' => $parentTransaction->id,
                'note' => $this->note,
                'user_id' => auth()->id(),
            ]);
        }
    }

    private function createFixedTransactions($parentTransaction)
    {
        // Atualiza a transação pai
        $parentTransaction->update([
            'installments' => $this->installments,
            'current_installment' => 1
        ]);

        // Criar as transações fixas com o mesmo valor
        $dateObj = Carbon::parse($this->date);
        
        for ($i = 2; $i <= $this->installments; $i++) {
            $dateObj = $dateObj->addMonth();
            
            Transaction::create([
                'description' => $this->description,
                'amount' => $this->amount,
                'date' => $dateObj->format('Y-m-d'),
                'account_id' => $this->account_id,
                'category_id' => $this->category_id,
                'type' => $this->type,
                'transaction_type' => 'fixed',
                'installments' => $this->installments,
                'current_installment' => $i,
                'parent_transaction_id' => $parentTransaction->id,
                'note' => $this->note,
                'user_id' => auth()->id(),
            ]);
        }
    }

    private function getIntervalFromFrequency()
    {
        switch ($this->recurrence_frequency) {
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

    public function closeModal()
    {
        $this->dispatch('closeModal');
    }

    public function updatedAmount($value)
    {
        if (!empty($value)) {
            // Remove R$ e espaços
            $value = str_replace(['R$', ' '], '', $value);
            // Substitui pontos por nada e vírgula por ponto
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
            $this->amount = (float) $value;
        }
    }

    public function render()
    {
        try {
            $accounts = Account::where('user_id', auth()->id())->get();
            $categories = Category::where('type', $this->type)->get();
        } catch (\Exception $e) {
            $accounts = collect();
            $categories = collect();
        }

        return view('livewire.transactions.form-modal', [
            'accounts' => $accounts,
            'categories' => $categories
        ]);
    }
} 