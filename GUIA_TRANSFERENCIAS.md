# Guia de Uso: Sistema de Transferências

## O que são Transferências?

Transferências permitem que você mova dinheiro entre suas contas cadastradas na plataforma. Por exemplo:
- Transferir dinheiro da conta corrente para a poupança
- Mover fundos entre diferentes bancos
- Realocar recursos entre carteiras digitais

## Como Criar uma Transferência

### Passo 1: Acessar o Formulário
1. Vá para a página de **Transações**
2. Clique no botão **"Nova Transação"**

### Passo 2: Selecionar Tipo
1. No campo **"Tipo"**, selecione **"Transferência"**
2. A interface irá se adaptar automaticamente

### Passo 3: Preencher os Dados
Você verá os seguintes campos:

- **Conta de Origem** ⚠️ *Obrigatório*
  - Selecione a conta de onde o dinheiro sairá
  
- **Conta de Destino** ⚠️ *Obrigatório*
  - Selecione a conta para onde o dinheiro irá
  - Deve ser diferente da conta de origem
  
- **Valor** ⚠️ *Obrigatório*
  - Digite o valor a ser transferido
  - Use ponto ou vírgula para decimais (ex: 100,50)
  
- **Data** ⚠️ *Obrigatório*
  - Selecione a data da transferência
  
- **Descrição** *(Opcional)*
  - Adicione uma descrição para identificar a transferência
  - Exemplo: "Reserva para viagem", "Pagamento de aluguel"

### Passo 4: Confirmar
1. Clique em **"Criar"**
2. O sistema criará automaticamente:
   - Uma **despesa** na conta de origem
   - Uma **receita** na conta de destino
3. Os saldos das contas serão atualizados automaticamente

## Como Visualizar Transferências

### Na Lista de Transações
Transferências aparecem com:
- **Ícone**: ⇄ (setas bidirecionais)
- **Cor**: Azul (diferente de receitas e despesas)
- **Descrição**: "Transferência: [Conta Origem] → [Conta Destino]"
- **Valor**: Sem sinal de + ou -

### Exemplo de Visualização
```
⇄ Transferência para poupança
  Transferência: Conta Corrente → Poupança • 15/12/2025
  R$ 500,00
```

## Como Filtrar Transferências

1. Clique no botão **"Filtros"**
2. No campo **"Tipo"**, selecione **"Transferências"**
3. Apenas transferências serão exibidas

### Outros Filtros
Você também pode combinar com:
- **Conta Bancária**: Ver transferências de/para uma conta específica
- **Data**: Filtrar por período
- **Busca**: Procurar por descrição

## Como Excluir uma Transferência

1. Localize a transferência na lista
2. Clique no ícone de **lixeira** 🗑️
3. Confirme a exclusão
4. **Importante**: Ambas as transações (origem e destino) serão removidas
5. Os saldos das contas serão ajustados automaticamente

## ⚠️ Regras Importantes

### Não é Possível Editar
- Transferências **não podem ser editadas**
- Se precisar corrigir, você deve:
  1. Excluir a transferência incorreta
  2. Criar uma nova transferência com os dados corretos

### Contas Devem Ser Diferentes
- Você não pode transferir de uma conta para ela mesma
- O sistema bloqueará tentativas de fazer isso

### Campos Não Disponíveis
Para transferências, os seguintes campos não estão disponíveis:
- ❌ Categoria (transferências não têm categoria)
- ❌ Cartão de crédito
- ❌ Transação recorrente
- ❌ Parcelamento

### Impacto nos Saldos
- A conta de **origem** terá o saldo **reduzido**
- A conta de **destino** terá o saldo **aumentado**
- O valor total entre todas as contas permanece o mesmo

## Exemplos de Uso

### Exemplo 1: Reserva de Emergência
```
Tipo: Transferência
Conta de Origem: Conta Corrente
Conta de Destino: Poupança
Valor: R$ 1.000,00
Data: 01/12/2025
Descrição: Reserva de emergência mensal
```

### Exemplo 2: Pagamento de Aluguel
```
Tipo: Transferência
Conta de Origem: Conta Corrente
Conta de Destino: Conta Investimentos
Valor: R$ 2.500,00
Data: 05/12/2025
Descrição: Separar valor do aluguel
```

### Exemplo 3: Consolidação de Contas
```
Tipo: Transferência
Conta de Origem: Banco A
Conta de Destino: Banco B
Valor: R$ 5.000,00
Data: 10/12/2025
Descrição: Consolidação de contas
```

## Dicas e Boas Práticas

### 📝 Use Descrições Claras
- Adicione descrições que ajudem a identificar o propósito
- Exemplos: "Reserva viagem", "Fundo emergência", "Investimento mensal"

### 📅 Registre na Data Correta
- Use a data real da transferência
- Isso ajuda no controle financeiro e relatórios

### 🔍 Revise Antes de Confirmar
- Verifique se selecionou as contas corretas
- Confirme o valor antes de criar
- Lembre-se: não é possível editar depois

### 📊 Use Filtros para Análise
- Filtre por período para ver transferências mensais
- Filtre por conta para ver movimentações específicas
- Use a busca para encontrar transferências por descrição

### 🗂️ Organize Suas Contas
- Mantenha suas contas bem nomeadas
- Isso facilita identificar origem e destino nas transferências
- Exemplo: "Conta Corrente - Banco X", "Poupança - Banco Y"

## Perguntas Frequentes

### Por que não posso editar uma transferência?
Transferências criam duas transações vinculadas. Editar uma sem a outra causaria inconsistências nos saldos. Por isso, é necessário excluir e recriar.

### O que acontece se eu excluir apenas uma das transações?
O sistema garante que ambas as transações sejam excluídas juntas. Não é possível excluir apenas uma.

### Posso fazer transferências recorrentes?
Atualmente não. Você precisa criar cada transferência manualmente. Esta funcionalidade pode ser adicionada no futuro.

### As transferências aparecem nos relatórios?
Sim, transferências são incluídas nos relatórios financeiros e podem ser filtradas separadamente.

### Posso transferir para contas de outros usuários?
Não. Transferências só funcionam entre contas do mesmo usuário na plataforma.

## Suporte

Se você encontrar problemas ou tiver dúvidas:
1. Verifique se preencheu todos os campos obrigatórios
2. Confirme que as contas de origem e destino são diferentes
3. Verifique se o valor é positivo
4. Entre em contato com o suporte se o problema persistir

---

**Última atualização**: Dezembro 2025
**Versão**: 1.0
