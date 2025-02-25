<?php

namespace App\Livewire\Transactions;

use Livewire\Component;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\Account;

class FormModal extends Component
{
    public $value = 0;
    public $description = '';
    public $date;
    public $type = 'expense';
    public $category_id = '';
    public $account_id = '';
    public $observations = '';
    public $status = 'pending';

    protected $rules = [
        'value' => 'required|numeric',
        'description' => 'required|string|max:255',
        'date' => 'required|date',
        'type' => 'required|in:income,expense',
        'category_id' => 'required|exists:categories,id',
        'account_id' => 'required|exists:accounts,id',
        'observations' => 'nullable|string',
        'status' => 'required|in:pending,completed',
    ];

    public function mount()
    {
        $this->date = now()->format('Y-m-d');
        $this->categories = Category::all();
        $this->accounts = Account::all();
    }

    public function updatedValue($value)
    {
        // Remove formatação e converte para float
        $value = str_replace(['R$', '.', ','], ['', '', '.'], $value);
        $this->value = (float) $value;
    }

    public function saveTransaction()
    {
        $validatedData = $this->validate();

        try {
            Transaction::create([
                'value' => $this->value,
                'description' => $this->description,
                'date' => $this->date,
                'type' => $this->type,
                'category_id' => $this->category_id,
                'account_id' => $this->account_id,
                'observations' => $this->observations,
                'status' => $this->status,
                'user_id' => auth()->id(), // Adiciona o user_id
            ]);

            $this->reset(['value', 'description', 'category_id', 'account_id', 'observations']);
            $this->date = now()->format('Y-m-d');
            $this->dispatch('transaction-saved');
            $this->dispatch('closeModal');
        } catch (\Exception $e) {
            \Log::error('Erro ao salvar transação: ' . $e->getMessage());
            session()->flash('error', 'Erro ao salvar a transação.');
        }
    }

    public function getFormattedValueProperty()
    {
        return 'R$ ' . number_format((float) $this->value, 2, ',', '.');
    }

    public function render()
    {
        return view('livewire.transactions.form-modal', [
            'categories' => Category::where('type', $this->type)->get(),
            'accounts' => Account::where('user_id', auth()->id())->get(),
        ]);
    }
}