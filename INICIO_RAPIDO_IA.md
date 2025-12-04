# Início Rápido: Assistente de IA

## 🚀 Configuração em 5 Minutos

### Passo 1: Acesse o Painel de Administração (1 min)

1. Faça login como administrador
2. Clique em **"Admin IA"** no menu lateral
3. Ou acesse: `http://localhost:5173/ai-admin`

### Passo 2: Configure o Assistente (2 min)

Na aba **"Configuração"**:

1. **Modelo de IA**: Deixe `gemini-2.5-flash` (já selecionado)
2. **Nível de Permissão**: Escolha uma opção:
   - 🟢 **Leitura Agregada** (Recomendado para começar)
     - Mais seguro
     - Acessa apenas totais e estatísticas
   - 🟡 **Leitura Transacional** (Para análises detalhadas)
     - Acessa últimas 50 transações
     - Vê descrições e datas
   - 🔴 **Leitura Completa** (Use com cautela)
     - Acesso total aos dados
     - Apenas para análises profundas

3. **Permitir Criar Transações**: 
   - ❌ Deixe **DESATIVADO** por enquanto
   - ✅ Ative depois se quiser que a IA crie transações

4. Clique em **"Salvar Configuração"**

### Passo 3: Teste o Assistente (2 min)

1. **Abra o chat**
   - Clique no botão flutuante 💬 no canto inferior direito
   - Ele aparece em todas as páginas

2. **Envie uma mensagem de teste**
   ```
   Exemplos:
   - "Quanto gastei este mês?"
   - "Qual é meu saldo total?"
   - "Em qual categoria gasto mais?"
   ```

3. **Verifique a resposta**
   - O assistente deve responder em alguns segundos
   - A resposta deve estar relacionada aos seus dados

### Passo 4: Verifique os Logs

1. Volte para `/ai-admin`
2. Clique na aba **"Logs de Conversas"**
3. Veja sua conversa registrada
4. Confira quais dados foram acessados

## ✅ Pronto!

Seu assistente de IA está configurado e funcionando!

## 🎯 Próximos Passos

### Para Usuários Iniciantes

1. **Experimente perguntas simples:**
   - "Quanto gastei hoje?"
   - "Qual é meu saldo?"
   - "Mostre minhas despesas"

2. **Explore análises:**
   - "Qual categoria tem mais gastos?"
   - "Quanto economizei este mês?"
   - "Onde posso cortar gastos?"

### Para Usuários Avançados

1. **Ative permissões maiores:**
   - Mude para "Leitura Transacional"
   - Faça perguntas mais detalhadas
   - Analise padrões de consumo

2. **Ative criação de transações:**
   - Ative "Permitir Criar Transações"
   - Use comandos como: "Registre uma despesa de R$ 50 em alimentação"
   - Verifique os logs de auditoria

## 📊 Configurações Recomendadas

### Para Uso Pessoal

```
✅ Modelo: gemini-2.5-flash
✅ Permissão: Leitura Transacional
✅ Escrita: Ativada (após testar)
```

### Para Uso Empresarial

```
✅ Modelo: gemini-2.5-flash
✅ Permissão: Leitura Agregada
✅ Escrita: Desativada
✅ Revisar logs semanalmente
```

### Para Demonstração

```
✅ Modelo: gemini-2.5-flash
✅ Permissão: Leitura Agregada
✅ Escrita: Desativada
```

## 🔒 Dicas de Segurança

✅ **Faça:**
- Comece com permissões mínimas
- Revise os logs regularmente
- Teste antes de dar acesso aos usuários
- Eduque os usuários sobre o que compartilhar

❌ **Não faça:**
- Dar "Leitura Completa" sem necessidade
- Ignorar os logs de auditoria
- Compartilhar senhas ou PINs com a IA
- Ativar escrita sem testar antes

## 💡 Perguntas Frequentes

### P: Preciso de uma chave de API?
**R:** Não! A plataforma gerencia isso automaticamente.

### P: O assistente funciona offline?
**R:** Não, é necessário conexão com a internet.

### P: Posso mudar as configurações depois?
**R:** Sim! Você pode ajustar a qualquer momento em /ai-admin.

### P: Os dados são seguros?
**R:** Sim! Todas as conversas são registradas e auditadas. Você controla exatamente quais dados a IA pode acessar.

### P: Quanto custa usar o assistente?
**R:** O custo está incluído na plataforma. Não há cobranças adicionais.

## 📖 Documentação Completa

Para mais detalhes, consulte:
- **CONFIGURACAO_ASSISTENTE_IA.md** - Guia completo de configuração
- **PRD.md** - Documento de requisitos do produto

---

**Tempo total de configuração:** ~5 minutos  
**Dificuldade:** ⭐ Fácil  
**Status:** ✅ Pronto para usar
