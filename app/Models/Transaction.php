<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Validator;

/**
 * ATENÇÃO: CONFIGURAÇÃO CRÍTICA
 * 
 * Este modelo contém lógica financeira essencial para o tratamento de valores monetários.
 * NÃO MODIFICAR sem consultar o documento FINANCIAL_RULES.md.
 * Alterações neste arquivo podem causar inconsistências financeiras em todo o sistema.
 */
class Transaction extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
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
        'notes',
        'cliente', // Adicionado para transações de receita
        'fornecedor', // Adicionado para transações de despesa
    ];

    /**
     * ATENÇÃO: CONFIGURAÇÃO CRÍTICA - NÃO MODIFICAR
     * 
     * Estes casts são essenciais para a correta manipulação de valores monetários.
     * amount como 'integer' garante que valores sejam armazenados como centavos.
     */
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
        $rules = $this->rules;
        if (( $data['type'] ?? null) === 'income') {
            $rules['cliente'] = 'nullable|string|max:255';
            $rules['fornecedor'] = 'prohibited';
        } elseif (( $data['type'] ?? null) === 'expense') {
            $rules['fornecedor'] = 'nullable|string|max:255';
            $rules['cliente'] = 'prohibited';
        } else {
            $rules['cliente'] = 'prohibited';
            $rules['fornecedor'] = 'prohibited';
        }
        return Validator::make($data, $rules);
    }

    /**
     * ATENÇÃO: CONFIGURAÇÃO CRÍTICA - NÃO MODIFICAR
     * 
     * Acessor para formatar o valor monetário de centavos para o formato com decimais.
     * Conversão: amount (centavos) / 100 = valor em reais formatado.
     */
    public function getFormattedAmountAttribute()
    {
        return 'R$ ' . number_format($this->amount / 100, 2, ',', '.');
    }

    // Mutator para garantir que o valor seja armazenado corretamente
    /**
     * ATENÇÃO: CONFIGURAÇÃO CRÍTICA - NÃO MODIFICAR
     * =======================================================
     * 1. TODOS OS VALORES FINANCEIROS SÃO ARMAZENADOS EM CENTAVOS
     * 2. EXEMPLO: R$ 400,00 = 40000 CENTAVOS (não 400)
     * 3. ESTA REGRA É FIXA E NÃO DEVE SER ALTERADA
     * 4. ALTERAÇÕES AQUI PODEM CAUSAR INCONSISTÊNCIA FINANCEIRA
     * 5. Ver FINANCIAL_RULES.md para mais detalhes
     */
    public function setAmountAttribute($value)
    {
        if (is_null($value)) {
            $this->attributes['amount'] = 0;
            return;
        }

        // Se o valor já for numérico (já em centavos), use-o diretamente
        if (is_numeric($value)) {
            \Log::info("Valor numérico recebido diretamente: {$value}");
            $this->attributes['amount'] = (int)$value;
            return;
        }

        if (is_string($value)) {
            // Remove todos os caracteres não numéricos, exceto vírgula e ponto
            $value = preg_replace('/[^0-9.,]/', '', $value);
            
            // Converte vírgula para ponto
            $value = str_replace(',', '.', $value);
            
            // Converte para float
            $value = (float)$value;
            
            // REGRA FIXA: Multiplica por 100 para armazenar em centavos
            // R$ 400,00 deve ser armazenado como 40000 centavos
            $value = round($value * 100);
        }

        \Log::info("Valor final para amount: {$value}");
        $this->attributes['amount'] = (int)$value;
    }

    // Adicione estes métodos auxiliares
    /**
     * ATENÇÃO: CONFIGURAÇÃO CRÍTICA - NÃO MODIFICAR
     * 
     * Verifica se a transação está com status 'paid'.
     * Transações com status 'paid' afetam o saldo da conta.
     * Ver FINANCIAL_RULES.md para mais detalhes.
     */
    public function isPaid()
    {
        return $this->status === 'paid';
    }

    /**
     * ATENÇÃO: CONFIGURAÇÃO CRÍTICA - NÃO MODIFICAR
     * 
     * Verifica se a transação está com status 'pending'.
     * Transações com status 'pending' NÃO afetam o saldo da conta.
     * Ver FINANCIAL_RULES.md para mais detalhes.
     */
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