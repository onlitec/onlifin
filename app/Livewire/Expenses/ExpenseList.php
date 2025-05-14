<?php

namespace App\Livewire\Expenses;

use Livewire\Component;
use App\Models\Expense;
use App\Models\Category;
use App\Services\ExpenseService;
use Carbon\Carbon;
use App\Models\Transaction;

class ExpenseList extends Component
{
    // Propriedades para despesa
    public $title = '';
    public $amount = '';
    public $date;
    public $category_id = '';
    
    // Propriedades para nova categoria
    public $newCategory = '';
    public $showCategoryForm = false;

    public $categorySearch = '';
    public $showCategoryDropdown = false;

    protected $expenseService;

    public function mount()
    {
        $this->expenseService = app(ExpenseService::class);
        $this->date = now()->format('Y-m-d');
        $this->dispatch('initMask');
    }

    public function hydrate()
    {
        $this->dispatch('initMask');
    }

    protected $rules = [
        'title' => 'required|min:3',
        'amount' => 'required|numeric|min:0.01',
        'date' => 'required|date',
        'category_id' => 'required|exists:categories,id',
    ];

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

    public function saveExpense()
    {
        $this->validate();

        try {
            $this->expenseService->create([
                'title' => $this->title,
                'amount' => $this->amount,
                'date' => $this->date,
                'category_id' => $this->category_id,
                'user_id' => auth()->id()
            ]);

            $this->reset(['title', 'amount', 'category_id']);
            $this->date = now()->format('Y-m-d');
            session()->flash('success', 'Despesa registrada com sucesso!');
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao registrar despesa.');
        }
    }

    public function toggleCategoryForm()
    {
        $this->showCategoryForm = !$this->showCategoryForm;
    }

    public function saveCategory()
    {
        $this->validate([
            'newCategory' => 'required|min:3|unique:categories,name'
        ]);

        $category = Category::create([
            'name' => $this->newCategory,
            'type' => 'expense'
        ]);

        $this->category_id = $category->id;
        $this->newCategory = '';
        $this->showCategoryForm = false;
        session()->flash('success', 'Categoria criada com sucesso!');
    }

    public function toggleCategoryDropdown()
    {
        $this->showCategoryDropdown = !$this->showCategoryDropdown;
    }

    public function selectCategory($categoryId)
    {
        $category = Category::find($categoryId);
        if ($category) {
            $this->category_id = $category->id;
            $this->categorySearch = $category->name;
            $this->showCategoryDropdown = false;
        }
    }

    public function createCategory()
    {
        if (empty($this->categorySearch)) {
            return;
        }

        $this->validate([
            'categorySearch' => 'required|min:3|unique:categories,name'
        ]);

        $category = Category::create([
            'name' => $this->categorySearch,
            'type' => 'expense'
        ]);

        $this->category_id = $category->id;
        $this->showCategoryDropdown = false;
        session()->flash('success', 'Categoria criada com sucesso!');
    }

    public function updatedCategorySearch()
    {
        $this->showCategoryDropdown = true;
        $this->category_id = null; // Limpa a categoria selecionada ao digitar
    }

    public function render()
    {
        $categories = Category::where('type', 'expense')
            ->orderBy('name')
            ->get();
        
        $expenses = Transaction::with('category')
            ->where('user_id', auth()->id())
            ->where('type', 'expense')  // Filtra apenas despesas
            ->orderBy('date', 'desc')
            ->get();
        
        return view('livewire.expenses.expense-list', [
            'categories' => $categories,
            'expenses' => $expenses
        ])->layout('layouts.app');
    }
} 