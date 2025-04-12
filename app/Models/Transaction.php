<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Validator;

class Transaction extends Model
{
    protected $fillable = [
        'type',
        'status',
        'recurrence_type',
        'installment_number',
        'total_installments',
        'next_date',
        'date',
        'description',
        'amount',
        'category_id',
        'account_id',
        'notes',
        'user_id'
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'integer',
        'status' => 'string',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function validate(array $data)
    {
        return Validator::make($data, $this->rules);
    }

    public function isPending(): bool
    {
        return number_format($this->amount / 100, 2, ',', '.');
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isIncome(): bool
    {
        return $this->type === 'income';
    }

    public function isExpense(): bool
    {
        return $this->type === 'expense';
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'R$ ' . number_format($this->amount / 100, 2, ',', '.');
    }

    public function setAmountAttribute($value)
    {
        // Remove formatação e converte para centavos
        $amount = str_replace(['R$', '.', ','], ['', '', '.'], $value);
        $this->attributes['amount'] = round((float) $amount * 100);
    }

    // Métodos para recorrência
    public function hasRecurrence()
    {
        return $this->recurrence_type && $this->recurrence_type !== 'none';
    }

    public function isFixedRecurrence()
    {
        return $this->recurrence_type === 'fixed';
    }

    public function isInstallmentRecurrence()
    {
        return $this->recurrence_type === 'installment';
    }

    public function getFormattedInstallmentAttribute()
    {
        if (!$this->isInstallmentRecurrence() || !$this->installment_number || !$this->total_installments) {
            return '';
        }

        return "Parcela {$this->installment_number}/{$this->total_installments}";
    }
}