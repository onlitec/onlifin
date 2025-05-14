<?php

namespace App\Livewire\Transactions;

use Livewire\Component;
use App\Models\Transaction;
use Carbon\Carbon;

class Index extends Component
{
    public $currentMonth;
    public $searchTerm = '';

    public function mount()
    {
        $this->currentMonth = now();
    }

    public function previousMonth()
    {
        $this->currentMonth = $this->currentMonth->subMonth();
    }

    public function nextMonth()
    {
        $this->currentMonth = $this->currentMonth->addMonth();
    }

    public function render()
    {
        $transactions = Transaction::query()
            ->whereYear('date', $this->currentMonth->year)
            ->whereMonth('date', $this->currentMonth->month)
            ->when($this->searchTerm, function($query) {
                $query->where(function($q) {
                    $q->where('title', 'like', "%{$this->searchTerm}%")
                      ->orWhereHas('category', function($q) {
                          $q->where('name', 'like', "%{$this->searchTerm}%");
                      });
                });
            })
            ->orderBy('date', 'desc')
            ->get();

        return view('livewire.transactions.index', [
            'transactions' => $transactions
        ]);
    }
} 