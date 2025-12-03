# 🧪 Guia de Teste - IA Criando Transações

## 📋 Pré-requisitos

Antes de testar, certifique-se de que:
- ✅ Você está logado como **admin**
- ✅ Você tem pelo menos **uma conta cadastrada**
- ✅ Você tem algumas **categorias cadastradas**
- ✅ A Edge Function `ai-assistant` está na **versão 4**

---

## 🚀 Passo a Passo para Testar

### 1. Preparar Ambiente

#### Verificar Contas
1. Vá em **Contas**
2. Certifique-se de ter pelo menos uma conta
3. Anote o nome da conta (ex: "Conta Corrente")
4. Anote o saldo atual

#### Verificar Categorias
1. Vá em **Categorias**
2. Certifique-se de ter categorias de despesa (ex: "Alimentação", "Transporte")
3. Certifique-se de ter categorias de receita (ex: "Salário")

---

### 2. Ativar Permissão de Escrita

#### Acessar Painel Admin
1. Menu Admin → **Administração de IA**
2. Vá para a aba **Permissões**

#### Ativar Toggle
1. Localize **"Permitir Criação de Transações"**
2. Ative o toggle (deve ficar azul)
3. Leia o aviso de segurança (fundo amarelo)
4. Clique em **Salvar Permissões**
5. Aguarde mensagem de sucesso ✅

#### Verificar Ativação
1. Vá para a aba **Configurações**
2. No card de status, verifique:
   ```
   Criação de Transações: Ativada ⚠️
   ```

---

### 3. Testes Básicos

#### Teste 1: Despesa Simples
```
1. Abra o chat da IA (botão flutuante)
2. Digite: "Registre uma despesa de R$ 50 no café"
3. Envie a mensagem
```

**Resultado Esperado**:
- ✅ IA responde com confirmação
- ✅ Mensagem inclui: "✅ Transação registrada com sucesso!"
- ✅ Mostra: Tipo, Valor, Descrição, Data
- ✅ Notificação toast: "✅ Transação Criada"

**Verificar**:
1. Vá em **Transações**
2. Procure transação com:
   - Valor: R$ 50,00
   - Descrição: "café" ou similar
   - Tipo: Despesa
   - Data: Hoje
3. Verifique se o saldo da conta foi atualizado (diminuiu R$ 50)

---

#### Teste 2: Receita
```
1. No chat da IA
2. Digite: "Adicione uma receita de R$ 1000 de freelance"
3. Envie
```

**Resultado Esperado**:
- ✅ IA confirma criação
- ✅ Tipo: Receita
- ✅ Valor: R$ 1000,00

**Verificar**:
1. Transação aparece na lista
2. Saldo da conta aumentou R$ 1000

---

#### Teste 3: Despesa com Categoria
```
1. No chat da IA
2. Digite: "Cadastre R$ 80 de Uber na categoria transporte"
3. Envie
```

**Resultado Esperado**:
- ✅ IA confirma criação
- ✅ Categoria: Transporte

**Verificar**:
1. Transação tem categoria "Transporte" associada
2. Aparece corretamente no dashboard por categoria

---

### 4. Testes Avançados

#### Teste 4: Especificar Conta
```
1. Se você tem múltiplas contas
2. Digite: "Registre R$ 200 no restaurante na conta corrente"
3. Envie
```

**Resultado Esperado**:
- ✅ Transação criada na conta especificada
- ✅ Saldo da conta corrente atualizado

---

#### Teste 5: Especificar Data (se suportado)
```
1. Digite: "Adicione uma despesa de R$ 100 no supermercado no dia 28/11"
2. Envie
```

**Resultado Esperado**:
- ✅ Transação com data 2025-11-28
- ✅ Não usa data atual

---

### 5. Testes de Validação

#### Teste 6: Valor Inválido
```
1. Digite: "Registre uma despesa de -50 reais"
2. Envie
```

**Resultado Esperado**:
- ❌ IA retorna erro
- ❌ Mensagem: "Valor inválido. Deve ser um número positivo"
- ❌ Nenhuma transação criada

---

#### Teste 7: Comando Ambíguo
```
1. Digite: "Registre uma despesa"
2. Envie
```

**Resultado Esperado**:
- ❌ IA pede mais informações
- ❌ Solicita valor e descrição
- ❌ Nenhuma transação criada

---

#### Teste 8: Sem Valor
```
1. Digite: "Cadastre uma despesa no supermercado"
2. Envie
```

**Resultado Esperado**:
- ❌ IA pede o valor
- ❌ Nenhuma transação criada

---

### 6. Testes de Segurança

#### Teste 9: Permissão Desativada
```
1. Vá em Administração de IA → Permissões
2. Desative "Permitir Criação de Transações"
3. Salve
4. No chat, digite: "Registre uma despesa de R$ 100"
5. Envie
```

**Resultado Esperado**:
- ❌ IA responde mas NÃO cria transação
- ❌ Pode dar dica de como criar manualmente
- ❌ Nenhuma transação criada

---

#### Teste 10: Usuário Não-Admin
```
1. Faça logout
2. Faça login como usuário comum (não admin)
3. Tente criar transação via IA
```

**Resultado Esperado**:
- ❌ Usuário não consegue ativar permissão
- ❌ Se admin ativou, usuário pode criar transações
- ✅ Transações criadas são do próprio usuário

---

### 7. Testes de Auditoria

#### Teste 11: Verificar Logs
```
1. Crie algumas transações via IA
2. Vá em Administração de IA → Logs de Chat
3. Procure logs recentes
```

**Resultado Esperado**:
- ✅ Logs mostram action_type: "write"
- ✅ Cada log tem created_transaction_id
- ✅ Mensagem e resposta registradas
- ✅ Data e hora corretas

---

#### Teste 12: Exportar Logs
```
1. Na aba Logs de Chat
2. Clique em "Exportar"
3. Abra o arquivo CSV
```

**Resultado Esperado**:
- ✅ CSV contém todas as conversas
- ✅ Inclui transações criadas
- ✅ Formato correto

---

### 8. Testes de Integração

#### Teste 13: Dashboard Atualizado
```
1. Crie várias transações via IA
2. Vá para o Dashboard
```

**Resultado Esperado**:
- ✅ Saldo total atualizado
- ✅ Gráfico de despesas por categoria atualizado
- ✅ Histórico mensal atualizado
- ✅ Todas as métricas refletem as novas transações

---

#### Teste 14: Relatórios
```
1. Crie transações via IA
2. Vá em Relatórios
3. Gere relatório do mês atual
```

**Resultado Esperado**:
- ✅ Transações criadas pela IA aparecem no relatório
- ✅ Valores corretos
- ✅ Categorias corretas

---

## ✅ Checklist de Validação

Marque cada item após testar:

### Funcionalidade Básica
- [ ] IA cria despesas corretamente
- [ ] IA cria receitas corretamente
- [ ] Valores são validados
- [ ] Descrições são capturadas
- [ ] Data atual é usada por padrão

### Categorização
- [ ] IA identifica categorias corretamente
- [ ] Transações são associadas às categorias
- [ ] Categorias aparecem no dashboard

### Contas
- [ ] Saldo é atualizado automaticamente
- [ ] Transações são associadas à conta correta
- [ ] Múltiplas contas funcionam

### Validação
- [ ] Valores negativos são rejeitados
- [ ] Comandos ambíguos são tratados
- [ ] Erros têm mensagens claras

### Segurança
- [ ] Permissão desativada por padrão
- [ ] Toggle funciona corretamente
- [ ] Aviso de segurança é exibido
- [ ] Apenas admin pode ativar

### Auditoria
- [ ] Logs registram action_type
- [ ] Logs incluem created_transaction_id
- [ ] Exportação funciona
- [ ] Histórico completo disponível

### Integração
- [ ] Dashboard atualizado
- [ ] Relatórios incluem transações da IA
- [ ] Fluxo de caixa atualizado
- [ ] Todas as visualizações corretas

---

## 🐛 Problemas Comuns e Soluções

### Problema: IA não cria transação

**Verificar**:
1. Permissão está ativada?
2. Você tem contas cadastradas?
3. Comando foi claro o suficiente?
4. Edge Function está na versão 4?

**Solução**:
- Ative a permissão no painel admin
- Cadastre pelo menos uma conta
- Use comando mais específico
- Verifique versão da Edge Function

---

### Problema: Saldo não atualizado

**Verificar**:
1. Transação tem account_id?
2. Conta existe no banco?

**Solução**:
- Especifique a conta no comando
- Verifique se a conta não foi excluída
- Atualize manualmente se necessário

---

### Problema: Categoria errada

**Verificar**:
1. Categoria existe?
2. Nome da categoria está correto?

**Solução**:
- Especifique a categoria explicitamente
- Use nome exato da categoria
- Cadastre categoria se não existir

---

### Problema: Erro 500

**Verificar**:
1. Edge Function deployada?
2. Variáveis de ambiente configuradas?
3. Banco de dados acessível?

**Solução**:
- Verifique logs da Edge Function no Supabase
- Confirme SUPABASE_URL e SUPABASE_SERVICE_ROLE_KEY
- Teste conexão com banco

---

## 📊 Métricas de Sucesso

Após completar todos os testes, você deve ter:

1. ✅ Pelo menos 5 transações criadas via IA
2. ✅ Saldos atualizados corretamente
3. ✅ Logs de auditoria completos
4. ✅ Dashboard refletindo as mudanças
5. ✅ Nenhum erro não tratado
6. ✅ Validações funcionando
7. ✅ Permissões controladas

---

## 🎯 Cenários de Uso Real

### Cenário 1: Registro Rápido
**Situação**: Usuário acabou de fazer uma compra

**Teste**:
```
"Registre R$ 35 no estacionamento"
```

**Tempo Esperado**: < 5 segundos

---

### Cenário 2: Múltiplas Transações
**Situação**: Usuário quer registrar várias compras do dia

**Teste**:
```
"Registre R$ 50 no café"
"Adicione R$ 120 no almoço"
"Cadastre R$ 30 no Uber"
```

**Tempo Esperado**: < 15 segundos total

---

### Cenário 3: Transação Detalhada
**Situação**: Usuário quer todos os detalhes

**Teste**:
```
"Cadastre uma despesa de R$ 200 no restaurante italiano 
na categoria alimentação na conta corrente"
```

**Tempo Esperado**: < 5 segundos

---

## 📝 Relatório de Teste

Após completar os testes, documente:

### Testes Realizados
- Total de testes: ___
- Testes passados: ___
- Testes falhados: ___

### Problemas Encontrados
1. ___
2. ___
3. ___

### Sugestões de Melhoria
1. ___
2. ___
3. ___

### Conclusão
- [ ] Funcionalidade pronta para produção
- [ ] Requer ajustes
- [ ] Requer mais testes

---

**Última atualização**: 01/12/2025  
**Versão da Edge Function**: 4  
**Status**: ✅ Pronto para Teste
