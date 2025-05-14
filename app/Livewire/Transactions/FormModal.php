<?php

namespace App\Livewire\Transactions;

use Livewire\Component;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;

class FormModal extends Component
{
    public $type = 'expense';
    public $description = '';
    public $amount = '';
    public $date;
    public $account_id = '';
    public $category_id = '';
    public $note = '';
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
    ];

    public function mount($type = null)
    {
        if ($type) {
            $this->type = $type;
        }
        $this->date = now()->format('Y-m-d');
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
        
        // Converte para centavos apenas uma vez
        $amountInCents = $this->convertToCents($this->amount);
        
        \Log::info('Salvando transação com valor: ' . $this->amount . ' => ' . $amountInCents . ' centavos');

        Transaction::create([
            'description' => $this->description,
            'amount' => $amountInCents,
            'date' => $this->date,
            'account_id' => $this->account_id,
            'category_id' => $this->category_id,
            'type' => $this->type,
            'note' => $this->note,
            'user_id' => auth()->id(),
        ]);

        $this->reset();
        $this->dispatch('transactionSaved');
        $this->closeModal();
    }
    
    /**
     * Converte um valor no formato brasileiro (1.234,56) para centavos (123456)
     */
    private function convertToCents($value)
    {
        // Remove todos os pontos (separadores de milhar)
        $value = str_replace('.', '', $value);
        
        // Substitui vírgula por ponto para cálculo
        $value = str_replace(',', '.', $value);
        
        // Multiplica por 100 e arredonda para obter os centavos
        return round((float)$value * 100);
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
            
            // Mantém o valor como string
            $this->amount = $value;
        }
    }

    public function formatAmount()
    {
        if (!empty($this->amount)) {
            // Remove todos os pontos (separadores de milhar)
            $value = str_replace('.', '', $this->amount);
            
            // Substitui vírgula por ponto para formatação
            $value = str_replace(',', '.', $value);
            
            // Formata para moeda brasileira
            $this->amount = number_format((float)$value, 2, ',', '.');
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