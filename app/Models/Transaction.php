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

    protected $rules = [
        'type' => 'required|in:income,expense',
        'status' => 'required|in:paid,pending',
        'date' => 'required|date',
        'description' => 'required|string|max:255',
        'amount' => 'required|numeric|min:0',
        'category_id' => 'required|exists:categories,id',
        'account_id' => 'required|exists:accounts,id',
        'user_id' => 'required|exists:users,id',
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

    // Acessor para formatar o valor
    public function getFormattedAmountAttribute()
    {
        return 'R$ ' . number_format($this->amount / 100, 2, ',', '.');
    }

    // Mutator para garantir que o valor seja armazenado corretamente
    public function setAmountAttribute($value)
    {
        if (is_null($value)) {
            $this->attributes['amount'] = 0;
            return;
        }

        if (is_string($value)) {
            // Remove qualquer caractere que não seja número ou ponto/vírgula
            $value = preg_replace('/[^\d.,-]/', '', $value);
            // Substitui vírgula por ponto
            $value = str_replace(',', '.', $value);
            // Remove pontos de milhar
            $value = str_replace('.', '', $value);
        }

        // Converte para float e multiplica por 100 para armazenar em centavos
        $value = (float) $value * 100;
        
        // Arredonda para evitar problemas com ponto flutuante
        $value = round($value);

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