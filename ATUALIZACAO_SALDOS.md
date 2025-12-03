# Sistema de Atualização Automática de Saldos

## Visão Geral

O sistema agora atualiza automaticamente os saldos das contas bancárias sempre que uma transação é criada, atualizada ou excluída. Isso garante que os saldos exibidos nos dashboards, relatórios e indicadores estejam sempre corretos e atualizados.

## Como Funciona

### 1. Atualização Automática via Trigger

Quando você:
- **Cria uma nova transação** (manual ou importada)
- **Atualiza uma transação existente**
- **Exclui uma transação**

O banco de dados automaticamente:
- Calcula o impacto no saldo da conta
- Atualiza o saldo da conta em tempo real
- Garante consistência dos dados

### 2. Lógica de Cálculo

**Receitas (income):**
- Adicionam valor ao saldo da conta
- Exemplo: Receita de R$ 1.000 → Saldo aumenta R$ 1.000

**Despesas (expense):**
- Subtraem valor do saldo da conta
- Exemplo: Despesa de R$ 500 → Saldo diminui R$ 500

### 3. Importação de Extratos

Ao importar extratos bancários:
1. As transações são criadas no banco de dados
2. O trigger automático atualiza os saldos
3. Uma recalculação adicional é executada para garantir precisão
4. Os dados são imediatamente refletidos em todos os dashboards

## Funcionalidades Disponíveis

### Recalcular Saldos Manualmente

Se você suspeitar de alguma inconsistência nos saldos, pode recalculá-los manualmente:

1. Acesse a página **Contas Bancárias**
2. Clique no botão **"Recalcular Saldos"** no canto superior direito
3. O sistema irá:
   - Somar todas as receitas de cada conta
   - Subtrair todas as despesas de cada conta
   - Atualizar o saldo com o valor correto
   - Exibir uma mensagem de confirmação

### Quando Usar a Recalculação Manual

- Após importar muitas transações de uma vez
- Se notar discrepâncias entre o saldo exibido e o esperado
- Após corrigir transações com valores incorretos
- Como verificação periódica de integridade dos dados

## Integração com Dashboards e Relatórios

Todos os componentes do sistema agora refletem os saldos atualizados:

### Dashboard Principal
- **Saldo Total**: Soma de todas as contas
- **Receitas do Mês**: Calculadas a partir das transações
- **Despesas do Mês**: Calculadas a partir das transações
- **Gráficos**: Baseados em dados de transações em tempo real

### Relatórios
- **Saldo por Conta**: Sempre atualizado
- **Despesas por Categoria**: Reflete todas as transações
- **Histórico Mensal**: Dados precisos de cada período
- **Projeção de Fluxo de Caixa**: Baseada em saldos reais

### Indicadores
- **Contas Ativas**: Mostra saldos corretos
- **Cartões de Crédito**: Limites disponíveis atualizados
- **Alertas**: Baseados em saldos reais

## Detalhes Técnicos

### Funções do Banco de Dados

#### `update_account_balance_on_transaction()`
- **Tipo**: Trigger automático
- **Execução**: Após INSERT, UPDATE ou DELETE em transações
- **Função**: Atualiza o saldo da conta automaticamente

#### `recalculate_account_balance(account_id)`
- **Tipo**: Função RPC
- **Parâmetros**: UUID da conta
- **Retorno**: Novo saldo calculado
- **Uso**: Recalcular saldo de uma conta específica

#### `recalculate_all_account_balances(user_id)`
- **Tipo**: Função RPC
- **Parâmetros**: UUID do usuário
- **Retorno**: Lista de contas com saldos antigos e novos
- **Uso**: Recalcular todas as contas de um usuário

### API Frontend

```typescript
// Recalcular saldo de uma conta específica
await accountsApi.recalculateAccountBalance(accountId);

// Recalcular todas as contas do usuário
const results = await accountsApi.recalculateAllAccountBalances(userId);
```

## Resolução de Problemas

### Problema: Saldo não está correto após importação

**Solução:**
1. Vá para **Contas Bancárias**
2. Clique em **"Recalcular Saldos"**
3. Verifique se o saldo foi atualizado corretamente

### Problema: Dashboard mostra valores diferentes da página de contas

**Solução:**
1. Recarregue a página (F5)
2. Se persistir, use **"Recalcular Saldos"**
3. Verifique se há transações duplicadas

### Problema: Transação foi excluída mas o saldo não mudou

**Solução:**
- O trigger deve atualizar automaticamente
- Se não atualizar, use **"Recalcular Saldos"**
- Verifique os logs do navegador para erros

## Boas Práticas

### Ao Importar Extratos
1. Selecione a conta correta antes de importar
2. Revise as transações antes de confirmar
3. Aguarde a mensagem de confirmação
4. Verifique o saldo atualizado na lista de contas

### Ao Criar Transações Manualmente
1. Certifique-se de selecionar a conta correta
2. Escolha o tipo correto (Receita ou Despesa)
3. Insira o valor exato
4. O saldo será atualizado automaticamente

### Manutenção Periódica
- Execute **"Recalcular Saldos"** mensalmente como verificação
- Revise transações antigas para garantir precisão
- Exporte relatórios regularmente para backup

## Segurança e Integridade

### Transações Atômicas
- Todas as atualizações de saldo são atômicas
- Se uma operação falhar, nenhuma mudança é aplicada
- Garante consistência dos dados

### Auditoria
- Todas as mudanças de saldo são rastreáveis
- Histórico de transações mantém registro completo
- Timestamps de criação e atualização são registrados

### Permissões
- Apenas o proprietário da conta pode modificar transações
- Funções RPC usam SECURITY DEFINER para segurança
- Validações impedem operações não autorizadas

## Migração Aplicada

**Arquivo**: `00003_add_balance_update_functions.sql`

Esta migração adiciona:
- Trigger automático para atualização de saldos
- Funções RPC para recalculação manual
- Comentários e documentação no banco de dados

## Suporte

Se você encontrar problemas com saldos ou tiver dúvidas:
1. Tente usar **"Recalcular Saldos"** primeiro
2. Verifique se todas as transações estão corretas
3. Revise este documento para soluções comuns
4. Entre em contato com o suporte técnico se necessário
