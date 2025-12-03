# Guia: Editar e Excluir Transações

## 📝 Visão Geral

Agora você pode editar e excluir transações diretamente da página de Transações. Isso permite corrigir erros, atualizar informações e manter seus registros financeiros sempre precisos.

## ✏️ Como Editar uma Transação

### Passo a Passo

1. **Acesse a página de Transações**
   - No menu lateral, clique em "Transações"

2. **Localize a transação que deseja editar**
   - Role a lista de transações
   - Encontre a transação que precisa ser modificada

3. **Clique no ícone de lápis (✏️)**
   - Localizado no lado direito de cada transação
   - Ao passar o mouse, aparece "Editar transação"

4. **Modifique os dados desejados**
   - **Tipo**: Altere entre Receita e Despesa
   - **Valor**: Atualize o montante
   - **Data**: Mude a data da transação
   - **Descrição**: Edite a descrição
   - **Categoria**: Selecione outra categoria
   - **Conta**: Altere a conta associada
   - **Recorrência**: Ative/desative ou mude a frequência

5. **Clique em "Atualizar"**
   - A transação será atualizada
   - O saldo da conta será recalculado automaticamente
   - Uma mensagem de sucesso será exibida

### O Que Você Pode Editar

✅ **Tipo da transação** (Receita ↔ Despesa)
✅ **Valor** (qualquer montante)
✅ **Data** (qualquer data)
✅ **Descrição** (texto livre)
✅ **Categoria** (qualquer categoria do tipo correspondente)
✅ **Conta** (qualquer conta cadastrada)
✅ **Cartão** (se aplicável)
✅ **Recorrência** (ativar/desativar e frequência)

### O Que NÃO Aparece na Edição

❌ **Opção de parcelar**: Não é possível criar parcelas ao editar
- As parcelas só podem ser criadas ao criar uma nova transação
- Se precisar parcelar, crie uma nova transação parcelada

## 🗑️ Como Excluir uma Transação

### Passo a Passo

1. **Acesse a página de Transações**
   - No menu lateral, clique em "Transações"

2. **Localize a transação que deseja excluir**
   - Role a lista de transações
   - Encontre a transação que precisa ser removida

3. **Clique no ícone de lixeira (🗑️)**
   - Localizado no lado direito de cada transação
   - Ao passar o mouse, aparece "Excluir transação"

4. **Confirme a exclusão**
   - Uma janela de confirmação será exibida
   - Pergunta: "Tem certeza que deseja excluir esta transação?"
   - Clique em "OK" para confirmar ou "Cancelar" para desistir

5. **Transação excluída**
   - A transação será removida permanentemente
   - O saldo da conta será recalculado automaticamente
   - Uma mensagem de sucesso será exibida

### ⚠️ Atenção ao Excluir

- **A exclusão é permanente**: Não é possível desfazer
- **O saldo será ajustado**: A conta terá o saldo recalculado automaticamente
- **Confirme sempre**: Certifique-se de que está excluindo a transação correta

## 🔄 Atualização Automática de Saldos

### Como Funciona

Quando você edita ou exclui uma transação, o sistema automaticamente:

1. **Reverte o impacto da transação antiga** (se editando)
2. **Aplica o impacto da transação nova** (se editando)
3. **Remove o impacto da transação** (se excluindo)
4. **Atualiza o saldo da conta** em tempo real
5. **Reflete as mudanças** em todos os dashboards e relatórios

### Exemplos Práticos

#### Exemplo 1: Editar Valor de Despesa

**Situação:**
- Conta tinha saldo de R$ 1.000,00
- Transação original: Despesa de R$ 100,00
- Você edita para: Despesa de R$ 150,00

**Resultado:**
- Sistema reverte a despesa de R$ 100,00 → Saldo fica R$ 1.100,00
- Sistema aplica a despesa de R$ 150,00 → Saldo final R$ 950,00
- Diferença: R$ 50,00 a menos no saldo

#### Exemplo 2: Mudar Tipo de Transação

**Situação:**
- Conta tinha saldo de R$ 1.000,00
- Transação original: Despesa de R$ 200,00 (criada por engano)
- Você edita para: Receita de R$ 200,00 (tipo correto)

**Resultado:**
- Sistema reverte a despesa de R$ 200,00 → Saldo fica R$ 1.200,00
- Sistema aplica a receita de R$ 200,00 → Saldo final R$ 1.400,00
- Diferença: R$ 400,00 a mais no saldo (200 + 200)

#### Exemplo 3: Excluir Transação

**Situação:**
- Conta tinha saldo de R$ 1.000,00
- Transação: Despesa de R$ 300,00 (duplicada)
- Você exclui a transação

**Resultado:**
- Sistema remove a despesa de R$ 300,00
- Saldo final: R$ 1.300,00
- Diferença: R$ 300,00 a mais no saldo

## 💡 Casos de Uso Comuns

### 1. Corrigir Valor Digitado Errado

**Problema:** Você digitou R$ 1.500,00 mas era R$ 150,00

**Solução:**
1. Clique no ícone de lápis da transação
2. Altere o valor de 1500 para 150
3. Clique em "Atualizar"
4. ✅ Saldo corrigido automaticamente

### 2. Mudar Categoria Incorreta

**Problema:** Você categorizou uma compra de supermercado como "Transporte"

**Solução:**
1. Clique no ícone de lápis da transação
2. Selecione a categoria correta "Alimentação"
3. Clique em "Atualizar"
4. ✅ Relatórios de categoria atualizados

### 3. Corrigir Data Errada

**Problema:** Você registrou uma compra do dia 15 como dia 5

**Solução:**
1. Clique no ícone de lápis da transação
2. Altere a data de 05/12 para 15/12
3. Clique em "Atualizar"
4. ✅ Histórico mensal corrigido

### 4. Remover Transação Duplicada

**Problema:** Você importou um extrato e criou uma transação manual duplicada

**Solução:**
1. Identifique a transação duplicada
2. Clique no ícone de lixeira
3. Confirme a exclusão
4. ✅ Saldo corrigido, sem duplicação

### 5. Mudar Tipo de Transação

**Problema:** Você registrou uma receita como despesa por engano

**Solução:**
1. Clique no ícone de lápis da transação
2. Mude o tipo de "Despesa" para "Receita"
3. Ajuste a categoria se necessário (categorias mudam conforme o tipo)
4. Clique em "Atualizar"
5. ✅ Saldo corrigido com o tipo certo

## 🎯 Boas Práticas

### Ao Editar Transações

✅ **Revise antes de salvar**: Confira todos os campos antes de clicar em "Atualizar"
✅ **Verifique a conta**: Certifique-se de que a conta está correta
✅ **Confira a data**: Datas incorretas afetam relatórios mensais
✅ **Escolha a categoria certa**: Facilita análises futuras
✅ **Adicione descrições claras**: Ajuda a identificar a transação depois

### Ao Excluir Transações

✅ **Confirme a transação**: Certifique-se de que está excluindo a correta
✅ **Verifique se é duplicada**: Antes de excluir, confirme que é realmente duplicada
✅ **Considere editar ao invés de excluir**: Se só precisa corrigir dados, edite
✅ **Anote o motivo**: Se for importante, anote por que excluiu (para referência futura)

### Manutenção Regular

📅 **Revise semanalmente**: Verifique se todas as transações estão corretas
🔍 **Procure duplicações**: Especialmente após importar extratos
📊 **Confira os relatórios**: Use os relatórios para identificar inconsistências
💰 **Compare com extratos reais**: Valide seus saldos com os extratos bancários

## 🔍 Verificação Após Edição/Exclusão

Após editar ou excluir uma transação, verifique:

### 1. Saldo da Conta
- [ ] Acesse a página "Contas Bancárias"
- [ ] Verifique se o saldo está correto
- [ ] Compare com seu extrato bancário real

### 2. Dashboard
- [ ] Acesse o "Dashboard"
- [ ] Confira se o "Saldo Total" está correto
- [ ] Verifique "Receitas do Mês" e "Despesas do Mês"

### 3. Relatórios
- [ ] Acesse "Relatórios"
- [ ] Confira "Despesas por Categoria"
- [ ] Verifique o "Histórico Mensal"

### 4. Lista de Transações
- [ ] Volte para "Transações"
- [ ] Confirme que a transação foi atualizada/removida
- [ ] Verifique se não há duplicações

## ❓ Perguntas Frequentes

### P: Posso editar transações importadas de extratos?
**R:** Sim! Todas as transações podem ser editadas, independentemente de como foram criadas (manual ou importação).

### P: O que acontece se eu mudar a conta de uma transação?
**R:** O sistema automaticamente:
1. Remove o impacto da conta antiga
2. Aplica o impacto na conta nova
3. Atualiza os saldos de ambas as contas

### P: Posso desfazer uma exclusão?
**R:** Não. A exclusão é permanente. Por isso sempre há uma confirmação antes de excluir.

### P: E se eu excluir uma transação por engano?
**R:** Você precisará criar uma nova transação com os mesmos dados. Por isso é importante confirmar antes de excluir.

### P: Posso editar várias transações de uma vez?
**R:** Não, no momento você precisa editar uma transação por vez.

### P: O que acontece com transações parceladas?
**R:** Cada parcela é uma transação independente. Você pode editar ou excluir cada parcela individualmente.

### P: Posso transformar uma transação simples em parcelada?
**R:** Não. Para criar parcelas, você precisa criar uma nova transação parcelada. A opção de parcelar só aparece ao criar novas transações.

### P: Os relatórios são atualizados automaticamente?
**R:** Sim! Todos os dashboards e relatórios refletem as mudanças imediatamente após editar ou excluir uma transação.

## 🆘 Resolução de Problemas

### Problema: O saldo não atualizou após editar

**Soluções:**
1. Recarregue a página (F5)
2. Vá para "Contas Bancárias" e clique em "Recalcular Saldos"
3. Verifique se a transação foi realmente salva

### Problema: Não consigo editar uma transação

**Soluções:**
1. Verifique se você está logado
2. Recarregue a página
3. Tente fazer logout e login novamente
4. Verifique o console do navegador (F12) para erros

### Problema: A exclusão não funcionou

**Soluções:**
1. Recarregue a página para ver se a transação foi realmente excluída
2. Verifique sua conexão com a internet
3. Tente novamente

### Problema: Editei mas os dados antigos ainda aparecem

**Soluções:**
1. Recarregue a página (F5)
2. Limpe o cache do navegador
3. Verifique se clicou em "Atualizar" e não apenas fechou o diálogo

## 📊 Impacto nos Relatórios

Quando você edita ou exclui uma transação, os seguintes relatórios são afetados:

### Dashboard Principal
- ✅ Saldo Total
- ✅ Receitas do Mês
- ✅ Despesas do Mês
- ✅ Gráfico de Despesas por Categoria
- ✅ Gráfico de Histórico Mensal

### Página de Contas
- ✅ Saldo de cada conta
- ✅ Total de contas

### Página de Relatórios
- ✅ Despesas por Categoria
- ✅ Histórico Mensal
- ✅ Projeção de Fluxo de Caixa

### Página de Transações
- ✅ Lista de transações
- ✅ Filtros e buscas

## 🎉 Resumo

Agora você tem controle total sobre suas transações:

✅ **Edite** qualquer campo de qualquer transação
✅ **Exclua** transações duplicadas ou incorretas
✅ **Saldos atualizados** automaticamente
✅ **Relatórios precisos** sempre
✅ **Interface intuitiva** com ícones claros
✅ **Confirmações** para evitar erros
✅ **Feedback visual** em todas as operações

---

**Última atualização:** 2025-12-01  
**Versão:** 1.0.4 (com edição e exclusão de transações)
