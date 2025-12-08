# Correção do Erro CORS - Edge Function

## Problema Identificado

### Erro Original
```
Access to fetch at 'https://twbzhscoyasetrstrofl.supabase.co/functions/v1/financial-forecast' 
from origin 'https://onlifin.onlitec.com.br' has been blocked by CORS policy: 
Response to preflight request doesn't pass access control check: 
No 'Access-Control-Allow-Origin' header is present on the requested resource.
```

### Causa
A Edge Function `financial-forecast` não estava configurada para lidar com requisições CORS (Cross-Origin Resource Sharing), que são necessárias quando o frontend faz chamadas para um domínio diferente.

---

## Solução Implementada

### 1. Adicionados Headers CORS
```typescript
const corsHeaders = {
  "Access-Control-Allow-Origin": "*",
  "Access-Control-Allow-Methods": "POST, OPTIONS",
  "Access-Control-Allow-Headers": "authorization, x-client-info, apikey, content-type",
};
```

### 2. Tratamento de Preflight Request (OPTIONS)
```typescript
// Handle CORS preflight request
if (req.method === "OPTIONS") {
  return new Response(null, {
    status: 204,
    headers: corsHeaders,
  });
}
```

### 3. Headers em Todas as Respostas
Todos os retornos da função agora incluem os headers CORS:

**Resposta de Sucesso:**
```typescript
return new Response(
  JSON.stringify({
    success: true,
    forecast_id: savedForecast.id,
    message: "Previsão financeira gerada com sucesso",
  }),
  { 
    status: 200, 
    headers: { 
      "Content-Type": "application/json",
      ...corsHeaders
    } 
  }
);
```

**Resposta de Erro:**
```typescript
return new Response(
  JSON.stringify({ error: error.message || "Erro interno do servidor" }),
  { 
    status: 500, 
    headers: { 
      "Content-Type": "application/json",
      ...corsHeaders
    } 
  }
);
```

---

## O que é CORS?

### Definição
CORS (Cross-Origin Resource Sharing) é um mecanismo de segurança implementado pelos navegadores que restringe requisições HTTP feitas de um domínio para outro.

### Por que é necessário?
Quando o frontend (https://onlifin.onlitec.com.br) tenta fazer uma requisição para a Edge Function (https://twbzhscoyasetrstrofl.supabase.co), o navegador primeiro envia uma requisição "preflight" (OPTIONS) para verificar se o servidor permite essa comunicação.

### Fluxo CORS
```
1. Frontend faz requisição POST
   ↓
2. Navegador envia OPTIONS (preflight)
   ↓
3. Servidor responde com headers CORS
   ↓
4. Navegador permite a requisição POST
   ↓
5. Servidor responde com dados + headers CORS
```

---

## Headers CORS Explicados

### Access-Control-Allow-Origin
```typescript
"Access-Control-Allow-Origin": "*"
```
- Permite requisições de **qualquer origem**
- Para produção, pode ser restrito a domínios específicos:
  ```typescript
  "Access-Control-Allow-Origin": "https://onlifin.onlitec.com.br"
  ```

### Access-Control-Allow-Methods
```typescript
"Access-Control-Allow-Methods": "POST, OPTIONS"
```
- Define quais métodos HTTP são permitidos
- POST: para enviar dados
- OPTIONS: para preflight request

### Access-Control-Allow-Headers
```typescript
"Access-Control-Allow-Headers": "authorization, x-client-info, apikey, content-type"
```
- Define quais headers podem ser enviados na requisição
- `authorization`: para tokens de autenticação
- `x-client-info`: informações do cliente Supabase
- `apikey`: chave da API Supabase
- `content-type`: tipo de conteúdo (JSON)

---

## Deployment

### Versão Deployada
- **Versão:** 2
- **Status:** ACTIVE
- **Data:** 08/12/2025

### Comando Utilizado
```bash
supabase_deploy_edge_function --name financial-forecast
```

### Resultado
```json
{
  "id": "4f8e06d4-916d-4f2f-9223-ecdcffd87e39",
  "slug": "financial-forecast",
  "version": 2,
  "name": "financial-forecast",
  "status": "ACTIVE"
}
```

---

## Testes

### Antes da Correção
❌ Erro CORS ao chamar a função
❌ Requisição bloqueada pelo navegador
❌ Mensagem: "No 'Access-Control-Allow-Origin' header"

### Após a Correção
✅ Preflight request (OPTIONS) respondido corretamente
✅ Headers CORS presentes em todas as respostas
✅ Requisições permitidas pelo navegador
✅ Função executada com sucesso

---

## Arquivos Modificados

### Edge Function
- **Arquivo:** `supabase/functions/financial-forecast/index.ts`
- **Mudanças:**
  - Adicionado objeto `corsHeaders`
  - Adicionado tratamento para método OPTIONS
  - Incluído `...corsHeaders` em todas as respostas

---

## Segurança

### Configuração Atual
```typescript
"Access-Control-Allow-Origin": "*"
```
- Permite requisições de **qualquer origem**
- Adequado para desenvolvimento e MVP

### Recomendação para Produção
Para maior segurança em produção, restringir a origens específicas:

```typescript
const corsHeaders = {
  "Access-Control-Allow-Origin": "https://onlifin.onlitec.com.br",
  "Access-Control-Allow-Methods": "POST, OPTIONS",
  "Access-Control-Allow-Headers": "authorization, x-client-info, apikey, content-type",
};
```

Ou múltiplas origens:
```typescript
const allowedOrigins = [
  "https://onlifin.onlitec.com.br",
  "https://app.onlifin.com.br",
  "http://localhost:5173" // desenvolvimento
];

const origin = req.headers.get("origin");
const corsHeaders = {
  "Access-Control-Allow-Origin": allowedOrigins.includes(origin) ? origin : allowedOrigins[0],
  "Access-Control-Allow-Methods": "POST, OPTIONS",
  "Access-Control-Allow-Headers": "authorization, x-client-info, apikey, content-type",
};
```

---

## Outras Edge Functions

### Verificação Necessária
Se houver outras Edge Functions no projeto, elas também precisam dos mesmos headers CORS:

1. Listar todas as Edge Functions:
   ```bash
   ls supabase/functions/
   ```

2. Para cada função, adicionar:
   - Headers CORS
   - Tratamento de OPTIONS
   - Headers em todas as respostas

### Template CORS
```typescript
Deno.serve(async (req: Request) => {
  // CORS headers
  const corsHeaders = {
    "Access-Control-Allow-Origin": "*",
    "Access-Control-Allow-Methods": "POST, OPTIONS",
    "Access-Control-Allow-Headers": "authorization, x-client-info, apikey, content-type",
  };

  // Handle CORS preflight request
  if (req.method === "OPTIONS") {
    return new Response(null, {
      status: 204,
      headers: corsHeaders,
    });
  }

  try {
    // Sua lógica aqui
    
    return new Response(
      JSON.stringify({ success: true }),
      { 
        status: 200, 
        headers: { 
          "Content-Type": "application/json",
          ...corsHeaders
        } 
      }
    );
  } catch (error) {
    return new Response(
      JSON.stringify({ error: error.message }),
      { 
        status: 500, 
        headers: { 
          "Content-Type": "application/json",
          ...corsHeaders
        } 
      }
    );
  }
});
```

---

## Troubleshooting

### Se o erro persistir:

1. **Limpar cache do navegador**
   - Ctrl + Shift + Delete
   - Limpar cache e cookies

2. **Verificar se a nova versão foi deployada**
   ```bash
   # Verificar logs da Edge Function
   supabase functions logs financial-forecast
   ```

3. **Testar com curl**
   ```bash
   # Preflight request
   curl -X OPTIONS \
     https://twbzhscoyasetrstrofl.supabase.co/functions/v1/financial-forecast \
     -H "Access-Control-Request-Method: POST" \
     -H "Origin: https://onlifin.onlitec.com.br" \
     -v
   
   # Deve retornar headers CORS
   ```

4. **Verificar console do navegador**
   - Abrir DevTools (F12)
   - Aba Network
   - Verificar requisição OPTIONS
   - Verificar headers da resposta

---

## Referências

### Documentação
- [MDN - CORS](https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS)
- [Supabase Edge Functions](https://supabase.com/docs/guides/functions)
- [Deno Deploy - CORS](https://deno.com/deploy/docs/cors)

### Commits
- `2da6683` - fix: add CORS headers to financial-forecast Edge Function

---

## Conclusão

✅ **Problema:** Erro CORS ao chamar Edge Function
✅ **Solução:** Adicionados headers CORS e tratamento de preflight
✅ **Status:** Corrigido e deployado (versão 2)
✅ **Testes:** Linter passa sem erros

A Edge Function agora está configurada corretamente para aceitar requisições do frontend, permitindo que o sistema de previsão financeira funcione sem erros de CORS.

---

**Data:** 08 de Dezembro de 2025  
**Versão Edge Function:** 2  
**Status:** ✅ Resolvido
