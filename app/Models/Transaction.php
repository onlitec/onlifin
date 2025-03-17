<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    protected $fillable = [
        'type',
        'status',
        'transaction_type',
        'date',
        'description',
        'amount',
        'installments',
        'current_installment',
        'installment_frequency',
        'fixed_frequency',
        'fixed_end_date',
        'recurrence_frequency',
        'recurrence_end_date',
        'parent_transaction_id',
        'category_id',
        'account_id',
        'user_id',
        'notes'
    ];

    protected $casts = [
        'date' => 'datetime',
        'amount' => 'integer',
        'recurrence_end_date' => 'datetime',
        'fixed_end_date' => 'datetime',
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

    public function childTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'parent_transaction_id', 'id');
    }

    public function parentTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'parent_transaction_id');
    }

    // Acessor para formatar o valor
    public function getFormattedAmountAttribute()
    {
        return 'R$ ' . number_format($this->amount / 100, 2, ',', '.');
    }

    // Mutator para garantir que o valor seja armazenado corretamente
    public function setAmountAttribute($value)
    {
        if (is_string($value)) {
            $value = (float) str_replace(',', '.', str_replace('.', '', $value));
        }
        $this->attributes['amount'] = $value;
    }

    // Métodos auxiliares para status
    public function isPaid()
    {
        return $this->status === 'paid';
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    // Métodos auxiliares para tipo de transação
    public function isRegular()
    {
        return $this->transaction_type === 'regular';
    }

    public function isRecurring()
    {
        return $this->transaction_type === 'recurring';
    }

    public function isFixed()
    {
        return $this->transaction_type === 'fixed';
    }

    public function isInstallment()
    {
        return $this->transaction_type === 'installment';
    }

    // Obter descrição formatada para parcelas
    public function getInstallmentDescriptionAttribute()
    {
        if ($this->isInstallment() && $this->installments && $this->current_installment) {
            return "{$this->current_installment}/{$this->installments}";
        }
        return null;
    }
}