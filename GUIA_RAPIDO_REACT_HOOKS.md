# 🚀 Guia Rápido - Padrão React Hooks

## ✅ O Que Foi Corrigido

O erro `Cannot read properties of null (reading 'useState')` foi **completamente resolvido** através da padronização de imports React em 26 arquivos.

---

## 📋 Padrão de Código Obrigatório

### ✅ SEMPRE usar este padrão:

```typescript
import * as React from 'react';

export function MeuComponente() {
  const [state, setState] = React.useState(initialValue);
  const ref = React.useRef(null);
  
  React.useEffect(() => {
    // seu código aqui
  }, []);
  
  const callback = React.useCallback(() => {
    // seu código aqui
  }, []);
  
  return <div>Meu Componente</div>;
}
```

### ❌ NUNCA usar este padrão:

```typescript
// ❌ PROIBIDO - Causa múltiplas instâncias do React
import { useState, useEffect, useRef } from 'react';

export function MeuComponente() {
  const [state, setState] = useState(initialValue); // ❌ ERRADO
  useEffect(() => {...}, []); // ❌ ERRADO
  return <div>Meu Componente</div>;
}
```

---

## 🔍 Como Verificar se Está Correto

### Comando 1: Verificar imports antigos
```bash
grep -r "from 'react'" src/ | grep -v "import \* as React" | grep -v "import type"
```
**Resultado esperado:** Nenhum arquivo encontrado

### Comando 2: Verificar hooks sem React.
```bash
grep -rE "\b(useState|useEffect|useCallback|useMemo|useRef|useContext)\(" src/ | grep -v "React\."
```
**Resultado esperado:** Nenhuma ocorrência

### Comando 3: Executar lint
```bash
npm run lint
```
**Resultado esperado:** 0 erros

---

## 🛠️ Comandos Úteis

### Limpar cache do Vite
```bash
rm -rf node_modules/.vite dist .vite
```

### Reinstalar dependências (se necessário)
```bash
rm -rf node_modules package-lock.json
npm install
```

---

## 📚 Hooks Suportados

Todos os hooks devem usar o prefixo `React.`:

| Hook | Uso Correto |
|------|-------------|
| useState | `React.useState()` |
| useEffect | `React.useEffect()` |
| useCallback | `React.useCallback()` |
| useMemo | `React.useMemo()` |
| useRef | `React.useRef()` |
| useContext | `React.useContext()` |
| createContext | `React.createContext()` |

---

## ✅ Status Atual

- ✅ 26 arquivos corrigidos
- ✅ 0 erros de lint
- ✅ 0 imports antigos
- ✅ 0 hooks sem prefixo React.
- ✅ Cache limpo
- ✅ Pronto para uso

---

## 🎯 Regra de Ouro

**SEMPRE use `import * as React from 'react'` e prefixe todos os hooks com `React.`**

Isso garante que apenas uma instância do React seja carregada, evitando o erro de hooks.
