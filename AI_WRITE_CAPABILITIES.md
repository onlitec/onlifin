# 🤖 Capacidades de Escrita da IA - Documentação Completa

## 📋 Visão Geral

O Assistente de IA agora possui **permissões de escrita completas**, permitindo criar e modificar dados financeiros através de comandos em linguagem natural. Esta funcionalidade transforma a IA de um assistente passivo em um agente ativo capaz de executar tarefas.

---

## ✅ Permissões Ativadas

### Status Atual
- ✅ **Permissão de Escrita**: ATIVADA
- ✅ **Nível de Acesso**: `read_full` (acesso completo aos dados)
- ✅ **Criar Transações**: HABILITADO
- ✅ **Categorizar Transações**: HABILITADO
- ✅ **Operações em Lote**: HABILITADO

### Como Verificar
1. Acesse **Administração de IA** no menu
2. Verifique a seção "Status Atual"
3. Confirme que "Permissão de Escrita" está **Ativada ⚠️**

---

## 🎯 Capacidades Disponíveis

### 1. Criar Transações

#### Descrição
Crie novas transações (receitas ou despesas) através de comandos em linguagem natural.

#### Exemplos de Comandos
```
"Registre uma despesa de R$ 150 no supermercado hoje"
"Crie uma receita de R$ 5000 como salário de dezembro"
"Adicione uma despesa de R$ 45 no Uber ontem"
"Registre um gasto de R$ 200 na farmácia"
```

#### Como Funciona
1. Usuário envia comando em linguagem natural
2. IA analisa e extrai informações:
   - Tipo (receita ou despesa)
   - Valor
   - Descrição
   - Data (usa hoje se não especificada)
   - Categoria (identifica automaticamente)
   - Conta (usa a primeira disponível se não especificada)
3. IA valida os dados
4. Cria a transação no banco de dados
5. Atualiza o saldo da conta automaticamente
6. Retorna confirmação com detalhes

#### Resposta da IA
```
✅ Transação registrada com sucesso!

Tipo: Despesa
Valor: R$ 150.00
Descrição: Supermercado
Data: 2025-12-01
```

---

### 2. Categorizar Transação Individual

#### Descrição
Atribua ou altere a categoria de uma transação específica.

#### Exemplos de Comandos
```
"Categorize a transação do Uber como transporte"
"Mude a categoria da compra no supermercado para alimentação"
"Classifique o pagamento da Netflix como entretenimento"
```

#### Como Funciona
1. Usuário especifica a transação e categoria desejada
2. IA identifica a transação pelos detalhes fornecidos
3. Localiza a categoria apropriada
4. Atualiza a transação no banco de dados
5. Retorna confirmação

#### Resposta da IA
```
✅ Categoria atualizada com sucesso!

A transação foi categorizada.
```

---

### 3. Categorização em Lote

#### Descrição
Categorize múltiplas transações de uma só vez, ideal para transações importadas sem categoria.

#### Exemplos de Comandos
```
"Categorize todas as minhas transações sem categoria"
"Organize todas as transações não categorizadas"
"Classifique as transações pendentes"
```

#### Como Funciona
1. Usuário solicita categorização em lote
2. IA busca transações sem categoria (até 20 por vez)
3. Para cada transação:
   - Analisa descrição e merchant
   - Identifica padrões
   - Sugere categoria apropriada
   - Considera contexto brasileiro
4. Aplica as categorizações
5. Retorna resumo com estatísticas

#### Resposta da IA
```
✅ Categorização em lote concluída!

Total: 15
Sucesso: 14
Falhas: 1

Detalhes:
- 5 transações categorizadas como Alimentação
- 3 transações categorizadas como Transporte
- 2 transações categorizadas como Saúde
- 4 transações categorizadas como Compras
```

---

## 🧠 Inteligência da IA

### Análise de Transações

#### Descrição
A IA analisa automaticamente as descrições das transações para sugerir categorias apropriadas.

#### Exemplos de Análise

| Descrição | Merchant | Categoria Sugerida | Confiança |
|-----------|----------|-------------------|-----------|
| Compra no Pão de Açúcar | Pão de Açúcar | Alimentação | 95% |
| Corrida de Uber | Uber | Transporte | 98% |
| Consulta médica | Dr. Silva | Saúde | 90% |
| Netflix mensal | Netflix | Entretenimento | 99% |
| Conta de luz | CEMIG | Utilidades | 97% |

#### Fatores Considerados
- **Palavras-chave**: Identifica termos específicos (supermercado, farmácia, etc.)
- **Merchant**: Reconhece estabelecimentos conhecidos
- **Valor**: Considera faixas de valores típicas
- **Contexto**: Entende padrões brasileiros
- **Histórico**: Aprende com categorizações anteriores

---

## 🔒 Segurança e Auditoria

### Registro de Operações

#### Todas as Ações São Registradas
Cada operação de escrita é registrada na tabela `ai_chat_logs` com:
- ✅ ID do usuário
- ✅ Mensagem enviada
- ✅ Resposta da IA
- ✅ Tipo de ação (read/write)
- ✅ Nível de permissão usado
- ✅ ID da transação criada (se aplicável)
- ✅ Dados acessados
- ✅ Timestamp

#### Visualização de Logs
1. Acesse **Administração de IA**
2. Vá para a aba **Logs de Conversas**
3. Veja todas as interações com a IA
4. Filtre por tipo de ação (read/write)
5. Identifique transações criadas pela IA

### Validações de Segurança

#### Verificação de Propriedade
- ✅ Transações só podem ser modificadas pelo proprietário
- ✅ Validação de user_id em todas as operações
- ✅ Isolamento de dados entre usuários

#### Validação de Dados
- ✅ Tipo de transação (income/expense)
- ✅ Valor positivo e numérico
- ✅ Data válida
- ✅ Conta e categoria existentes
- ✅ Campos obrigatórios preenchidos

#### Controle de Permissões
- ✅ Flag `can_write_transactions` controla acesso
- ✅ Administradores podem desabilitar a qualquer momento
- ✅ Permissões granulares por nível de acesso

---

## 🎮 Como Usar

### Passo a Passo

#### 1. Abrir o Assistente de IA
- Clique no botão flutuante no canto inferior direito
- Ícone: 💬 (balão de conversa)

#### 2. Verificar Permissões
- Veja a mensagem de boas-vindas
- Confirme: "⚠️ Permissões de escrita ativadas"

#### 3. Enviar Comando
- Digite seu comando em linguagem natural
- Seja específico sobre o que deseja
- Pressione Enter ou clique em Enviar

#### 4. Aguardar Processamento
- IA analisa seu comando
- Valida os dados
- Executa a operação
- Retorna confirmação

#### 5. Verificar Resultado
- Leia a resposta da IA
- Confirme os detalhes
- Verifique a transação criada/modificada

### Dicas de Uso

#### Seja Específico
❌ "Registre um gasto"
✅ "Registre uma despesa de R$ 150 no supermercado hoje"

#### Inclua Detalhes
❌ "Crie uma transação"
✅ "Crie uma receita de R$ 5000 como salário de dezembro na conta corrente"

#### Use Linguagem Natural
✅ "Adicione uma despesa de R$ 45 no Uber ontem"
✅ "Registre um gasto de R$ 200 na farmácia hoje"
✅ "Crie uma receita de R$ 3000 como freelance"

#### Para Categorização
✅ "Categorize todas as transações sem categoria"
✅ "Organize as transações não classificadas"
✅ "Classifique a compra do Uber como transporte"

---

## 📊 Exemplos Práticos

### Cenário 1: Registrar Despesa Diária

**Situação**: Você fez compras no supermercado e quer registrar rapidamente.

**Comando**:
```
"Registre uma despesa de R$ 237,50 no supermercado hoje"
```

**Resposta da IA**:
```
✅ Transação registrada com sucesso!

Tipo: Despesa
Valor: R$ 237.50
Descrição: Supermercado
Data: 2025-12-01
Categoria: Alimentação
Conta: Conta Corrente
```

**Resultado**:
- ✅ Transação criada
- ✅ Saldo da conta atualizado
- ✅ Categoria atribuída automaticamente
- ✅ Registrado no histórico

---

### Cenário 2: Categorizar Transações Importadas

**Situação**: Você importou um extrato bancário com 20 transações sem categoria.

**Comando**:
```
"Categorize todas as minhas transações sem categoria"
```

**Resposta da IA**:
```
✅ Categorização em lote concluída!

Total: 20
Sucesso: 19
Falhas: 1

Categorizações aplicadas:
- 7 transações → Alimentação (supermercados, restaurantes)
- 4 transações → Transporte (Uber, gasolina)
- 3 transações → Saúde (farmácia, consultas)
- 2 transações → Entretenimento (Netflix, cinema)
- 3 transações → Compras (lojas, e-commerce)

Nota: 1 transação não pôde ser categorizada automaticamente.
Descrição: "Transferência PIX"
```

**Resultado**:
- ✅ 19 transações categorizadas
- ✅ Análise inteligente de descrições
- ✅ Categorias apropriadas atribuídas
- ✅ Relatório detalhado fornecido

---

### Cenário 3: Registrar Receita Mensal

**Situação**: Você recebeu seu salário e quer registrar.

**Comando**:
```
"Crie uma receita de R$ 8.500 como salário de dezembro na conta corrente"
```

**Resposta da IA**:
```
✅ Transação registrada com sucesso!

Tipo: Receita
Valor: R$ 8,500.00
Descrição: Salário de dezembro
Data: 2025-12-01
Categoria: Salário
Conta: Conta Corrente

Seu saldo foi atualizado:
Saldo anterior: R$ 2,345.67
Novo saldo: R$ 10,845.67
```

**Resultado**:
- ✅ Receita registrada
- ✅ Saldo atualizado corretamente
- ✅ Categoria identificada
- ✅ Confirmação com detalhes

---

## ⚙️ Configuração Técnica

### Estrutura do Banco de Dados

#### Tabela: ai_configurations
```sql
- can_write_transactions: boolean (true)
- permission_level: text ('read_full')
- is_active: boolean (true)
```

#### Tabela: ai_chat_logs
```sql
- action_type: text ('read' ou 'write')
- created_transaction_id: uuid (referência à transação criada)
- data_accessed: jsonb (campos acessados)
```

### Edge Function

#### Funções Disponíveis
1. `createTransaction()` - Cria nova transação
2. `updateTransactionCategory()` - Atualiza categoria
3. `batchUpdateTransactions()` - Categorização em lote
4. `getUserFinancialData()` - Busca dados do usuário

#### Formato de Ação (JSON)

**Criar Transação**:
```json
{
  "action": "create_transaction",
  "transaction_data": {
    "type": "expense",
    "amount": 150.00,
    "date": "2025-12-01",
    "description": "Supermercado",
    "account_id": "uuid",
    "category_id": "uuid"
  },
  "confirmation_message": "Transação registrada com sucesso!"
}
```

**Categorizar**:
```json
{
  "action": "update_category",
  "transaction_id": "uuid",
  "category_id": "uuid",
  "confirmation_message": "Categoria atualizada!"
}
```

**Categorização em Lote**:
```json
{
  "action": "batch_categorize",
  "updates": [
    {"id": "uuid1", "category_id": "uuid_cat1"},
    {"id": "uuid2", "category_id": "uuid_cat2"}
  ],
  "confirmation_message": "Categorização concluída!"
}
```

---

## 🔧 Administração

### Habilitar/Desabilitar Permissões

#### Via Interface (Recomendado)
1. Acesse **Administração de IA**
2. Vá para a aba **Configurações**
3. Localize "Permitir Criação de Transações"
4. Use o switch para ativar/desativar
5. Clique em **Salvar Configuração**

#### Via Banco de Dados
```sql
-- Desabilitar
UPDATE ai_configurations 
SET can_write_transactions = false 
WHERE is_active = true;

-- Habilitar
UPDATE ai_configurations 
SET can_write_transactions = true 
WHERE is_active = true;
```

### Monitoramento

#### Verificar Logs
```sql
SELECT 
  created_at,
  action_type,
  message,
  response,
  created_transaction_id
FROM ai_chat_logs
WHERE action_type = 'write'
ORDER BY created_at DESC
LIMIT 50;
```

#### Estatísticas
```sql
SELECT 
  action_type,
  COUNT(*) as total,
  COUNT(created_transaction_id) as transactions_created
FROM ai_chat_logs
GROUP BY action_type;
```

---

## 🚨 Solução de Problemas

### Problema: IA não está criando transações

#### Verificações
1. ✅ Permissão de escrita está ativada?
2. ✅ Nível de acesso é `read_full`?
3. ✅ Comando está claro e específico?
4. ✅ Conta e categoria existem?

#### Solução
```sql
-- Verificar configuração
SELECT can_write_transactions, permission_level 
FROM ai_configurations 
WHERE is_active = true;

-- Habilitar se necessário
UPDATE ai_configurations 
SET 
  can_write_transactions = true,
  permission_level = 'read_full'
WHERE is_active = true;
```

---

### Problema: Categorização não funciona

#### Verificações
1. ✅ Existem transações sem categoria?
2. ✅ Categorias estão cadastradas?
3. ✅ Descrições das transações são claras?

#### Solução
```sql
-- Verificar transações sem categoria
SELECT COUNT(*) 
FROM transactions 
WHERE category_id IS NULL;

-- Verificar categorias disponíveis
SELECT id, name, type 
FROM categories 
WHERE user_id = 'seu_user_id';
```

---

### Problema: Erro ao criar transação

#### Mensagens Comuns
- "Dados obrigatórios faltando" → Especifique tipo, valor e data
- "Valor inválido" → Use números positivos
- "Conta não encontrada" → Verifique se a conta existe
- "Categoria não encontrada" → Cadastre a categoria primeiro

#### Solução
- Seja mais específico no comando
- Verifique se contas e categorias existem
- Use valores numéricos válidos
- Especifique datas no formato correto

---

## 📈 Métricas e Análise

### Estatísticas de Uso

#### Transações Criadas pela IA
```sql
SELECT 
  DATE(created_at) as data,
  COUNT(*) as total_criadas
FROM ai_chat_logs
WHERE action_type = 'write' 
  AND created_transaction_id IS NOT NULL
GROUP BY DATE(created_at)
ORDER BY data DESC;
```

#### Taxa de Sucesso
```sql
SELECT 
  COUNT(CASE WHEN created_transaction_id IS NOT NULL THEN 1 END) * 100.0 / COUNT(*) as taxa_sucesso
FROM ai_chat_logs
WHERE action_type = 'write';
```

#### Categorias Mais Usadas
```sql
SELECT 
  c.name,
  COUNT(*) as vezes_usada
FROM transactions t
JOIN categories c ON t.category_id = c.id
JOIN ai_chat_logs l ON l.created_transaction_id = t.id
GROUP BY c.name
ORDER BY vezes_usada DESC
LIMIT 10;
```

---

## 🎓 Melhores Práticas

### Para Usuários

#### 1. Seja Claro e Específico
- ✅ Inclua valor, descrição e data
- ✅ Especifique tipo (receita/despesa)
- ✅ Mencione conta se tiver múltiplas

#### 2. Verifique os Resultados
- ✅ Leia a confirmação da IA
- ✅ Confira os detalhes da transação
- ✅ Verifique o saldo atualizado

#### 3. Use Linguagem Natural
- ✅ Escreva como falaria
- ✅ Não precisa usar termos técnicos
- ✅ A IA entende contexto

### Para Administradores

#### 1. Monitore Regularmente
- ✅ Revise logs de operações de escrita
- ✅ Verifique transações criadas pela IA
- ✅ Analise taxa de sucesso

#### 2. Mantenha Categorias Organizadas
- ✅ Cadastre categorias comuns
- ✅ Use nomes descritivos
- ✅ Evite duplicatas

#### 3. Eduque os Usuários
- ✅ Compartilhe exemplos de comandos
- ✅ Explique capacidades da IA
- ✅ Forneça guia de uso

---

## 🔮 Próximas Funcionalidades

### Em Desenvolvimento
- [ ] Editar transações existentes
- [ ] Excluir transações
- [ ] Criar categorias automaticamente
- [ ] Sugestões proativas de categorização
- [ ] Análise de padrões de gastos
- [ ] Alertas inteligentes

### Planejado
- [ ] Criar contas e cartões
- [ ] Gerenciar orçamentos
- [ ] Configurar metas financeiras
- [ ] Agendar transações recorrentes
- [ ] Exportar relatórios

---

## 📞 Suporte

### Precisa de Ajuda?

#### Documentação
- 📖 Leia este guia completo
- 📖 Consulte RESUMO_CONFIGURACAO_IA.md
- 📖 Veja ACESSO_DADOS_IA.md

#### Logs e Debug
- 🔍 Verifique logs no painel de administração
- 🔍 Consulte console do navegador
- 🔍 Analise logs do Edge Function

#### Contato
- 💬 Use o chat de suporte
- 📧 Envie email para suporte
- 🐛 Reporte bugs no GitHub

---

**Data de Atualização**: 2025-12-01  
**Versão**: 2.0  
**Status**: ✅ Permissões de Escrita ATIVADAS  
**Idioma**: Português (Brasil)
