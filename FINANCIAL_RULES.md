# Regras Financeiras do Sistema Onlifin

## Cálculo de Saldos de Contas

### ATENÇÃO: CONFIGURAÇÃO CRÍTICA - NÃO ALTERAR SEM APROVAÇÃO EXPLÍCITA

Os cálculos de saldo das contas bancárias e a apresentação desses valores são componentes críticos do sistema financeiro. Modificações incorretas podem causar inconsistências nos dados financeiros e relatórios. As configurações implementadas devem ser mantidas para garantir a precisão dos dados financeiros em todo o sistema.

### Regras de Implementação

1. **Armazenamento de Valores Monetários**:
   - Os valores monetários são armazenados em centavos no banco de dados para evitar problemas de arredondamento
   - Transações em reais são convertidas para centavos antes de serem salvas
   - Exemplo: R$ 400,00 é armazenado como 40000 centavos

2. **Cálculo de Saldo de Contas**:
   - O saldo inicial (`initial_balance`) é definido na criação da conta
   - O saldo atual (`current_balance`) é calculado pela fórmula: `saldo_inicial + receitas_pagas - despesas_pagas`
   - **IMPORTANTE**: Apenas transações com status "paid" (pagas) afetam o saldo da conta
   - Transações pendentes ("pending") não devem ser consideradas no cálculo do saldo atual
   - Todas as contas devem ter o valor `current_balance` calculado e salvo no banco de dados
   - Se não houver transações pagas, o `current_balance` deve ser igual ao `initial_balance`

3. **Exibição de Valores Monetários**:
   - Valores em centavos são convertidos para o formato R$ X,XX antes da exibição
   - Saldos negativos são exibidos em vermelho
   - Saldo atual total no dashboard deve ser a soma dos saldos de todas as contas

4. **Eventos que Disparam Recálculo de Saldo**:
   - Criação de uma transação
   - Atualização de uma transação
   - Alteração do status de uma transação (de pendente para paga ou vice-versa)
   - Exclusão de uma transação
   - Alteração do saldo inicial de uma conta

## Arquivos com Configuração Crítica

Os seguintes arquivos contêm configurações críticas relacionadas ao cálculo de saldos que NÃO DEVEM ser modificadas sem aprovação:

- `app/Models/Account.php`: Implementação do cálculo de saldo
- `app/Http/Controllers/DashboardController.php`: Cálculo do saldo total para o dashboard
- `resources/views/dashboard.blade.php`: Exibição do saldo total
- `resources/views/accounts/index.blade.php`: Exibição dos saldos de contas
- `app/Observers/AccountObserver.php`: Lógica de atualização automática de saldos
- `app/Livewire/Transactions/Expenses.php`: Lógica dos filtros de transações de despesas
- `resources/views/livewire/transactions/expenses.blade.php`: Interface de filtros de despesas
- `app/Livewire/Transactions/Income.php`: Lógica dos filtros de transações de receitas
- `resources/views/livewire/transactions/income.blade.php`: Interface de filtros de receitas

### Implicações de Modificações Incorretas

Alterações nas regras de cálculo de saldo podem resultar em:

1. Discrepância entre saldo total e a soma dos saldos individuais
2. Erros de arredondamento em cálculos financeiros
3. Exibição incorreta de valores financeiros
4. Falhas no sistema com erros 500
5. Valores financeiros incorretos em relatórios

## Procedimento para Alterações

Se for absolutamente necessário modificar o comportamento relacionado aos cálculos de saldo:

1. Documente as alterações propostas detalhadamente
2. Obtenha aprovação formal dos stakeholders
3. Implemente as alterações em ambiente de teste
4. Valide extensivamente o comportamento, especialmente para contas com muitas transações
5. Valide a consistência dos dados financeiros após as alterações
6. Atualize esta documentação de regras financeiras 