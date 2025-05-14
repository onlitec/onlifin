<?php

namespace App\Services;

use App\Models\Expense;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            if (isset($data['amount'])) {
                $data['amount'] = $this->formatAmount($data['amount']);
            }
            return Expense::create($data);
        });
    }

    public function formatAmount($amount)
    {
        if (empty($amount)) return 0;
        
        $value = str_replace(['R$', ' '], '', $amount);
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
        
        return (float) $value;
    }
} 