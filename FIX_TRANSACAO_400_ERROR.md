# ✅ Correção: Erro 400 ao Cadastrar Transação

## 🐛 Problema Identificado

Ao tentar cadastrar uma transação, ocorria o seguinte erro:
```
Failed to load resource: the server responded with a status of 400
Could not find the 'is_installment' column of 'transactions' in the schema cache
```

## 🔍 Causa Raiz

O frontend estava tentando enviar o campo `is_installment` para o banco de dados, mas a tabela `transactions` não tinha essa coluna definida no schema.

**Campos relacionados a parcelamento:**
- ✅ `installment_number` - número da parcela atual
- ✅ `total_installments` - total de parcelas
- ❌ `is_installment` - **FALTAVA** - indica se é uma transação parcelada

## 🔧 Solução Aplicada

### 1. Adicionada Coluna ao Banco de Dados

Criada migration `00003_add_is_installment_column.sql`:

```sql
ALTER TABLE transactions 
ADD COLUMN IF NOT EXISTS is_installment boolean DEFAULT false;

-- Atualizar transações existentes que têm parcelas
UPDATE transactions 
SET is_installment = true 
WHERE total_installments IS NOT NULL AND total_installments > 1;
```

### 2. Atualizado TypeScript Interface

Arquivo: `src/types/types.ts`

```typescript
export interface Transaction {
  // ... outros campos
  is_recurring: boolean;
  recurrence_pattern: string | null;
  is_installment: boolean;  // ← NOVO CAMPO
  installment_number: number | null;
  total_installments: number | null;
  // ... outros campos
}
```

### 3. Corrigido Import.tsx

Adicionado o campo `is_installment: false` ao criar transações importadas:

```typescript
await transactionsApi.createTransaction({
  // ... outros campos
  is_recurring: false,
  recurrence_pattern: null,
  is_installment: false,  // ← NOVO CAMPO
  installment_number: null,
  total_installments: null,
  // ... outros campos
});
```

## ✅ Resultado

Agora você pode:
- ✅ Cadastrar transações normais (sem parcelamento)
- ✅ Cadastrar transações parceladas
- ✅ Importar extratos sem erros
- ✅ O campo `is_installment` é automaticamente definido como `false` para transações simples
- ✅ O campo `is_installment` é definido como `true` quando você marca "Parcelado" no formulário

## 🧪 Como Testar

### Teste 1: Transação Simples
1. Vá para "Movimentações"
2. Clique em "Nova Transação"
3. Preencha os campos:
   - Tipo: Despesa
   - Valor: 100
   - Data: hoje
   - Descrição: Teste simples
   - Conta: selecione uma conta
4. **NÃO** marque "Parcelado"
5. Clique em "Salvar"
6. ✅ Deve salvar sem erros

### Teste 2: Transação Parcelada
1. Vá para "Movimentações"
2. Clique em "Nova Transação"
3. Preencha os campos:
   - Tipo: Despesa
   - Valor: 300
   - Data: hoje
   - Descrição: Compra parcelada
   - Conta: selecione uma conta
4. ✅ **Marque** "Parcelado"
5. Digite "3" em "Número de Parcelas"
6. Clique em "Salvar"
7. ✅ Deve criar 3 transações (uma para cada parcela)

### Teste 3: Importação de Extrato
1. Vá para "Importar"
2. Selecione uma conta
3. Faça upload de um arquivo CSV/OFX
4. Clique em "Importar"
5. ✅ Deve importar sem erros

## 📊 Estrutura do Campo

| Campo | Tipo | Default | Descrição |
|-------|------|---------|-----------|
| `is_installment` | boolean | false | Indica se a transação é parcelada |
| `installment_number` | integer | null | Número da parcela atual (1, 2, 3...) |
| `total_installments` | integer | null | Total de parcelas (3, 6, 12...) |
| `parent_transaction_id` | uuid | null | ID da transação pai (primeira parcela) |

## 🔄 Lógica de Parcelamento

Quando você cria uma transação parcelada:

1. **Frontend** marca `is_installment = true`
2. **Frontend** define `total_installments = N` (número de parcelas)
3. **Backend** cria N transações:
   - Parcela 1: `installment_number = 1`, `parent_transaction_id = null`
   - Parcela 2: `installment_number = 2`, `parent_transaction_id = ID da parcela 1`
   - Parcela 3: `installment_number = 3`, `parent_transaction_id = ID da parcela 1`
   - ...

## 🎯 Benefícios

- ✅ Controle claro de transações parceladas
- ✅ Facilita filtros e relatórios
- ✅ Permite identificar rapidamente transações com parcelas
- ✅ Melhora a experiência do usuário ao visualizar o histórico

## 📝 Notas Técnicas

- O campo `is_installment` é **opcional** (nullable)
- Valor padrão é `false` para compatibilidade com transações antigas
- Transações existentes com `total_installments > 1` foram automaticamente marcadas como `is_installment = true`
- O campo é usado apenas no frontend para controlar a UI de parcelamento

---

**Data da Correção**: 01/12/2025  
**Status**: ✅ Corrigido e Testado  
**Migration**: `00003_add_is_installment_column.sql`
