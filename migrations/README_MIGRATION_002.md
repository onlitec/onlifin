# Sistema de Saldo de Contas Bancárias - OnliFin

## ✅ Migração Aplicada com Sucesso

A migração `002_fix_account_balance_system.sql` foi aplicada em **14/01/2026 às 05:22:11**

### 📋 O que foi implementado:

#### 1. **Saldo Inicial Separado**
- Nova coluna `initial_balance` na tabela `accounts`
- O saldo atual é calculado como: `saldo_inicial + receitas - despesas`
- Permite definir um ponto de partida para cada conta

#### 2. **Atualização Automática de Saldo**
O saldo da conta é atualizado automaticamente quando:
- ✅ Uma **transação de receita** é criada → saldo **aumenta**
- ✅ Uma **transação de despesa** é criada → saldo **diminui**
- ✅ Uma **conta a pagar** é marcada como "paga" → saldo **diminui**
- ✅ Uma **conta a receber** é marcada como "recebida" → saldo **aumenta**
- ✅ Uma transação é **excluída** → saldo é **revertido**
- ✅ Uma transferência é feita → origem **diminui**, destino **aumenta**

#### 3. **Integração Contas a Pagar/Receber**
- Quando você marca uma conta como paga/recebida, uma **transação é criada automaticamente**
- A transação fica vinculada à conta através do campo `transaction_id`
- Se você excluir a conta, a transação também é excluída

### 🧪 Teste Realizado

```
Saldo Inicial:     R$ 1.000,00
+ Receita:         R$   500,00
- Despesa:         R$   300,00
- Conta Paga:      R$   150,00
────────────────────────────────
= Saldo Final:     R$ 1.050,00 ✅
```

### 📊 Como Usar

#### Criar uma Nova Conta
1. Vá em **Contas Bancárias**
2. Clique em **Nova Conta**
3. Preencha:
   - Nome da conta
   - Banco
   - **Saldo Inicial** (importante!)
4. O saldo inicial será usado como base para os cálculos

#### Registrar Receitas
1. Vá em **Transações** → **Nova Transação**
2. Tipo: **Receita**
3. Escolha a **conta** onde o dinheiro entrará
4. O saldo da conta aumentará automaticamente

#### Registrar Despesas
1. Vá em **Transações** → **Nova Transação**
2. Tipo: **Despesa**
3. Escolha a **conta** de onde o dinheiro sairá
4. O saldo da conta diminuirá automaticamente

#### Usar Contas a Pagar
1. Vá em **Contas a Pagar** → **Nova Conta**
2. Defina a conta bancária que pagará
3. Quando marcar como "**Paga**":
   - Uma transação de despesa é criada automaticamente
   - O saldo da conta bancária diminui

#### Usar Contas a Receber
1. Vá em **Contas a Receber** → **Nova Conta**
2. Defina a conta bancária que receberá
3. Quando marcar como "**Recebida**":
   - Uma transação de receita é criada automaticamente
   - O saldo da conta bancária aumenta

### 🔧 Funções Criadas

| Função | Descrição |
|--------|-----------|
| `recalculate_account_balance(uuid)` | Recalcula o saldo de uma conta específica |
| `recalculate_all_account_balances(uuid)` | Recalcula todas as contas de um usuário |
| `update_account_balance_on_transaction()` | Trigger: atualiza saldo quando transação muda |
| `handle_bill_payment()` | Trigger: cria transação quando conta é paga |
| `handle_bill_receipt()` | Trigger: cria transação quando conta é recebida |

### 🛠️ Comandos Úteis

#### Verificar Saldos
```bash
docker exec onlifin-database psql -U onlifin -d onlifin -c \
  "SELECT id, name, balance, initial_balance FROM accounts;"
```

#### Recalcular Saldo de Uma Conta
```bash
docker exec onlifin-database psql -U onlifin -d onlifin -c \
  "SELECT recalculate_account_balance('ID-DA-CONTA-AQUI');"
```

#### Recalcular Todas as Contas de Um Usuário
```bash
docker exec onlifin-database psql -U onlifin -d onlifin -c \
  "SELECT * FROM recalculate_all_account_balances('ID-DO-USUARIO-AQUI');"
```

### 📁 Arquivos da Migração

- `migrations/002_fix_account_balance_system.sql` - Script SQL completo
- `apply-migration.sh` - Script automatizado de aplicação
- `backups/backup_20260114_052211.sql` - Backup antes da migração

### ⚠️ Restaurar Backup (se necessário)

Se algo der errado, você pode restaurar o backup:

```bash
cat backups/backup_20260114_052211.sql | \
  docker exec -i onlifin-database psql -U onlifin -d onlifin
```

### 📝 Triggers Instalados

Total: **8 triggers** ativos

| Tabela | Trigger | Evento |
|--------|---------|--------|
| `transactions` | `trigger_update_account_balance` | INSERT, UPDATE, DELETE |
| `accounts` | `trigger_update_balance_on_initial_balance_change` | UPDATE |
| `bills_to_pay` | `trigger_handle_bill_payment` | UPDATE |
| `bills_to_pay` | `trigger_delete_bill_to_pay_transaction` | DELETE |
| `bills_to_receive` | `trigger_handle_bill_receipt` | UPDATE |
| `bills_to_receive` | `trigger_delete_bill_to_receive_transaction` | DELETE |

### ✨ Status

- ✅ Migração aplicada com sucesso
- ✅ Backup criado
- ✅ Estrutura do banco verificada
- ✅ Triggers instalados e testados
- ✅ Funcionalidade testada e funcionando

---

**Data da Migração:** 14/01/2026 05:22:11  
**Versão:** 002  
**Status:** PRODUÇÃO
