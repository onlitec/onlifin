<?php

namespace App\Livewire\Incomes;

use Livewire\Component;
use App\Models\Category;
use App\Models\Transaction;

class IncomeList extends Component
{
    public function render()
    {
        $categories = Category::where('type', 'income')
            ->orderBy('name')
            ->get();
        
        $incomes = Transaction::with('category')
            ->where('user_id', auth()->id())
            ->where('type', 'income')  // Filtra apenas receitas
            ->orderBy('date', 'desc')
            ->get();
        
        return view('livewire.incomes.income-list', [
            'categories' => $categories,
            'incomes' => $incomes
        ])->layout('layouts.app');
    }
} 