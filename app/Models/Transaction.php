<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'user_id',
        'notes'
    ];

    protected $casts = [
        'date' => 'datetime',
        'next_date' => 'datetime',
        'amount' => 'integer',
        'installment_number' => 'integer',
        'total_installments' => 'integer',
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

    // Adicione estes métodos auxiliares
    public function isPaid()
    {
        return $this->status === 'paid';
    }

    public function isPending()
    {
        return $this->status === 'pending';
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