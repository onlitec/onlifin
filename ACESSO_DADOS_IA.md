# 🤖 Acesso aos Dados pela IA - OnliFin

## ✅ Problema Resolvido

**Situação Anterior**: O modelo Gemini estava respondendo via API, mas não tinha acesso aos dados financeiros do usuário.

**Solução Implementada**: A Edge Function `ai-assistant` agora busca os dados financeiros do usuário no Supabase e os envia como contexto para o modelo Gemini, permitindo respostas personalizadas baseadas nos dados reais.

---

## 🔐 Níveis de Permissão

O sistema implementa três níveis de acesso aos dados, configuráveis no **Painel de Administração de IA**:

### 1. **read_aggregated** (Padrão - Mais Seguro)
Apenas dados agregados e estatísticas:
- ✅ Total de contas e cartões
- ✅ Saldo total
- ✅ Total de receitas e despesas
- ✅ Saldo líquido
- ✅ Despesas agrupadas por categoria
- ✅ Quantidade de transações
- ❌ Sem detalhes de transações individuais
- ❌ Sem informações sensíveis

**Exemplo de dados enviados**:
```json
{
  "permission_level": "read_aggregated",
  "financial_summary": {
    "total_accounts": 3,
    "total_cards": 2,
    "total_balance": 15000.00,
    "total_income": 8000.00,
    "total_expense": 3500.00,
    "net_balance": 4500.00,
    "expenses_by_category": {
      "alimentacao": 1200.00,
      "transporte": 800.00,
      "lazer": 500.00
    },
    "transaction_count": 45
  }
}
```

**Casos de uso**:
- Análise geral de gastos
- Dicas de economia baseadas em padrões
- Planejamento financeiro básico
- Recomendações de orçamento

---

### 2. **read_transactional** (Intermediário)
Dados transacionais com detalhes, mas sem informações ultra-sensíveis:
- ✅ Lista de contas (nome, saldo, moeda)
- ✅ Lista de cartões (nome, limite, limite disponível)
- ✅ Últimas 50 transações (tipo, valor, descrição, categoria, data)
- ✅ Categorias cadastradas
- ❌ Sem números de conta/cartão completos
- ❌ Sem dados bancários sensíveis

**Exemplo de dados enviados**:
```json
{
  "permission_level": "read_transactional",
  "accounts": [
    { "id": "uuid", "name": "Conta Corrente", "balance": 5000.00, "currency": "BRL" }
  ],
  "cards": [
    { "id": "uuid", "name": "Cartão Visa", "card_limit": 10000.00, "available_limit": 7500.00 }
  ],
  "recent_transactions": [
    {
      "id": "uuid",
      "type": "expense",
      "amount": 150.00,
      "description": "Supermercado",
      "category_id": "uuid",
      "date": "2025-11-28",
      "account_id": "uuid"
    }
  ],
  "categories": [
    { "id": "uuid", "name": "Alimentação", "type": "expense" }
  ]
}
```

**Casos de uso**:
- Categorização automática de transações
- Análise detalhada de padrões de gastos
- Identificação de transações duplicadas
- Sugestões de economia específicas
- Alertas de gastos incomuns

---

### 3. **read_full** (Acesso Completo - Requer Consentimento)
Acesso completo a todos os dados financeiros:
- ✅ Todas as contas com todos os campos
- ✅ Todos os cartões com todos os campos
- ✅ Todas as transações (sem limite)
- ✅ Todas as categorias
- ⚠️ Inclui informações sensíveis

**Casos de uso**:
- Análise financeira profunda
- Previsões avançadas de fluxo de caixa
- Recomendações de investimento
- Auditoria completa de finanças

**⚠️ IMPORTANTE**: Este nível deve ser usado apenas quando necessário e com consentimento explícito do usuário.

---

## 🛠️ Como Funciona

### Fluxo de Dados

```
┌─────────────┐
│   Usuário   │
│  (Frontend) │
└──────┬──────┘
       │ 1. Envia mensagem
       ▼
┌─────────────────────┐
│  Edge Function      │
│  ai-assistant       │
├─────────────────────┤
│ 2. Busca config IA  │
│ 3. Busca dados user │
│ 4. Monta contexto   │
└──────┬──────────────┘
       │ 5. Envia para Gemini
       ▼
┌─────────────────────┐
│   Gemini API        │
│  (Google)           │
├─────────────────────┤
│ 6. Processa com     │
│    contexto         │
└──────┬──────────────┘
       │ 7. Retorna resposta
       ▼
┌─────────────────────┐
│  Edge Function      │
│  ai-assistant       │
├─────────────────────┤
│ 8. Retorna resposta │
│    + metadata       │
└──────┬──────────────┘
       │ 9. Exibe resposta
       ▼
┌─────────────┐
│   Usuário   │
│  (Frontend) │
└─────────────┘
```

### Código da Edge Function

A função `getUserFinancialData()` busca os dados baseado no nível de permissão:

```typescript
async function getUserFinancialData(supabaseClient, userId, permissionLevel) {
  if (permissionLevel === 'read_aggregated') {
    // Busca apenas dados para agregação
    // Calcula totais, médias, somatórios
    return { financial_summary: {...} };
  }
  
  if (permissionLevel === 'read_transactional') {
    // Busca transações recentes (últimas 50)
    // Busca contas e cartões (sem dados sensíveis)
    return { accounts, cards, recent_transactions, categories };
  }
  
  if (permissionLevel === 'read_full') {
    // Busca TODOS os dados
    return { accounts, cards, transactions, categories };
  }
}
```

---

## 📊 Auditoria e Logs

Cada interação com a IA é registrada na tabela `ai_chat_logs`:

```sql
CREATE TABLE ai_chat_logs (
  id uuid PRIMARY KEY,
  user_id uuid REFERENCES profiles(id),
  message text NOT NULL,
  response text,
  data_accessed jsonb,              -- Quais dados foram acessados
  permission_level ai_permission_level NOT NULL,  -- Nível usado
  created_at timestamptz DEFAULT now()
);
```

### Visualização de Logs

No **Painel de Administração de IA** (`/ai-admin`), você pode:
- ✅ Ver todas as conversas
- ✅ Ver qual nível de permissão foi usado
- ✅ Ver quais dados foram acessados
- ✅ Exportar logs para auditoria
- ✅ Filtrar por usuário e data

---

## ⚙️ Configuração

### 1. Acessar Painel de Administração

Faça login como **admin** e acesse:
```
Menu Admin → Administração de IA
```

### 2. Configurar Nível de Permissão

Na aba **Configurações**:
1. Selecione o **Modelo de IA** (Gemini 2.5 Flash)
2. Escolha o **Nível de Permissão**:
   - `read_aggregated` - Apenas estatísticas (recomendado)
   - `read_transactional` - Transações recentes
   - `read_full` - Acesso completo (requer consentimento)
3. Clique em **Salvar Configuração**

### 3. Testar o Assistente

1. Clique no botão flutuante de chat (canto inferior direito)
2. Faça perguntas sobre suas finanças:
   - "Qual é meu saldo total?"
   - "Quanto gastei este mês?"
   - "Em que categoria gasto mais?"
   - "Tenho alguma despesa recorrente alta?"

---

## 🧪 Exemplos de Perguntas

### Com `read_aggregated`:
```
Usuário: "Qual é meu saldo total?"
IA: "Seu saldo total é R$ 15.000,00 distribuído em 3 contas."

Usuário: "Quanto gastei este mês?"
IA: "Você gastou R$ 3.500,00 este mês, sendo R$ 1.200,00 em alimentação."

Usuário: "Em que categoria gasto mais?"
IA: "Sua maior categoria de gastos é Alimentação com R$ 1.200,00."
```

### Com `read_transactional`:
```
Usuário: "Quais foram minhas últimas compras?"
IA: "Suas últimas compras foram:
- R$ 150,00 no Supermercado (28/11)
- R$ 80,00 em Transporte (27/11)
- R$ 45,00 em Lazer (26/11)"

Usuário: "Tenho alguma despesa duplicada?"
IA: "Identifiquei duas transações similares:
- R$ 50,00 em 'Netflix' (dia 15)
- R$ 50,00 em 'Netflix Premium' (dia 16)
Pode ser uma cobrança duplicada."
```

### Com `read_full`:
```
Usuário: "Faça uma análise completa das minhas finanças"
IA: "Análise completa:
- Receitas: R$ 8.000,00/mês
- Despesas: R$ 3.500,00/mês
- Taxa de poupança: 56%
- Principais gastos: Alimentação (34%), Transporte (23%)
- Recomendação: Você está economizando bem! Considere investir..."
```

---

## 🔒 Segurança e Privacidade

### Medidas Implementadas

1. **Níveis de Permissão Granulares**
   - Acesso mínimo necessário por padrão
   - Escalação gradual conforme necessidade

2. **Auditoria Completa**
   - Todos os acessos são registrados
   - Logs incluem timestamp, usuário, dados acessados

3. **Dados Mascarados**
   - Números de conta/cartão não são enviados (exceto em `read_full`)
   - Informações sensíveis são filtradas

4. **Consentimento Explícito**
   - `read_full` requer confirmação do administrador
   - Usuário deve estar ciente do nível de acesso

5. **Comunicação Segura**
   - TLS/HTTPS em todas as comunicações
   - Service Role Key usado apenas no backend

### Boas Práticas

✅ **Recomendado**:
- Usar `read_aggregated` para análises gerais
- Usar `read_transactional` para categorização e alertas
- Revisar logs regularmente
- Documentar mudanças de nível de permissão

❌ **Evitar**:
- Usar `read_full` sem necessidade
- Compartilhar logs com terceiros
- Deixar logs expostos publicamente

---

## 🐛 Troubleshooting

### Problema: IA não responde com dados do usuário

**Solução**:
1. Verifique se há uma configuração ativa em `ai_configurations`
2. Verifique se o usuário tem dados cadastrados (contas, transações)
3. Verifique os logs da Edge Function no Supabase Dashboard

### Problema: Erro 500 ao chamar IA

**Solução**:
1. Verifique se `SUPABASE_URL` e `SUPABASE_SERVICE_ROLE_KEY` estão configurados
2. Verifique se as tabelas existem no banco de dados
3. Verifique os logs da Edge Function

### Problema: IA responde mas sem contexto

**Solução**:
1. Verifique o nível de permissão configurado
2. Verifique se o usuário tem dados cadastrados
3. Teste com uma pergunta específica: "Qual é meu saldo total?"

---

## 📈 Melhorias Futuras

### Versão 1.1
- [ ] Cache de dados do usuário (reduzir queries)
- [ ] Suporte a múltiplos idiomas
- [ ] Histórico de conversas persistente
- [ ] Sugestões proativas baseadas em padrões

### Versão 1.2
- [ ] Integração com Open Banking
- [ ] Previsões de fluxo de caixa com ML
- [ ] Alertas inteligentes de gastos
- [ ] Recomendações de investimento

### Versão 2.0
- [ ] Assistente de voz
- [ ] Análise de documentos (extratos, notas fiscais)
- [ ] Planejamento financeiro automatizado
- [ ] Integração com contadores

---

## 📝 Changelog

### v1.0.0 (01/12/2025)
- ✅ Implementado acesso aos dados do usuário
- ✅ Três níveis de permissão (aggregated, transactional, full)
- ✅ Auditoria completa de acessos
- ✅ Integração com Gemini API
- ✅ Logs detalhados no painel admin
- ✅ Documentação completa

---

## 🎯 Resumo

A IA agora tem acesso aos dados financeiros do usuário de forma **segura**, **auditável** e **configurável**. O sistema permite:

1. ✅ Respostas personalizadas baseadas em dados reais
2. ✅ Controle granular de acesso aos dados
3. ✅ Auditoria completa de todas as interações
4. ✅ Segurança e privacidade garantidas
5. ✅ Fácil configuração pelo painel admin

**Teste agora**: Abra o chat da IA e pergunte "Qual é meu saldo total?" 🚀
