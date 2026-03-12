# Correções Aplicadas - Onlifin

**Data:** 05/03/2026 22:58

## Problemas Identificados

### 1. Erro 401 - Requisições API sem Token
**Sintoma:**
```
⚠️ [AuthFetch] Requisição API sem token: https://onlifin.onlitec.com.br/api/rest/v1/people
Failed to load resource: the server responded with a status of 401
Erro ao buscar pessoas: Object
Erro ao carregar pessoas: Error: Não foi possível carregar as pessoas

⚠️ [AuthFetch] Requisição API sem token: https://onlifin.onlitec.com.br/api/rest/v1/companies
Failed to load resource: the server responded with a status of 401
Erro ao buscar empresas: Object
Erro ao carregar empresas: Error: Não foi possível carregar as empresas
```

**Causa:**
Os contextos `PersonContext` e `CompanyContext` estavam tentando carregar dados antes do usuário estar completamente autenticado, resultando em requisições sem token JWT.

**Solução Aplicada:**
Adicionada verificação de autenticação antes de fazer requisições nos contextos:

#### PersonContext (`src/contexts/PersonContext.tsx`)
```typescript
const loadPeople = useCallback(async () => {
    setIsLoadingPeople(true);
    setError(null);

    try {
        // Verificar se o usuário está autenticado antes de carregar
        const { data: { user } } = await supabase.auth.getUser();
        if (!user) {
            setIsLoadingPeople(false);
            return;
        }

        const [peopleData, profile] = await Promise.all([
            personService.getAll(),
            profileService.getProfile()
        ]);
        // ... resto do código
```

#### CompanyContext (`src/contexts/CompanyContext.tsx`)
```typescript
const loadCompanies = useCallback(async () => {
    setIsLoadingCompanies(true);
    setError(null);

    try {
        // Verificar se o usuário está autenticado antes de carregar
        const { data: { user } } = await supabase.auth.getUser();
        if (!user) {
            setIsLoadingCompanies(false);
            return;
        }

        const data = await companyService.getAll();
        // ... resto do código
```

### 2. ReferenceError: goToCurrentMonth is not defined
**Sintoma:**
```
ReferenceError: goToCurrentMonth is not defined
    at uR (index-BxL7uhrc.js:917:42433)
    at nS (index-BxL7uhrc.js:38:17018)
```

**Causa:**
Cache do navegador ou build antigo contendo referências ao `DashboardOld.tsx` que possui a função `goToCurrentMonth`, mas o `Dashboard.tsx` atual não possui essa função.

**Solução Aplicada:**
- Limpeza do cache de build: `rm -rf dist node_modules/.vite`
- O Dashboard atual (`src/pages/Dashboard.tsx`) usa um botão "Mês Atual" que atualiza os estados diretamente:
```typescript
<Button
    variant="outline"
    onClick={() => {
        setSelectedMonth(new Date().getMonth().toString());
        setSelectedYear(new Date().getFullYear().toString());
    }}
>
    Mês Atual
</Button>
```

## Arquivos Modificados

1. **`src/contexts/PersonContext.tsx`**
   - Adicionado import: `import { supabase } from '@/db/client';`
   - Adicionada verificação de autenticação em `loadPeople()`

2. **`src/contexts/CompanyContext.tsx`**
   - Adicionada verificação de autenticação em `loadCompanies()`

3. **Cache limpo**
   - Removido: `dist/`
   - Removido: `node_modules/.vite/`

## Instruções para Teste

### 1. Reiniciar o Servidor de Desenvolvimento
```bash
cd /home/alfreire/docker/apps/onlifin
# Parar o servidor atual se estiver rodando
# Iniciar novamente
npm run dev
# ou
pnpm dev
```

### 2. Limpar Cache do Navegador
No navegador, pressione:
- **Chrome/Edge:** `Ctrl + Shift + Delete` → Limpar cache e cookies
- **Firefox:** `Ctrl + Shift + Delete` → Limpar cache
- Ou use modo anônimo/privado para testar

### 3. Testar o Fluxo
1. **Logout** (se estiver logado)
2. **Login** com credenciais válidas
3. Verificar se:
   - ✅ Não aparecem erros 401 no console
   - ✅ Pessoas são carregadas corretamente
   - ✅ Empresas são carregadas corretamente
   - ✅ Dashboard carrega sem erros de `goToCurrentMonth`
   - ✅ Botão "Mês Atual" funciona corretamente

### 4. Verificar Console do Navegador
Abra o DevTools (F12) e verifique:
- **Console:** Não deve haver erros de `ReferenceError` ou `401`
- **Network:** Requisições para `/api/rest/v1/people` e `/api/rest/v1/companies` devem ter status `200 OK`
- **Application → Local Storage:** Deve conter `onlifin_auth_session` com token válido

## Observações Importantes

### Fluxo de Autenticação
O sistema agora segue este fluxo:
1. Usuário faz login → Token JWT é gerado e salvo
2. `PersonContext` e `CompanyContext` verificam autenticação
3. Se autenticado → Carrega dados
4. Se não autenticado → Retorna sem fazer requisições

### Prevenção de Erros Futuros
- Sempre verificar autenticação antes de fazer requisições protegidas
- Usar `supabase.auth.getUser()` para verificar sessão
- Evitar carregar dados em contextos antes do login estar completo

## Próximos Passos (Se Necessário)

Se os erros persistirem:
1. Verificar se o servidor está usando o código atualizado
2. Fazer hard refresh no navegador (`Ctrl + F5`)
3. Verificar logs do servidor de desenvolvimento
4. Verificar se há múltiplas instâncias do servidor rodando

## Status

✅ **Correções aplicadas com sucesso**
⏳ **Aguardando testes do usuário**
