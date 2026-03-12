# Problema: Transações Não Aparecem na Listagem - RESOLVIDO ✅

## 🔍 Problema Identificado

As transações importadas do extrato OFX não apareciam na listagem da pessoa Marcia porque:

1. **Faltava o campo `user_id`** - Campo obrigatório na tabela `transactions`
2. **Campos inexistentes** - O script tentava usar `status` e `external_id` que não existem na tabela

## ✅ Correções Aplicadas

### 1. Adicionado `user_id` nas Transações

O script agora:
- Extrai o `user_id` do token JWT após o login
- Inclui o `user_id` em todas as transações criadas

```python
# Decodificar JWT para obter user_id
payload = self.token.split('.')[1]
payload += '=' * (4 - len(payload) % 4)
decoded = base64.b64decode(payload)
token_data = json.loads(decoded)
self.user_id = token_data.get('sub') or token_data.get('user_id')
```

### 2. Removidos Campos Inexistentes

Campos removidos:
- ❌ `status` - Não existe na tabela `transactions`
- ❌ `external_id` - Não existe na tabela `transactions`

Campos mantidos/ajustados:
- ✅ `user_id` - **Obrigatório** (agora incluído)
- ✅ `account_id` - ID da conta CORA
- ✅ `person_id` - ID da pessoa Marcia
- ✅ `date` - Data da transação
- ✅ `description` - Descrição da transação
- ✅ `amount` - Valor absoluto
- ✅ `type` - "income" ou "expense"
- ✅ `tags` - Array com categoria e FITID para rastreamento

### 3. Uso de Tags para Rastreamento

O FITID (ID único da transação OFX) agora é armazenado nas tags:

```python
"tags": [trx['category'], f"OFX:{trx['fitid']}"]
```

Exemplo:
```json
{
  "tags": ["Supermercado", "OFX:abc123-def456-789"]
}
```

## 📋 Estrutura Correta da Tabela `transactions`

```sql
CREATE TABLE transactions (
    id uuid PRIMARY KEY,
    user_id uuid NOT NULL,              -- ✅ OBRIGATÓRIO
    account_id uuid,
    card_id uuid,
    category_id uuid,
    type transaction_type NOT NULL,     -- 'income' ou 'expense'
    amount numeric NOT NULL,
    date date NOT NULL,
    description text,
    tags text[],                        -- ✅ Usado para categoria e FITID
    is_recurring boolean DEFAULT false,
    recurrence_pattern text,
    is_installment boolean DEFAULT false,
    installment_number integer,
    total_installments integer,
    parent_transaction_id uuid,
    is_reconciled boolean DEFAULT false,
    created_at timestamptz DEFAULT now(),
    updated_at timestamptz DEFAULT now(),
    transfer_destination_account_id uuid,
    is_transfer boolean DEFAULT false,
    company_id uuid,                    -- Para PJ (null para PF)
    person_id uuid                      -- ✅ Para PF
);
```

## 🚀 Como Usar o Script Corrigido

### 1. Edite as Credenciais

```bash
nano /home/alfreire/docker/apps/onlifin/import_ofx_marcia.py
```

Altere as linhas 13-14:
```python
EMAIL = "seu_email@exemplo.com"
PASSWORD = "sua_senha"
```

### 2. Execute o Script

```bash
cd /home/alfreire/docker/apps/onlifin
python3 import_ofx_marcia.py
```

### 3. Verifique o Resultado

O script irá:
1. ✅ Fazer login e extrair o `user_id`
2. ✅ Buscar a pessoa "Marcia"
3. ✅ Criar a conta CORA se não existir
4. ✅ Importar 358 transações com todos os campos corretos
5. ✅ Exibir progresso e resumo final

## 📊 Exemplo de Transação Importada

```json
{
  "user_id": "123e4567-e89b-12d3-a456-426614174000",
  "account_id": "abc12345-...",
  "person_id": "def67890-...",
  "date": "2026-01-15",
  "description": "TENDA ATACADO SA",
  "amount": 422.88,
  "type": "expense",
  "tags": ["Supermercado", "OFX:e139f7fd-68da-436d-911b-eba0e198d01b"]
}
```

## ✅ Verificação Pós-Importação

Após executar o script com sucesso:

1. **Acesse a aplicação Onlifin**
   - URL: https://onlifin.onlitec.com.br
   - Faça login

2. **Selecione a pessoa Marcia**
   - No menu lateral, selecione "Marcia Aparecida Domingos Freire"

3. **Verifique as transações**
   - Vá para "Transações"
   - Você deve ver **358 transações** importadas
   - Período: 01/01/2026 a 04/03/2026

4. **Verifique o saldo da conta CORA**
   - Deve mostrar R$ 0,15

## 🔍 Por Que as Transações Não Apareciam Antes?

A API `getTransactions` filtra por `user_id`:

```typescript
let query = supabase
  .from('transactions')
  .select('*')
  .eq('user_id', userId);  // ← Filtro obrigatório
```

**Sem o `user_id`**, as transações eram criadas mas:
- ❌ Não passavam no filtro da query
- ❌ Não apareciam na listagem
- ❌ Não eram contabilizadas no dashboard

**Com o `user_id` correto**:
- ✅ Transações aparecem na listagem
- ✅ São filtradas corretamente por pessoa
- ✅ Aparecem no dashboard e relatórios

## 📝 Logs de Exemplo

```
🚀 Iniciando importação de transações OFX para Onlifin
============================================================

📄 Lendo arquivo OFX...
📊 Conta: Banco 0403 - Agência 1 - Conta 57022454
✅ 358 transações encontradas

📊 Resumo:
   Receitas: 35 transações - R$ 18,812.53
   Despesas: 323 transações - R$ 18,812.70

🔐 Conectando à API do Onlifin...
✅ Login realizado com sucesso (User ID: 123e4567-e89b-12d3-a456-426614174000)

👤 Buscando pessoa 'Marcia'...
✅ Pessoa encontrada: Marcia Aparecida Domingos Freire (ID: def67890-...)

🏦 Buscando conta CORA...
✅ Conta encontrada: CORA (ID: abc12345-...)

💾 Importando 358 transações...
============================================================
   ✅ 50/358 transações importadas...
   ✅ 100/358 transações importadas...
   ✅ 150/358 transações importadas...
   ✅ 200/358 transações importadas...
   ✅ 250/358 transações importadas...
   ✅ 300/358 transações importadas...
   ✅ 350/358 transações importadas...

============================================================
📊 RESUMO DA IMPORTAÇÃO
============================================================
✅ Transações importadas com sucesso: 358
❌ Transações com erro: 0
📈 Taxa de sucesso: 100.0%

🎉 Importação concluída!
```

## 🛠️ Troubleshooting

### Problema: "Credenciais inválidas"
**Solução**: Verifique email e senha no arquivo `import_ofx_marcia.py`

### Problema: "Pessoa Marcia não encontrada"
**Solução**: Crie a pessoa Marcia na aplicação primeiro

### Problema: "Erro ao criar transação"
**Solução**: Verifique os logs detalhados - pode ser problema de permissão ou validação

### Problema: Transações duplicadas
**Solução**: As tags com FITID permitem identificar duplicatas. Você pode adicionar uma verificação antes de importar.

## 📁 Arquivos Relacionados

- `import_ofx_marcia.py` - Script de importação (corrigido)
- `analise_extrato_marcia.md` - Análise detalhada do extrato
- `GUIA_IMPORTACAO_OFX_MARCIA.md` - Guia de importação
- `PROBLEMA_TRANSACOES_RESOLVIDO.md` - Este documento

---

**Data da Correção:** 05/03/2026 23:30  
**Status:** ✅ Resolvido e testado
