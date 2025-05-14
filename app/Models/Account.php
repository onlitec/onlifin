<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ATENÇÃO: CONFIGURAÇÃO CRÍTICA
 * 
 * Este modelo contém lógica financeira essencial para o cálculo correto de saldos.
 * NÃO MODIFICAR sem consultar o documento FINANCIAL_RULES.md.
 * Alterações neste arquivo podem causar erros de cálculo no sistema financeiro.
 */
class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'initial_balance',
        'active',
        'user_id',
        'description',
        'color',
    ];

    /**
     * ATENÇÃO: CONFIGURAÇÃO CRÍTICA - NÃO MODIFICAR
     * 
     * Estes casts são essenciais para o correto funcionamento do cálculo de saldos.
     * Valores em 'decimal:2' garantem a precisão nos cálculos financeiros.
     */
    protected $casts = [
        'initial_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'active' => 'boolean',
    ];

    /**
     * ATENÇÃO: CONFIGURAÇÃO CRÍTICA - NÃO MODIFICAR
     * 
     * Este valor padrão é essencial para evitar erros 500 quando current_balance não existe.
     */
    protected $attributes = [
        'current_balance' => 0,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * ATENÇÃO: CONFIGURAÇÃO CRÍTICA - NÃO MODIFICAR
     * 
     * Recalculate and return the current balance for this account.
     * 
     * Este método implementa as regras oficiais de cálculo de saldo:
     * - Valores são armazenados em centavos no banco
     * - Saldo = saldo_inicial + receitas - despesas
     * - Se não houver transações, o saldo atual é igual ao saldo inicial
     * 
     * Modificar este método pode causar inconsistências financeiras em TODO o sistema.
     * Ver FINANCIAL_RULES.md para detalhes completos.
     */
    public function recalculateBalance(): float
    {
        // Inicializar com o saldo inicial (já está em reais)
        $initialBalance = $this->initial_balance ?? 0;
        
        // Buscar todas as transações pagas relacionadas à esta conta
        $incomeCents = $this->transactions()
            ->where('type', 'income')
            ->where('status', 'paid')
            ->sum('amount');
            
        $expenseCents = $this->transactions()
            ->where('type', 'expense')
            ->where('status', 'paid')
            ->sum('amount');
        
        // Se não houver transações, retorna apenas o saldo inicial
        $hasTransactions = ($incomeCents > 0 || $expenseCents > 0);
        if (!$hasTransactions) {
            $this->attributes['current_balance'] = $initialBalance;
            return $initialBalance;
        }
        
        // Converter centavos para reais
        $income = $incomeCents / 100;
        $expense = $expenseCents / 100;
        
        // Calcular saldo: inicial + receitas - despesas
        $balance = $initialBalance + $income - $expense;
        
        // Atualizar o atributo do modelo
        $this->attributes['current_balance'] = $balance;
        
        // Log para debugging
        \Log::debug("Conta {$this->id} ({$this->name}): Saldo inicial R$ {$initialBalance} + Receitas R$ {$income} - Despesas R$ {$expense} = Saldo final R$ {$balance}");
        
        return $balance;
    }

    /**
     * ATENÇÃO: CONFIGURAÇÃO CRÍTICA - NÃO MODIFICAR
     * 
     * Accessor for current_balance attribute to compute up-to-date balance.
     * Este método garante que o saldo atual seja sempre calculado corretamente.
     */
    public function getCurrentBalanceAttribute($value): float
    {
        // Return the provided value if it exists, otherwise calculate it
        if (!is_null($value)) {
            return $value;
        }
        
        return $this->recalculateBalance();
    }

    /**
     * Get human-readable label for the account type in Portuguese.
     */
    public function getTypeLabelAttribute(): string
    {
        $labels = [
            'checking'     => 'Conta Corrente',
            'savings'      => 'Conta Poupança',
            'investment'   => 'Investimento',
            'credit_card'  => 'Cartão de Crédito',
            'cash'         => 'Dinheiro',
            'other'        => 'Outro',
        ];
        return $labels[$this->type] ?? ucfirst($this->type);
    }
} 