# 🤖 IA com Permissão para Criar Transações - OnliFin

## ✅ Funcionalidade Implementada

A IA do OnliFin agora pode **criar transações automaticamente** quando solicitado pelo usuário através de linguagem natural!

**Exemplos de comandos**:
- "Registre uma despesa de R$ 150 no supermercado"
- "Adicione uma receita de R$ 3000 do meu salário"
- "Cadastre um gasto de R$ 45 no cinema"
- "Crie uma despesa de R$ 80 com Uber na categoria transporte"

---

## 🔐 Segurança e Controle

### Permissão Desativada por Padrão
- ✅ A permissão de escrita está **desativada por padrão**
- ✅ Apenas administradores podem ativar
- ✅ Requer ativação explícita no painel admin

### Auditoria Completa
- ✅ Todas as transações criadas pela IA são registradas
- ✅ Logs incluem `action_type: 'write'`
- ✅ Referência à transação criada (`created_transaction_id`)
- ✅ Histórico completo de quem, quando e o quê

### Validação Rigorosa
- ✅ Tipo de transação validado (income/expense)
- ✅ Valor deve ser positivo
- ✅ Data obrigatória (usa data atual se não especificada)
- ✅ Conta e categoria validadas contra dados do usuário

---

## ⚙️ Como Ativar

### 1. Acessar Painel de Administração

Faça login como **admin** e acesse:
```
Menu Admin → Administração de IA
```

### 2. Ativar Permissão de Escrita

1. Vá para a aba **Permissões**
2. Localize o toggle **"Permitir Criação de Transações"**
3. Ative o toggle
4. Leia o aviso de segurança
5. Clique em **Salvar Permissões**

### 3. Verificar Ativação

No card de status (aba Configurações), você verá:
```
Criação de Transações: Ativada ⚠️
```

---

## 💬 Como Usar

### Comandos Básicos

#### Criar Despesa
```
Usuário: "Registre uma despesa de R$ 150 no supermercado"

IA: ✅ Transação registrada com sucesso!

Tipo: Despesa
Valor: R$ 150.00
Descrição: Supermercado
Data: 2025-12-01
```

#### Criar Receita
```
Usuário: "Adicione uma receita de R$ 3000 do meu salário"

IA: ✅ Transação registrada com sucesso!

Tipo: Receita
Valor: R$ 3000.00
Descrição: Salário
Data: 2025-12-01
```

#### Criar com Categoria Específica
```
Usuário: "Cadastre um gasto de R$ 80 com Uber na categoria transporte"

IA: ✅ Transação registrada com sucesso!

Tipo: Despesa
Valor: R$ 80.00
Descrição: Uber
Categoria: Transporte
Data: 2025-12-01
```

### Comandos Avançados

#### Especificar Data
```
Usuário: "Registre uma despesa de R$ 200 no restaurante no dia 28/11"

IA: ✅ Transação registrada com sucesso!

Tipo: Despesa
Valor: R$ 200.00
Descrição: Restaurante
Data: 2025-11-28
```

#### Especificar Conta
```
Usuário: "Adicione uma receita de R$ 500 na conta corrente"

IA: ✅ Transação registrada com sucesso!

Tipo: Receita
Valor: R$ 500.00
Conta: Conta Corrente
Data: 2025-12-01
```

---

## 🔍 Como Funciona Internamente

### Fluxo de Criação

```
┌─────────────┐
│   Usuário   │
│  "Registre  │
│  R$ 150"    │
└──────┬──────┘
       │ 1. Envia mensagem
       ▼
┌─────────────────────┐
│  Edge Function      │
│  ai-assistant       │
├─────────────────────┤
│ 2. Verifica permis. │
│ 3. Envia para Gemini│
└──────┬──────────────┘
       │ 4. Gemini analisa
       ▼
┌─────────────────────┐
│   Gemini API        │
├─────────────────────┤
│ 5. Retorna JSON:    │
│ {                   │
│   "action": "create"│
│   "data": {...}     │
│ }                   │
└──────┬──────────────┘
       │ 6. Edge Function processa
       ▼
┌─────────────────────┐
│  Edge Function      │
├─────────────────────┤
│ 7. Valida dados     │
│ 8. Cria transação   │
│ 9. Atualiza saldo   │
│ 10. Registra log    │
└──────┬──────────────┘
       │ 11. Retorna confirmação
       ▼
┌─────────────┐
│   Usuário   │
│  ✅ Sucesso │
└─────────────┘
```

### Formato JSON Interno

Quando a IA detecta uma solicitação de criação, ela retorna:

```json
{
  "action": "create_transaction",
  "transaction_data": {
    "type": "expense",
    "amount": 150.00,
    "date": "2025-12-01",
    "description": "Supermercado",
    "account_id": "uuid-da-conta",
    "category_id": "uuid-da-categoria"
  },
  "confirmation_message": "Transação de despesa de R$ 150,00 no supermercado registrada com sucesso!"
}
```

A Edge Function então:
1. Extrai o JSON da resposta
2. Valida os dados
3. Cria a transação no banco
4. Atualiza o saldo da conta
5. Registra no log de auditoria
6. Retorna mensagem de confirmação

---

## 📊 Auditoria e Logs

### Visualizar Transações Criadas pela IA

1. Vá em **Administração de IA** → **Logs de Chat**
2. Procure por logs com badge **"write"**
3. Cada log mostra:
   - Mensagem do usuário
   - Resposta da IA
   - ID da transação criada
   - Data e hora

### Verificar Transação Criada

1. Vá em **Transações**
2. Localize a transação pela data e valor
3. A transação terá todos os campos preenchidos
4. O saldo da conta foi atualizado automaticamente

### Exportar Logs

1. Na aba **Logs de Chat**, clique em **Exportar**
2. Um arquivo CSV será baixado com:
   - Data e hora
   - Usuário
   - Mensagem
   - Resposta
   - Nível de permissão
   - Tipo de ação (read/write)

---

## ⚠️ Validações e Erros

### Validações Automáticas

A IA valida automaticamente:

1. **Tipo de Transação**
   - Deve ser "income" ou "expense"
   - Erro: "Tipo de transação inválido"

2. **Valor**
   - Deve ser um número positivo
   - Erro: "Valor inválido. Deve ser um número positivo"

3. **Data**
   - Formato YYYY-MM-DD
   - Usa data atual se não especificada

4. **Conta**
   - Deve existir no cadastro do usuário
   - Usa primeira conta disponível se não especificada

5. **Categoria**
   - Deve existir e ser do tipo correto (income/expense)
   - Opcional

### Mensagens de Erro

#### Sem Contas Cadastradas
```
IA: ❌ Você ainda não tem contas cadastradas. 
Por favor, cadastre uma conta antes de criar transações.
```

#### Valor Inválido
```
IA: ❌ Erro ao criar transação: Valor inválido. 
Deve ser um número positivo.
```

#### Categoria Não Encontrada
```
IA: ❌ Não encontrei a categoria "alimentação" no seu cadastro. 
Deseja criar a transação sem categoria ou cadastrar a categoria primeiro?
```

---

## 🧪 Exemplos de Teste

### Teste 1: Despesa Simples
```
Comando: "Registre uma despesa de R$ 50 no café"
Resultado Esperado: ✅ Transação criada com sucesso
Verificar: Transação aparece na lista, saldo atualizado
```

### Teste 2: Receita com Data
```
Comando: "Adicione uma receita de R$ 1000 de freelance no dia 25/11"
Resultado Esperado: ✅ Transação criada com data 2025-11-25
Verificar: Data correta na transação
```

### Teste 3: Despesa com Categoria
```
Comando: "Cadastre R$ 200 de compras na categoria alimentação"
Resultado Esperado: ✅ Transação com categoria correta
Verificar: Categoria associada à transação
```

### Teste 4: Valor Inválido
```
Comando: "Registre uma despesa de -50 reais"
Resultado Esperado: ❌ Erro: Valor inválido
Verificar: Nenhuma transação criada
```

### Teste 5: Sem Permissão
```
1. Desative "Permitir Criação de Transações"
2. Comando: "Registre uma despesa de R$ 100"
Resultado Esperado: IA responde mas não cria transação
Verificar: Nenhuma transação criada, apenas resposta informativa
```

---

## 🔒 Boas Práticas de Segurança

### Para Administradores

✅ **Recomendado**:
- Ativar permissão apenas quando necessário
- Revisar logs regularmente
- Verificar transações criadas pela IA
- Manter backup dos dados
- Documentar quando e por que ativou

❌ **Evitar**:
- Deixar permissão ativada permanentemente sem necessidade
- Não revisar logs de auditoria
- Compartilhar acesso admin sem controle
- Ignorar alertas de segurança

### Para Usuários

✅ **Recomendado**:
- Verificar transações criadas pela IA
- Usar comandos claros e específicos
- Confirmar valores e datas
- Reportar erros ao administrador

❌ **Evitar**:
- Confiar cegamente sem verificar
- Usar comandos ambíguos
- Criar transações duplicadas

---

## 🐛 Troubleshooting

### Problema: IA não cria transação

**Causa 1**: Permissão desativada
**Solução**: Ative "Permitir Criação de Transações" no painel admin

**Causa 2**: Sem contas cadastradas
**Solução**: Cadastre pelo menos uma conta antes

**Causa 3**: Comando ambíguo
**Solução**: Use comandos mais específicos com valor e descrição claros

---

### Problema: Transação criada com dados errados

**Causa**: IA interpretou incorretamente
**Solução**: 
1. Exclua a transação manualmente
2. Use comando mais específico
3. Especifique conta e categoria explicitamente

---

### Problema: Saldo não atualizado

**Causa**: Transação sem conta associada
**Solução**: 
1. Verifique se a transação tem `account_id`
2. Atualize manualmente o saldo se necessário
3. Especifique a conta no próximo comando

---

## 📈 Estatísticas e Métricas

### Métricas Disponíveis

No painel de logs, você pode ver:
- Total de transações criadas pela IA
- Taxa de sucesso vs erro
- Categorias mais usadas
- Valores médios
- Horários de maior uso

### Análise de Uso

```sql
-- Contar transações criadas pela IA
SELECT COUNT(*) 
FROM ai_chat_logs 
WHERE action_type = 'write' 
AND created_transaction_id IS NOT NULL;

-- Valor total criado pela IA
SELECT SUM(t.amount) 
FROM transactions t
JOIN ai_chat_logs l ON t.id = l.created_transaction_id
WHERE l.action_type = 'write';
```

---

## 🎯 Casos de Uso

### 1. Registro Rápido de Despesas
**Cenário**: Usuário acabou de fazer uma compra e quer registrar rapidamente

**Comando**: "Registre R$ 35 no estacionamento"

**Benefício**: Registro instantâneo sem abrir formulário

---

### 2. Entrada de Múltiplas Transações
**Cenário**: Usuário quer registrar várias compras do dia

**Comandos**:
```
"Registre R$ 50 no café da manhã"
"Adicione R$ 120 no almoço"
"Cadastre R$ 30 no Uber"
```

**Benefício**: Entrada rápida via conversação natural

---

### 3. Registro com Contexto
**Cenário**: Usuário quer registrar com detalhes específicos

**Comando**: "Cadastre uma despesa de R$ 200 no restaurante italiano na categoria alimentação na conta corrente no dia 28/11"

**Benefício**: Todos os detalhes capturados em um comando

---

## 📝 Changelog

### v1.1.0 (01/12/2025)
- ✅ Implementada permissão de criação de transações
- ✅ Validação completa de dados
- ✅ Auditoria com action_type e created_transaction_id
- ✅ Toggle no painel admin
- ✅ Notificações de sucesso/erro
- ✅ Atualização automática de saldo
- ✅ Documentação completa

---

## 🚀 Próximas Melhorias

### Versão 1.2
- [ ] Criação de transações recorrentes
- [ ] Parcelamento automático
- [ ] Sugestão de categoria baseada em histórico
- [ ] Confirmação antes de criar (modo seguro)

### Versão 1.3
- [ ] Edição de transações via IA
- [ ] Exclusão de transações via IA
- [ ] Transferências entre contas
- [ ] Análise de duplicatas

### Versão 2.0
- [ ] Criação em lote (múltiplas transações)
- [ ] Importação de nota fiscal via foto
- [ ] Reconhecimento de voz
- [ ] Integração com Open Banking

---

## 🎓 Resumo

A funcionalidade de **criação de transações pela IA** permite que usuários registrem suas finanças de forma natural e rápida através de comandos em linguagem natural.

**Principais Vantagens**:
1. ✅ Registro rápido sem formulários
2. ✅ Linguagem natural e intuitiva
3. ✅ Validação automática de dados
4. ✅ Auditoria completa
5. ✅ Segurança com permissão desativada por padrão

**Como Começar**:
1. Ative a permissão no painel admin
2. Teste com: "Registre uma despesa de R$ 50 no café"
3. Verifique a transação criada
4. Revise os logs de auditoria

**Teste agora e simplifique sua gestão financeira!** 🚀
