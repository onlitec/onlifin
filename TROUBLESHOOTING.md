# Guia de Solução de Problemas

## 🔄 Problemas de Cache do Navegador

### Sintoma
Você ainda vê erros antigos no console mesmo após as correções terem sido aplicadas.

### Causa
O navegador está carregando versões antigas (em cache) dos arquivos JavaScript.

### Solução
Execute uma **atualização forçada** no navegador:

#### Chrome / Edge / Brave
- **Windows/Linux:** `Ctrl + Shift + R` ou `Ctrl + F5`
- **Mac:** `Cmd + Shift + R`

#### Firefox
- **Windows/Linux:** `Ctrl + Shift + R` ou `Ctrl + F5`
- **Mac:** `Cmd + Shift + R`

#### Safari
- **Mac:** `Cmd + Option + R`

### Alternativa
1. Abra as **Ferramentas do Desenvolvedor** (F12)
2. Clique com botão direito no ícone de **Recarregar**
3. Selecione **"Esvaziar cache e recarregar forçadamente"**

---

## ⚠️ Avisos que Podem Ser Ignorados

### 1. Dialog Overlay Ref Warning

```
Warning: Function components cannot be given refs.
Check the render method of `Primitive.div.SlotClone`.
at DialogOverlay
```

**O que é:** Aviso interno da biblioteca Radix UI (usada pelo shadcn/ui)

**Impacto:** Nenhum - a funcionalidade funciona perfeitamente

**Ação:** Pode ser ignorado com segurança

---

### 2. MobX Array Index Warning

```
[mobx.array] Attempt to read an array index (0) that is out of bounds (0).
```

**O que é:** Aviso de uma ferramenta de desenvolvimento (React DevTools)

**Impacto:** Nenhum - não afeta a aplicação

**Ação:** Pode ser ignorado com segurança

---

## 🔑 Configuração da Chave da API

### Onde Inserir a Chave da API?

A chave da API do modelo de IA deve ser configurada em **dois lugares**:

#### 1. Interface de Administração (Referência)
- Acesse: **IA Admin** → Aba **Configurações**
- Campo: **"Chave da API (Referência)"**
- **Nota:** Este campo é apenas para referência visual. A chave real deve ser configurada no Supabase.

#### 2. Variáveis de Ambiente do Supabase (Produção)

Para que a IA funcione em produção, configure a chave nas variáveis de ambiente:

1. Acesse o **Dashboard do Supabase**
2. Vá para **Project Settings** → **Edge Functions** → **Environment Variables**
3. Adicione uma nova variável:
   - **Nome:** `GEMINI_API_KEY`
   - **Valor:** Sua chave da API do Google Gemini
4. Salve e reimplante a Edge Function

---

## 🤖 Problemas com o Assistente de IA

### Erro: CORS Policy

```
Access to fetch at 'https://...supabase.co/functions/v1/ai-assistant' 
has been blocked by CORS policy
```

**Status:** ✅ **CORRIGIDO** na versão 2 da Edge Function

**Se ainda aparecer:**
1. Limpe o cache do navegador (veja instruções acima)
2. Aguarde 1-2 minutos para propagação da Edge Function
3. Recarregue a página

---

### Erro: 400 Bad Request

**Causa possível:** Chave da API não configurada ou inválida

**Solução:**
1. Verifique se a variável `GEMINI_API_KEY` está configurada no Supabase
2. Confirme que a chave é válida e tem permissões corretas
3. Reimplante a Edge Function após configurar a variável

---

### Erro: "error?.context?.text is not a function"

**Status:** ✅ **CORRIGIDO** no código

**Se ainda aparecer:**
- Limpe o cache do navegador
- A correção já está aplicada no arquivo `AIAssistant.tsx`

---

## 📊 Problemas com Dados

### Categorias não aparecem

**Verificação:**
```sql
SELECT * FROM categories WHERE is_system = true;
```

**Solução:** As 13 categorias do sistema devem estar presentes. Se não estiverem, execute a migration novamente.

---

### Transações não aparecem

**Verificação:**
1. Verifique se você está logado
2. Confirme que há transações cadastradas
3. Verifique os filtros de data

**Solução:**
- Limpe os filtros
- Cadastre uma transação de teste
- Verifique as permissões RLS no Supabase

---

## 🔐 Problemas de Autenticação

### Não consigo fazer login

**Verificações:**
1. Username está correto? (sem espaços, sem caracteres especiais além de underscore)
2. Senha está correta?
3. Usuário foi criado com sucesso?

**Solução:**
- Tente criar uma nova conta
- Verifique o console do navegador para erros
- Confirme que o Supabase Auth está ativo

---

### Primeiro usuário não virou admin

**Verificação:**
```sql
SELECT id, username, role FROM profiles ORDER BY created_at LIMIT 1;
```

**Solução:** O trigger deve definir automaticamente. Se não funcionou:
```sql
UPDATE profiles 
SET role = 'admin' 
WHERE id = (SELECT id FROM profiles ORDER BY created_at LIMIT 1);
```

---

## 🎨 Problemas de Interface

### Página em branco

**Causas possíveis:**
1. Erro de JavaScript não tratado
2. Componente com erro de renderização
3. Problema de rota

**Solução:**
1. Abra o console do navegador (F12)
2. Verifique erros em vermelho
3. Limpe o cache e recarregue
4. Verifique se a rota existe em `routes.tsx`

---

### Gráficos não aparecem

**Verificações:**
1. Há dados suficientes? (mínimo 1 transação)
2. As datas estão corretas?
3. As categorias estão vinculadas?

**Solução:**
- Cadastre pelo menos 3 transações
- Verifique se as transações têm categorias
- Confirme que as datas estão no período filtrado

---

## 🔧 Comandos Úteis

### Verificar erros de linting
```bash
npm run lint
```

### Limpar cache do npm
```bash
npm cache clean --force
```

### Reinstalar dependências
```bash
rm -rf node_modules package-lock.json
npm install
```

### Verificar logs do Supabase
1. Acesse o Dashboard do Supabase
2. Vá para **Logs** → **Edge Functions**
3. Filtre por `ai-assistant`

---

## 📝 Checklist de Verificação

Antes de reportar um problema, verifique:

- [ ] Limpei o cache do navegador
- [ ] Recarreguei a página com Ctrl+Shift+R
- [ ] Verifiquei o console do navegador (F12)
- [ ] Confirmei que estou logado
- [ ] Verifiquei se há dados cadastrados
- [ ] Testei em modo anônimo/privado
- [ ] Verifiquei a conexão com internet
- [ ] Confirmei que o Supabase está online

---

## 🆘 Suporte Adicional

### Logs do Navegador
Para reportar problemas, inclua:
1. Mensagens de erro do console (F12 → Console)
2. Erros de rede (F12 → Network)
3. Passos para reproduzir o problema

### Logs do Supabase
Para problemas com Edge Functions:
1. Dashboard do Supabase → Logs
2. Filtre por função específica
3. Copie os logs de erro

---

## ✅ Status das Correções

| Problema | Status | Versão |
|----------|--------|--------|
| Campo de chave da API | ✅ Implementado | Atual |
| Erro de ref no ScrollArea | ✅ Corrigido | Atual |
| Erro CORS | ✅ Corrigido | Edge Function v2 |
| Erro de tratamento de erro | ✅ Corrigido | Atual |
| Aviso do Dialog | ⚠️ Biblioteca externa | N/A |
| Aviso do MobX | ⚠️ DevTools | N/A |

---

**Última atualização:** 2025-01-31  
**Versão da plataforma:** 1.0.0  
**Versão da Edge Function:** 2
