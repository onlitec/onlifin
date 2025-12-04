# Guia de Configuração do Assistente de IA

## 🤖 Visão Geral

O Assistente de IA é um recurso poderoso da plataforma que permite aos usuários interagir com seus dados financeiros usando linguagem natural. O assistente pode:

- 💬 Responder perguntas sobre suas finanças
- 📊 Analisar gastos e fornecer insights
- 💡 Dar dicas de economia personalizadas
- 📝 Criar transações por comando de voz/texto
- 🎯 Ajudar no planejamento financeiro
- 📈 Gerar previsões e análises

## ✅ Status Atual

### O Que Já Está Implementado

✅ **Interface do Assistente**
- Botão flutuante no canto inferior direito de todas as páginas
- Chat interativo com histórico de conversas
- Design responsivo e intuitivo

✅ **Painel de Administração**
- Página dedicada em `/ai-admin`
- Configuração de modelo de IA
- Controles de permissão granulares
- Visualização de logs de conversas
- Auditoria de acesso aos dados

✅ **Edge Function Implantada**
- Função `ai-assistant` deployada no Supabase
- Integração com API do Gemini 2.5 Flash
- Processamento seguro de mensagens
- Registro automático de logs

✅ **Banco de Dados**
- Tabela `ai_configurations` para configurações
- Tabela `ai_chat_logs` para histórico
- Políticas de segurança (RLS) configuradas
- Índices para performance otimizada

## 🔧 Como Configurar o Assistente

### Passo 1: Acessar o Painel de Administração

1. **Faça login como administrador**
   - Use as credenciais de admin da plataforma
   - Email: admin@financeiro.com
   - Senha: admin123 (ou a senha que você configurou)

2. **Navegue até o painel de IA**
   - No menu lateral, clique em "Admin IA"
   - Ou acesse diretamente: `http://localhost:5173/ai-admin`

### Passo 2: Configurar o Modelo de IA

Na aba **"Configuração"**, você verá os seguintes campos:

#### 1. Modelo de IA

```
Campo: Modelo de IA
Opções disponíveis:
- gemini-2.5-flash (Recomendado - Rápido e eficiente)
- gemini-2.5-pro (Mais avançado, respostas mais detalhadas)
- gpt-4 (OpenAI - Requer configuração adicional)
- gpt-3.5-turbo (OpenAI - Mais rápido)
```

**Recomendação:** Use `gemini-2.5-flash` para melhor custo-benefício.

#### 2. Endpoint da API

```
Campo: Endpoint da API
Valor padrão: (Gerenciado automaticamente)
```

⚠️ **Importante:** O endpoint é gerenciado automaticamente pela plataforma. Não é necessário alterar este campo a menos que você esteja usando uma API customizada.

**Endpoint atual:**
```
https://api-integrations.appmedo.com/app-7xkeeoe4bsap/api-rLob8RdzAOl9/v1beta/models/gemini-2.5-flash:streamGenerateContent?alt=sse
```

#### 3. Chave da API

```
Campo: Chave da API (API Key)
Tipo: Senha (oculta)
```

⚠️ **CRÍTICO:** A chave da API é gerenciada de forma segura pela plataforma através do sistema de integração. Você **NÃO** precisa inserir uma chave manualmente.

**Como funciona:**
- A plataforma usa um sistema de proxy seguro
- As credenciais são gerenciadas no backend
- Nenhuma chave é exposta no frontend
- Tudo é configurado automaticamente

### Passo 3: Configurar Permissões de Acesso

Esta é a parte mais importante da configuração. Você controla exatamente quais dados o assistente pode acessar.

#### Níveis de Permissão

##### 1. **Leitura Agregada** (Padrão - Recomendado)

```
Nível: read_aggregated
Segurança: ⭐⭐⭐⭐⭐ (Mais seguro)
```

**O que o assistente pode ver:**
- ✅ Totais e somatórios (saldo total, receitas totais, despesas totais)
- ✅ Estatísticas agregadas (média de gastos, número de transações)
- ✅ Despesas agrupadas por categoria
- ✅ Número de contas e cartões
- ✅ Lista de categorias disponíveis

**O que o assistente NÃO pode ver:**
- ❌ Transações individuais
- ❌ Descrições de transações
- ❌ Datas específicas de transações
- ❌ Números de conta ou cartão
- ❌ Informações bancárias detalhadas

**Casos de uso:**
- Análise geral de gastos
- Dicas de economia baseadas em padrões
- Planejamento de orçamento
- Perguntas sobre totais e médias

**Exemplo de perguntas:**
- "Quanto gastei este mês?"
- "Qual categoria tem mais despesas?"
- "Qual é meu saldo total?"
- "Quanto economizei comparado ao mês passado?"

##### 2. **Leitura Transacional**

```
Nível: read_transactional
Segurança: ⭐⭐⭐ (Moderado)
```

**O que o assistente pode ver:**
- ✅ Tudo do nível agregado
- ✅ Lista das últimas 50 transações
- ✅ Descrições de transações
- ✅ Datas das transações
- ✅ Valores individuais
- ✅ Categorias de cada transação

**O que o assistente NÃO pode ver:**
- ❌ Números de conta completos
- ❌ Números de cartão
- ❌ Informações bancárias sensíveis
- ❌ Histórico completo (apenas últimas 50)

**Casos de uso:**
- Análise detalhada de gastos
- Identificação de padrões de consumo
- Categorização automática de transações
- Recomendações personalizadas

**Exemplo de perguntas:**
- "Quais foram minhas últimas compras no supermercado?"
- "Quanto gastei em restaurantes esta semana?"
- "Mostre minhas despesas de transporte"
- "Quais transações foram acima de R$ 100?"

##### 3. **Leitura Completa**

```
Nível: read_full
Segurança: ⭐ (Menos seguro - Use com cautela)
```

**O que o assistente pode ver:**
- ✅ Tudo dos níveis anteriores
- ✅ Histórico completo de transações
- ✅ Todas as contas com detalhes
- ✅ Todos os cartões com limites
- ✅ Informações completas de categorias
- ✅ Dados de recorrência e parcelamento

**Casos de uso:**
- Análise financeira profunda
- Auditoria completa
- Planejamento financeiro avançado
- Relatórios detalhados

**⚠️ Atenção:** Use este nível apenas se você confia completamente no modelo de IA e entende os riscos de privacidade.

#### Permissão de Escrita

```
Campo: Permitir Criar Transações
Tipo: Switch (Ativado/Desativado)
Padrão: Desativado
```

**Quando ativado:**
- ✅ O assistente pode criar transações por comando
- ✅ Registra automaticamente no sistema
- ✅ Atualiza saldos das contas
- ✅ Todas as criações são auditadas

**Exemplo de comandos:**
- "Registre uma despesa de R$ 50 em alimentação"
- "Crie uma receita de R$ 1000 de salário"
- "Adicione um gasto de R$ 30 em transporte hoje"

**⚠️ Importante:**
- Todas as transações criadas são registradas nos logs
- Você pode revisar e excluir transações criadas pela IA
- A IA sempre confirma antes de criar (no futuro)

### Passo 4: Salvar Configuração

1. **Revise todas as configurações**
   - Modelo selecionado
   - Nível de permissão
   - Permissão de escrita

2. **Clique em "Salvar Configuração"**
   - A configuração será salva no banco de dados
   - Uma mensagem de sucesso será exibida
   - As mudanças entram em vigor imediatamente

3. **Teste o assistente**
   - Clique no botão flutuante de chat
   - Envie uma mensagem de teste
   - Verifique se a resposta está adequada

## 📊 Monitoramento e Auditoria

### Aba "Logs de Conversas"

Esta aba mostra todo o histórico de interações com o assistente.

#### Informações Exibidas

Para cada conversa, você verá:

```
┌─────────────────────────────────────────────────────────┐
│ 👤 Usuário: João Silva                                  │
│ 📅 Data: 01/12/2024 às 14:30                           │
│ 🔒 Permissão: Leitura Agregada                         │
│ ⚡ Ação: Leitura                                        │
│                                                         │
│ 💬 Mensagem:                                            │
│ "Quanto gastei este mês?"                              │
│                                                         │
│ 🤖 Resposta:                                            │
│ "Você gastou R$ 2.450,00 este mês. Suas principais    │
│  categorias de despesa foram: Alimentação (R$ 800),   │
│  Transporte (R$ 500) e Lazer (R$ 350)."               │
│                                                         │
│ 📋 Dados Acessados:                                     │
│ - total_balance                                         │
│ - total_expense                                         │
│ - expenses_by_category                                  │
└─────────────────────────────────────────────────────────┘
```

#### Tipos de Ação

- **🔍 Leitura (read)**: Assistente apenas consultou dados
- **✏️ Escrita (write)**: Assistente criou uma transação
- **❌ Erro (error)**: Ocorreu um erro na operação

#### Filtros e Busca

Você pode filtrar logs por:
- 📅 Data (últimas 24h, 7 dias, 30 dias, todos)
- 👤 Usuário específico
- ⚡ Tipo de ação (leitura, escrita)
- 🔒 Nível de permissão usado

### Exportar Logs

```
Botão: Exportar Logs
Formato: CSV
```

**O que é exportado:**
- Data e hora da conversa
- ID do usuário
- Mensagem enviada
- Resposta do assistente
- Nível de permissão usado
- Tipo de ação realizada
- Dados acessados
- ID de transação criada (se aplicável)

**Casos de uso:**
- Auditoria de conformidade
- Análise de uso do assistente
- Identificação de padrões
- Relatórios para stakeholders

## 🎯 Casos de Uso Práticos

### Caso 1: Análise Básica de Gastos

**Configuração:**
- Nível: Leitura Agregada
- Escrita: Desativada

**Perguntas que funcionam:**
- "Quanto gastei este mês?"
- "Qual é meu saldo total?"
- "Em qual categoria gasto mais?"
- "Quanto economizei comparado ao mês passado?"

### Caso 2: Análise Detalhada com Histórico

**Configuração:**
- Nível: Leitura Transacional
- Escrita: Desativada

**Perguntas que funcionam:**
- "Mostre minhas últimas compras no supermercado"
- "Quais foram meus gastos em restaurantes esta semana?"
- "Liste todas as despesas acima de R$ 100"
- "Quando foi minha última compra de combustível?"

### Caso 3: Assistente Completo com Criação

**Configuração:**
- Nível: Leitura Transacional (ou Completa)
- Escrita: Ativada

**Comandos que funcionam:**
- "Registre uma despesa de R$ 50 em alimentação"
- "Crie uma receita de R$ 1000 de salário hoje"
- "Adicione um gasto de R$ 30 em transporte"
- "Registre que recebi R$ 200 de freelance"

## 🔒 Segurança e Privacidade

### Boas Práticas

#### 1. Princípio do Menor Privilégio

✅ **Recomendado:**
- Comece com "Leitura Agregada"
- Aumente permissões apenas se necessário
- Revise regularmente os logs

❌ **Evite:**
- Dar "Leitura Completa" por padrão
- Ativar escrita sem necessidade
- Ignorar os logs de auditoria

#### 2. Monitoramento Regular

📅 **Semanalmente:**
- Revise os logs de conversas
- Verifique se há uso indevido
- Identifique padrões anormais

📅 **Mensalmente:**
- Exporte logs para análise
- Revise permissões configuradas
- Ajuste configurações se necessário

#### 3. Educação dos Usuários

📚 **Oriente os usuários sobre:**
- O que o assistente pode fazer
- Quais dados ele pode acessar
- Como usar de forma segura
- Quando NÃO usar o assistente

### Dados Sensíveis

⚠️ **Nunca compartilhe com o assistente:**
- Senhas ou PINs
- Números completos de cartão
- Códigos de segurança (CVV)
- Senhas bancárias
- Tokens de autenticação

✅ **Seguro para compartilhar:**
- Valores de transações
- Categorias de gastos
- Datas de transações
- Descrições gerais
- Perguntas sobre análises

## 🛠️ Solução de Problemas

### Problema 1: Assistente não responde

**Sintomas:**
- Botão de chat não aparece
- Mensagens não são enviadas
- Erro ao abrir o chat

**Soluções:**

1. **Verifique se está logado**
   ```
   - Faça logout e login novamente
   - Verifique se sua sessão não expirou
   ```

2. **Verifique a configuração**
   ```
   - Acesse /ai-admin
   - Confirme que há uma configuração salva
   - Verifique se o modelo está selecionado
   ```

3. **Verifique o console do navegador**
   ```
   - Pressione F12
   - Vá para a aba "Console"
   - Procure por erros em vermelho
   ```

### Problema 2: Respostas genéricas ou incorretas

**Sintomas:**
- Assistente não acessa seus dados
- Respostas muito genéricas
- Não reconhece suas transações

**Soluções:**

1. **Verifique o nível de permissão**
   ```
   - Acesse /ai-admin
   - Verifique se o nível de permissão está adequado
   - Para análises detalhadas, use "Leitura Transacional"
   ```

2. **Verifique se há dados no sistema**
   ```
   - Acesse "Transações"
   - Confirme que há transações cadastradas
   - Verifique se as contas têm saldo
   ```

3. **Reformule a pergunta**
   ```
   ❌ "Como estão minhas finanças?"
   ✅ "Quanto gastei este mês em alimentação?"
   
   ❌ "Me ajude"
   ✅ "Mostre minhas últimas 5 despesas"
   ```

### Problema 3: Assistente não cria transações

**Sintomas:**
- Comando de criação não funciona
- Erro ao tentar criar transação
- Transação não aparece na lista

**Soluções:**

1. **Verifique a permissão de escrita**
   ```
   - Acesse /ai-admin
   - Aba "Configuração"
   - Verifique se "Permitir Criar Transações" está ATIVADO
   ```

2. **Forneça todos os dados necessários**
   ```
   ❌ "Registre uma despesa"
   ✅ "Registre uma despesa de R$ 50 em alimentação hoje"
   
   Dados obrigatórios:
   - Tipo (receita ou despesa)
   - Valor (em reais)
   - Categoria
   - Data (ou "hoje")
   ```

3. **Verifique se há contas cadastradas**
   ```
   - Acesse "Contas Bancárias"
   - Confirme que há pelo menos uma conta
   - O assistente precisa de uma conta para criar transações
   ```

### Problema 4: Erro "Não autorizado"

**Sintomas:**
- Mensagem de erro ao enviar mensagem
- "Não autorizado" ou "Unauthorized"
- Chat não funciona

**Soluções:**

1. **Faça logout e login novamente**
   ```
   - Clique no menu do usuário
   - Selecione "Sair"
   - Faça login novamente
   ```

2. **Limpe o cache do navegador**
   ```
   - Pressione Ctrl+Shift+Delete
   - Selecione "Cookies e dados de sites"
   - Clique em "Limpar dados"
   - Recarregue a página
   ```

3. **Verifique as permissões do usuário**
   ```
   - Usuários comuns podem usar o assistente
   - Apenas admins podem configurar
   - Verifique se seu perfil tem as permissões corretas
   ```

## 📈 Otimização e Performance

### Dicas para Respostas Mais Rápidas

1. **Use perguntas específicas**
   ```
   ✅ Rápido: "Quanto gastei em alimentação este mês?"
   ❌ Lento: "Me conte tudo sobre minhas finanças"
   ```

2. **Limite o escopo temporal**
   ```
   ✅ Rápido: "Despesas desta semana"
   ❌ Lento: "Todas as despesas desde sempre"
   ```

3. **Use o nível de permissão adequado**
   ```
   ✅ Rápido: Leitura Agregada (para totais)
   ❌ Lento: Leitura Completa (quando não necessário)
   ```

### Monitoramento de Uso

**Métricas importantes:**
- 📊 Número de conversas por dia
- ⏱️ Tempo médio de resposta
- ✅ Taxa de sucesso das respostas
- 📝 Número de transações criadas

**Como acessar:**
- Vá para /ai-admin
- Aba "Logs de Conversas"
- Analise os padrões de uso

## 🎓 Exemplos de Perguntas

### Análise de Gastos

```
✅ "Quanto gastei este mês?"
✅ "Qual categoria tem mais despesas?"
✅ "Quanto gastei em alimentação nos últimos 30 dias?"
✅ "Qual foi minha maior despesa?"
✅ "Quanto economizei comparado ao mês passado?"
```

### Planejamento Financeiro

```
✅ "Qual é meu saldo disponível?"
✅ "Quanto posso gastar ainda este mês?"
✅ "Estou dentro do orçamento?"
✅ "Quanto preciso economizar para atingir R$ 5000?"
✅ "Qual é minha média de gastos mensal?"
```

### Criação de Transações (se escrita ativada)

```
✅ "Registre uma despesa de R$ 50 em alimentação"
✅ "Crie uma receita de R$ 1000 de salário hoje"
✅ "Adicione um gasto de R$ 30 em transporte"
✅ "Registre que recebi R$ 200 de freelance ontem"
✅ "Anote uma despesa de R$ 100 em lazer"
```

### Análise de Padrões

```
✅ "Quais são meus gastos recorrentes?"
✅ "Em que dia do mês gasto mais?"
✅ "Qual categoria está crescendo?"
✅ "Onde posso economizar?"
✅ "Quais são meus hábitos de consumo?"
```

## 📝 Checklist de Configuração

Use este checklist para garantir que tudo está configurado corretamente:

### Configuração Inicial

- [ ] Acessei o painel /ai-admin como administrador
- [ ] Selecionei o modelo de IA (gemini-2.5-flash recomendado)
- [ ] Configurei o nível de permissão adequado
- [ ] Decidi se vou ativar permissão de escrita
- [ ] Salvei a configuração
- [ ] Recebi mensagem de sucesso

### Teste do Assistente

- [ ] Botão flutuante aparece no canto inferior direito
- [ ] Consigo abrir o chat clicando no botão
- [ ] Enviei uma mensagem de teste
- [ ] Recebi uma resposta do assistente
- [ ] A resposta está relacionada aos meus dados

### Verificação de Logs

- [ ] Acessei a aba "Logs de Conversas"
- [ ] Vejo minha conversa de teste registrada
- [ ] As informações estão corretas (data, usuário, permissão)
- [ ] Consigo ver quais dados foram acessados

### Segurança

- [ ] Revisei o nível de permissão escolhido
- [ ] Entendo quais dados o assistente pode acessar
- [ ] Configurei permissão de escrita apenas se necessário
- [ ] Planejei revisar os logs regularmente

## 🚀 Próximos Passos

Após configurar o assistente, você pode:

1. **Educar os usuários**
   - Compartilhe exemplos de perguntas
   - Explique o que o assistente pode fazer
   - Oriente sobre segurança e privacidade

2. **Monitorar o uso**
   - Revise os logs semanalmente
   - Identifique perguntas comuns
   - Ajuste permissões se necessário

3. **Otimizar a experiência**
   - Colete feedback dos usuários
   - Ajuste o nível de permissão
   - Considere ativar/desativar escrita

4. **Expandir funcionalidades**
   - Integre com mais fontes de dados
   - Adicione novos tipos de análise
   - Personalize prompts do assistente

## 📞 Suporte

Se você encontrar problemas não cobertos neste guia:

1. **Verifique os logs do navegador** (F12 → Console)
2. **Revise os logs de conversas** (/ai-admin → Logs)
3. **Consulte a documentação técnica** (para desenvolvedores)
4. **Entre em contato com o suporte** (se disponível)

---

**Última atualização:** 2025-12-01  
**Versão:** 1.0.0  
**Status:** ✅ Assistente configurado e pronto para uso
