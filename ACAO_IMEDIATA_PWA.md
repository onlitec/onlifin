# ⚡ Ação Imediata - PWA OnliFin

## 🎯 Problema

O prompt de instalação do PWA não aparece em `https://onlifin.onlitec.com.br/`

## ✅ Solução Rápida (3 Passos)

### 1️⃣ Fazer Build

```bash
cd /workspace/app-7xkeeoe4bsap
npm run build
```

### 2️⃣ Verificar Arquivos

Confirme que estes arquivos existem em `dist/`:

```bash
ls -la dist/manifest.json
ls -la dist/sw.js
ls -la dist/offline.html
ls -la dist/apple-touch-icon.svg
ls -la dist/icons/
```

**✅ Todos devem existir!**

### 3️⃣ Deploy para Produção

Envie **TODOS** os arquivos de `dist/` para o servidor:

```bash
# Exemplo com rsync
rsync -avz dist/ user@server:/var/www/onlifin/

# Ou use seu método de deploy (FTP, CI/CD, etc.)
```

---

## 🔍 Verificação Rápida

Após o deploy, teste:

### 1. Arquivos Acessíveis

Abra no navegador:
- ✅ `https://onlifin.onlitec.com.br/manifest.json`
- ✅ `https://onlifin.onlitec.com.br/sw.js`
- ✅ `https://onlifin.onlitec.com.br/icons/icon-192x192.svg`

**Todos devem carregar sem erro 404!**

### 2. Service Worker Registrado

1. Abra `https://onlifin.onlitec.com.br/`
2. Pressione **F12**
3. Vá em **Application** → **Service Workers**
4. Deve aparecer: **"activated and running"**

### 3. Lighthouse

1. **F12** → **Lighthouse**
2. Selecione **Progressive Web App**
3. **Generate Report**
4. Score deve ser **≥ 90%**

---

## 🎉 Resultado Esperado

Após o deploy correto:

✅ Prompt de instalação aparece automaticamente  
✅ Ou instalação manual funciona (Menu → "Instalar OnliFin")  
✅ App funciona offline  
✅ Ícone aparece na tela inicial após instalação  

---

## 🐛 Se Ainda Não Funcionar

### Limpar Cache do Navegador

```
Chrome/Edge:
1. Ctrl+Shift+Delete
2. Selecione "Todo o período"
3. Marque "Cookies" e "Cache"
4. Limpar dados
5. Recarregue (Ctrl+F5)
```

### Resetar Prompt de Instalação

No Console do DevTools:

```javascript
localStorage.removeItem('pwa-install-dismissed');
location.reload();
```

### Forçar Atualização do Service Worker

No Console do DevTools:

```javascript
navigator.serviceWorker.getRegistrations().then(regs => {
  regs.forEach(reg => reg.unregister());
  location.reload();
});
```

---

## 📋 Checklist Final

- [ ] Build executado (`npm run build`)
- [ ] Arquivos PWA existem em `dist/`
- [ ] Deploy feito para produção
- [ ] `manifest.json` acessível (200 OK)
- [ ] `sw.js` acessível (200 OK)
- [ ] Ícones acessíveis (200 OK)
- [ ] Service Worker registrado
- [ ] Lighthouse score ≥ 90%
- [ ] Prompt de instalação aparece

---

## 📞 Documentação Completa

- **GUIA_DEPLOY_PWA.md** - Guia completo de deploy
- **PWA_TROUBLESHOOTING.md** - Solução de problemas
- **PWA_DEPLOY_CHECKLIST.md** - Checklist detalhado

---

## 🚀 Instalação Manual (Alternativa)

Se o prompt não aparecer, usuários podem instalar manualmente:

### Desktop (Chrome/Edge)
Menu (⋮) → **"Instalar OnliFin..."**

### Android (Chrome/Edge)
Menu (⋮) → **"Adicionar à tela inicial"**

### iOS (Safari)
Compartilhar (□↑) → **"Adicionar à Tela de Início"**

---

**Status:** ✅ Implementação Completa  
**Próximo Passo:** Fazer build e deploy  
**Data:** 2025-12-09
