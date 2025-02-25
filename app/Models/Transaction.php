<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'type',
        'status',
        'date',
        'description',
        'amount',
        'category_id',
        'account_id',
        'notes',
        'user_id'
    ];

    protected $casts = [
        'amount' => 'integer',
        'date' => 'date',
        'category_id' => 'integer',
        'account_id' => 'integer',
        'user_id' => 'integer'
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

    public function isPending(): bool
    {
        return $this->status === 'pending';
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
}