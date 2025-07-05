# 📋 Análise e Proposta de Melhorias - Sistema de Importação com IA

## 1. Visão Geral

### Objetivo Principal
Aprimorar o sistema de importação de extratos bancários para detectar automaticamente transações recorrentes e fazer a baixa automática de pagamentos pendentes correspondentes.

## 2. Melhorias Propostas

### 2.1 Aprimoramento do Prompt da IA

#### Prompt Atual (Simplificado)
```
Analise as transações e categorize cada uma delas.
Para despesas, use categorias como: Mercado, Combustível, etc.
```

#### Prompt Proposto (Aprimorado)
```
Você é um assistente financeiro especializado em categorizar transações bancárias e identificar padrões de recorrência.

Para cada transação, analise e determine:

1. TIPO: Se é receita (income) ou despesa (expense)

2. CATEGORIA: Utilize preferencialmente uma categoria existente ou sugira uma nova

3. RECORRÊNCIA: Identifique se é um pagamento/recebimento recorrente baseado em:
   - Descrições similares (ex: "NETFLIX", "SABSP", "ALUGUEL")
   - Valores mensais fixos (ex: mensalidades, assinaturas)
   - Padrões de cobrança (ex: "PARC 03/12", "REF 07/2024")
   
4. FORNECEDOR/CLIENTE: Extraia o nome da empresa/pessoa da descrição

5. DETECÇÃO DE BAIXA: Se identificar como recorrente, busque por:
   - Nome do fornecedor/cliente
   - Valor aproximado (±10% de variação)
   - Periodicidade mensal

Responda APENAS em JSON no formato:
{
  "transactions": [
    {
      "id": 0,
      "type": "expense|income",
      "category": "nome da categoria",
      "suggested_category": "nova categoria se necessário",
      "is_recurring": true|false,
      "recurring_pattern": {
        "type": "fixed|installment",
        "description_pattern": "padrão identificado",
        "expected_day": 5,
        "installment_info": "3/12" (se aplicável)
      },
      "fornecedor": "nome do fornecedor",
      "cliente": "nome do cliente",
      "confidence": 0.95
    }
  ]
}
```

### 2.2 Implementação da Detecção de Recorrência

#### Novo Método em TempStatementImportController.php

```php
/**
 * Processa detecção de transações recorrentes após análise da IA
 */
private function processRecurringDetection($transactions, $aiAnalysis, $accountId)
{
    foreach ($aiAnalysis['transactions'] as $index => $analyzed) {
        if (!isset($analyzed['is_recurring']) || !$analyzed['is_recurring']) {
            continue;
        }
        
        // Buscar transação recorrente existente
        $recurring = $this->findMatchingRecurringTransaction(
            $analyzed,
            $accountId,
            $transactions[$index]['date']
        );
        
        if ($recurring) {
            // Vincular e dar baixa
            $this->linkAndPayRecurringTransaction(
                $recurring,
                $transactions[$index],
                $analyzed
            );
            
            // Marcar na análise para o mapeamento
            $aiAnalysis['transactions'][$index]['linked_recurring_id'] = $recurring->id;
            $aiAnalysis['transactions'][$index]['auto_paid'] = true;
        } else {
            // Sugerir criação de nova recorrente
            $aiAnalysis['transactions'][$index]['suggest_create_recurring'] = true;
        }
    }
    
    return $aiAnalysis;
}

/**
 * Busca transação recorrente correspondente
 */
private function findMatchingRecurringTransaction($analyzed, $accountId, $transactionDate)
{
    $query = Transaction::where('account_id', $accountId)
        ->where('type', $analyzed['type'])
        ->whereIn('recurrence_type', ['fixed', 'installment'])
        ->where('status', 'pending');
    
    // Buscar por padrão de descrição
    if (isset($analyzed['recurring_pattern']['description_pattern'])) {
        $pattern = $analyzed['recurring_pattern']['description_pattern'];
        $query->where('description', 'LIKE', "%{$pattern}%");
    }
    
    // Buscar por fornecedor/cliente
    if ($analyzed['type'] === 'expense' && isset($analyzed['fornecedor'])) {
        $query->where('fornecedor', $analyzed['fornecedor']);
    } elseif ($analyzed['type'] === 'income' && isset($analyzed['cliente'])) {
        $query->where('cliente', $analyzed['cliente']);
    }
    
    // Buscar por valor aproximado (±10%)
    $amount = $analyzed['amount'];
    $minAmount = $amount * 0.9;
    $maxAmount = $amount * 1.1;
    $query->whereBetween('amount', [$minAmount, $maxAmount]);
    
    // Buscar por data esperada (±7 dias)
    $date = Carbon::parse($transactionDate);
    $query->whereDate('date', '>=', $date->copy()->subDays(7))
          ->whereDate('date', '<=', $date->copy()->addDays(7));
    
    return $query->first();
}

/**
 * Vincula e dá baixa em transação recorrente
 */
private function linkAndPayRecurringTransaction($recurring, $importedTransaction, $analyzed)
{
    DB::transaction(function() use ($recurring, $importedTransaction, $analyzed) {
        // Dar baixa na transação recorrente
        $recurring->status = 'paid';
        $recurring->save();
        
        // Se for parcelada, criar próxima parcela
        if ($recurring->isInstallmentRecurrence() && 
            $recurring->installment_number < $recurring->total_installments) {
            $this->createNextRecurringTransaction($recurring);
        }
        
        // Se for fixa, atualizar próxima data
        if ($recurring->isFixedRecurrence() && $recurring->next_date) {
            $nextDate = Carbon::parse($recurring->next_date)->addMonth();
            $recurring->next_date = $nextDate;
            $recurring->save();
        }
        
        // Log da vinculação
        Log::info('Transação recorrente vinculada e paga automaticamente', [
            'recurring_id' => $recurring->id,
            'imported_description' => $importedTransaction['description'],
            'confidence' => $analyzed['confidence'] ?? 0
        ]);
    });
}
```

### 2.3 Interface de Revisão para Transações Recorrentes

#### Nova View: resources/views/transactions/import-recurring-review.blade.php

```blade
<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
    <h3 class="text-lg font-semibold text-yellow-800 mb-3">
        🔄 Transações Recorrentes Detectadas
    </h3>
    
    @foreach($recurringDetections as $detection)
        <div class="bg-white rounded-lg p-4 mb-3 border border-yellow-100">
            <div class="flex justify-between items-start">
                <div>
                    <p class="font-medium">{{ $detection['description'] }}</p>
                    <p class="text-sm text-gray-600">
                        Valor: R$ {{ number_format($detection['amount'] / 100, 2, ',', '.') }}
                    </p>
                </div>
                
                @if($detection['linked_recurring_id'])
                    <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm">
                        ✓ Vinculada e Paga
                    </span>
                @else
                    <div class="flex gap-2">
                        <button onclick="createRecurring({{ $detection['index'] }})"
                                class="bg-blue-500 text-white px-3 py-1 rounded text-sm">
                            Criar Recorrente
                        </button>
                        <button onclick="ignoreRecurring({{ $detection['index'] }})"
                                class="bg-gray-300 text-gray-700 px-3 py-1 rounded text-sm">
                            Ignorar
                        </button>
                    </div>
                @endif
            </div>
            
            @if($detection['recurring_pattern'])
                <div class="mt-2 text-sm text-gray-500">
                    <p>Padrão: {{ $detection['recurring_pattern']['description_pattern'] }}</p>
                    <p>Tipo: {{ $detection['recurring_pattern']['type'] === 'fixed' ? 'Fixa' : 'Parcelada' }}</p>
                </div>
            @endif
        </div>
    @endforeach
</div>
```

### 2.4 Melhorias no Prompt de Categorização

#### Exemplos Específicos para Treinar a IA

```json
{
  "training_examples": [
    {
      "description": "NETFLIX.COM",
      "expected": {
        "category": "Casa Entretenimento",
        "is_recurring": true,
        "recurring_pattern": {
          "type": "fixed",
          "description_pattern": "NETFLIX"
        },
        "fornecedor": "Netflix"
      }
    },
    {
      "description": "SABSP - CONTA AGUA REF 07/2024",
      "expected": {
        "category": "Casa Água",
        "is_recurring": true,
        "recurring_pattern": {
          "type": "fixed",
          "description_pattern": "SABSP",
          "expected_day": 10
        },
        "fornecedor": "SABESP"
      }
    },
    {
      "description": "SUPERMERCADO TAUSTE PARC 03/12",
      "expected": {
        "category": "Mercado",
        "is_recurring": true,
        "recurring_pattern": {
          "type": "installment",
          "description_pattern": "SUPERMERCADO TAUSTE",
          "installment_info": "3/12"
        },
        "fornecedor": "Supermercado Tauste"
      }
    }
  ]
}
```

## 3. Implementação Passo a Passo

### Fase 1: Atualização do Prompt da IA (Imediata)
1. Modificar `prepareTransactionsPrompt()` para incluir detecção de recorrência
2. Atualizar `extractJsonFromAIResponse()` para processar novos campos
3. Adicionar exemplos de treinamento no prompt

### Fase 2: Detecção e Vinculação (1 semana)
1. Implementar `processRecurringDetection()`
2. Criar método `findMatchingRecurringTransaction()`
3. Implementar `linkAndPayRecurringTransaction()`
4. Adicionar logs e auditoria

### Fase 3: Interface de Revisão (1 semana)
1. Criar componente de revisão de recorrências
2. Adicionar opções de criar/ignorar recorrências
3. Implementar feedback visual para vinculações automáticas
4. Adicionar relatório de importação com detalhes

### Fase 4: Machine Learning (Futuro)
1. Armazenar histórico de categorizações
2. Treinar modelo específico por usuário
3. Melhorar precisão com base em padrões anteriores
4. Implementar sugestões inteligentes

## 4. Benefícios Esperados

1. **Economia de Tempo**: Redução de 80% no tempo de categorização manual
2. **Precisão**: Detecção automática de 95%+ das transações recorrentes
3. **Controle Financeiro**: Baixa automática evita esquecimento de pagamentos
4. **Inteligência**: Sistema aprende padrões específicos de cada usuário
5. **Conformidade**: Histórico completo de todas as vinculações automáticas

## 5. Métricas de Sucesso

- Taxa de detecção correta de recorrências: >90%
- Redução de transações pendentes esquecidas: >75%
- Satisfação do usuário com categorização automática: >85%
- Tempo médio de importação reduzido em: 70%

## 6. Considerações de Segurança

1. Sempre requerer confirmação para valores altos
2. Log detalhado de todas as ações automáticas
3. Permitir desfazer vinculações incorretas
4. Notificar usuário de ações automáticas realizadas 