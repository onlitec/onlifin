# 🧪 Guia de Teste - IA com Acesso aos Dados

## 📋 Pré-requisitos

Antes de testar, certifique-se de que:
- ✅ Você está logado na aplicação
- ✅ Você tem pelo menos uma conta cadastrada
- ✅ Você tem algumas transações cadastradas
- ✅ A Edge Function `ai-assistant` foi deployada (versão 3)

---

## 🚀 Passo a Passo para Testar

### 1. Preparar Dados de Teste

Se você ainda não tem dados, cadastre alguns exemplos:

#### Criar uma Conta
1. Vá em **Contas** → **Nova Conta**
2. Preencha:
   - Nome: "Conta Corrente"
   - Banco: "Banco do Brasil"
   - Saldo Inicial: R$ 5.000,00
3. Salve

#### Criar Transações
1. Vá em **Transações** → **Nova Transação**
2. Crie algumas despesas:
   - R$ 150,00 - Supermercado - Categoria: Alimentação
   - R$ 80,00 - Uber - Categoria: Transporte
   - R$ 45,00 - Cinema - Categoria: Lazer
3. Crie uma receita:
   - R$ 3.000,00 - Salário - Categoria: Salário

---

### 2. Verificar Configuração da IA

1. **Login como Admin**
   - Usuário: `admin`
   - Senha: `*M3a74g20M`

2. **Acessar Painel de IA**
   - Menu Admin → **Administração de IA**

3. **Verificar Configuração**
   - Aba: **Configurações**
   - Modelo: `gemini-2.5-flash`
   - Nível de Permissão: `read_aggregated` (padrão)
   - Status: Ativo ✅

---

### 3. Testar com Nível `read_aggregated`

Este é o nível mais seguro, que envia apenas estatísticas agregadas.

#### Abrir o Chat da IA
1. Clique no botão flutuante de chat (canto inferior direito) 💬
2. O chat deve abrir com a mensagem de boas-vindas

#### Perguntas para Testar

**Teste 1: Saldo Total**
```
Pergunta: "Qual é meu saldo total?"
Resposta Esperada: "Seu saldo total é R$ 5.000,00 distribuído em 1 conta."
```

**Teste 2: Gastos do Mês**
```
Pergunta: "Quanto gastei este mês?"
Resposta Esperada: "Você gastou R$ 275,00 este mês."
```

**Teste 3: Categoria com Mais Gastos**
```
Pergunta: "Em que categoria gasto mais?"
Resposta Esperada: "Sua maior categoria de gastos é Alimentação com R$ 150,00."
```

**Teste 4: Análise Geral**
```
Pergunta: "Faça uma análise das minhas finanças"
Resposta Esperada: A IA deve mencionar:
- Total de receitas: R$ 3.000,00
- Total de despesas: R$ 275,00
- Saldo líquido: R$ 2.725,00
- Sugestões de economia
```

---

### 4. Testar com Nível `read_transactional`

Este nível permite que a IA veja transações individuais.

#### Alterar Nível de Permissão
1. Vá em **Administração de IA** → **Configurações**
2. Altere **Nível de Permissão** para: `read_transactional`
3. Clique em **Salvar Configuração**
4. Aguarde confirmação de sucesso ✅

#### Perguntas para Testar

**Teste 1: Últimas Compras**
```
Pergunta: "Quais foram minhas últimas compras?"
Resposta Esperada: A IA deve listar as transações:
- R$ 150,00 no Supermercado
- R$ 80,00 em Uber
- R$ 45,00 no Cinema
```

**Teste 2: Transações por Categoria**
```
Pergunta: "Mostre minhas despesas de alimentação"
Resposta Esperada: "Você tem R$ 150,00 em despesas de alimentação, sendo R$ 150,00 no Supermercado."
```

**Teste 3: Padrões de Gastos**
```
Pergunta: "Identifique padrões nos meus gastos"
Resposta Esperada: A IA deve analisar as transações e identificar:
- Frequência de gastos
- Categorias mais usadas
- Possíveis gastos recorrentes
```

---

### 5. Testar com Nível `read_full`

⚠️ **ATENÇÃO**: Este nível dá acesso completo a todos os dados. Use apenas para testes.

#### Alterar Nível de Permissão
1. Vá em **Administração de IA** → **Configurações**
2. Altere **Nível de Permissão** para: `read_full`
3. Clique em **Salvar Configuração**
4. Confirme que entende os riscos

#### Perguntas para Testar

**Teste 1: Análise Completa**
```
Pergunta: "Faça uma análise financeira completa"
Resposta Esperada: A IA deve fornecer:
- Análise detalhada de todas as transações
- Padrões de gastos ao longo do tempo
- Recomendações personalizadas
- Previsões de fluxo de caixa
```

**Teste 2: Todas as Transações**
```
Pergunta: "Liste todas as minhas transações"
Resposta Esperada: A IA deve listar TODAS as transações cadastradas, sem limite.
```

---

### 6. Verificar Logs de Auditoria

Após testar, verifique se os logs estão sendo registrados corretamente:

1. Vá em **Administração de IA** → **Logs de Chat**
2. Você deve ver todas as conversas registradas
3. Para cada log, verifique:
   - ✅ Mensagem do usuário
   - ✅ Resposta da IA
   - ✅ Nível de permissão usado
   - ✅ Data e hora
   - ✅ Dados acessados (campos)

---

## ✅ Checklist de Validação

Marque cada item após testar:

### Funcionalidade Básica
- [ ] Chat abre ao clicar no botão flutuante
- [ ] Mensagens são enviadas corretamente
- [ ] IA responde em português
- [ ] Respostas são relevantes ao contexto

### Acesso aos Dados
- [ ] Com `read_aggregated`: IA menciona totais e estatísticas
- [ ] Com `read_transactional`: IA lista transações específicas
- [ ] Com `read_full`: IA tem acesso completo aos dados
- [ ] IA usa dados reais do usuário nas respostas

### Configuração
- [ ] Nível de permissão pode ser alterado no painel admin
- [ ] Mudanças de configuração são aplicadas imediatamente
- [ ] Configuração é salva corretamente no banco

### Auditoria
- [ ] Todas as conversas são registradas em `ai_chat_logs`
- [ ] Logs incluem nível de permissão usado
- [ ] Logs incluem campos de dados acessados
- [ ] Logs podem ser visualizados no painel admin

### Segurança
- [ ] Dados sensíveis não são expostos em `read_aggregated`
- [ ] Apenas últimas 50 transações em `read_transactional`
- [ ] `read_full` requer confirmação explícita
- [ ] Logs não expõem dados sensíveis na UI

---

## 🐛 Problemas Comuns

### Problema: IA responde mas não menciona dados do usuário

**Causa**: Nível de permissão muito restritivo ou sem dados cadastrados

**Solução**:
1. Verifique se você tem contas e transações cadastradas
2. Tente alterar para `read_transactional`
3. Faça uma pergunta específica: "Qual é meu saldo total?"

---

### Problema: Erro 500 ao enviar mensagem

**Causa**: Edge Function não consegue acessar o banco de dados

**Solução**:
1. Verifique se a Edge Function foi deployada (versão 3)
2. Verifique se `SUPABASE_URL` e `SUPABASE_SERVICE_ROLE_KEY` estão configurados
3. Verifique os logs da Edge Function no Supabase Dashboard

---

### Problema: IA responde com dados genéricos

**Causa**: Configuração de IA não está ativa ou não existe

**Solução**:
1. Vá em **Administração de IA** → **Configurações**
2. Verifique se existe uma configuração
3. Se não existir, crie uma nova com:
   - Modelo: `gemini-2.5-flash`
   - Nível: `read_aggregated`
4. Salve e teste novamente

---

## 📊 Exemplos de Respostas Esperadas

### Com `read_aggregated`:
```
Usuário: "Como estão minhas finanças?"

IA: "Suas finanças estão em boa situação! Você tem:
- Saldo total: R$ 5.000,00 em 1 conta
- Receitas: R$ 3.000,00
- Despesas: R$ 275,00
- Saldo líquido: R$ 2.725,00

Você está economizando 90,8% da sua receita, o que é excelente! 
Continue assim e considere investir parte desse saldo."
```

### Com `read_transactional`:
```
Usuário: "Onde estou gastando mais?"

IA: "Analisando suas últimas transações, você está gastando mais em:

1. Alimentação: R$ 150,00 (54,5%)
   - Supermercado: R$ 150,00

2. Transporte: R$ 80,00 (29,1%)
   - Uber: R$ 80,00

3. Lazer: R$ 45,00 (16,4%)
   - Cinema: R$ 45,00

Dica: Considere usar transporte público para reduzir gastos com Uber."
```

---

## 🎯 Resultado Esperado

Após completar todos os testes, você deve ter:

1. ✅ IA respondendo com dados reais do usuário
2. ✅ Três níveis de permissão funcionando corretamente
3. ✅ Logs de auditoria registrando todas as interações
4. ✅ Configuração flexível no painel admin
5. ✅ Respostas personalizadas e relevantes

---

## 📞 Suporte

Se encontrar problemas:
1. Verifique a documentação em `ACESSO_DADOS_IA.md`
2. Verifique os logs da Edge Function no Supabase Dashboard
3. Verifique os logs do navegador (F12 → Console)
4. Verifique se todas as tabelas existem no banco de dados

---

**Última atualização**: 01/12/2025  
**Versão da Edge Function**: 3  
**Status**: ✅ Funcional
