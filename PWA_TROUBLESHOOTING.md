# 🔧 PWA Troubleshooting - OnliFin

## ❌ Problema: Prompt de Instalação Não Aparece

### Causas Comuns

1. **Arquivos PWA não implantados em produção**
2. **Manifest.json não acessível**
3. **Service Worker não registrado**
4. **Prompt já foi dispensado anteriormente**
5. **Navegador não suporta PWA**
6. **App já está instalado**
7. **Critérios PWA não atendidos**

---

## 🔍 Diagnóstico Passo a Passo

### 1️⃣ Verificar se os Arquivos PWA Estão Acessíveis

Abra no navegador:

```
https://onlifin.onlitec.com.br/manifest.json
https://onlifin.onlitec.com.br/sw.js
https://onlifin.onlitec.com.br/offline.html
```

**✅ Esperado:** Os arquivos devem carregar sem erro 404

**❌ Se der erro 404:** Os arquivos não foram implantados. Você precisa fazer deploy dos arquivos da pasta `public/` para o servidor.

---

### 2️⃣ Verificar Service Worker no Console

1. Abra o site: `https://onlifin.onlitec.com.br/`
2. Pressione **F12** (DevTools)
3. Vá em **Application** → **Service Workers**

**✅ Esperado:** Deve aparecer um Service Worker com status "activated and running"

**❌ Se não aparecer:** O Service Worker não está registrado. Verifique o console por erros.

---

### 3️⃣ Verificar Manifest no DevTools

1. Abra DevTools (**F12**)
2. Vá em **Application** → **Manifest**

**✅ Esperado:** Deve mostrar todas as informações do manifest (nome, ícones, etc.)

**❌ Se aparecer erro:** O manifest não está carregando corretamente.

---

### 4️⃣ Verificar Console por Erros

1. Abra DevTools (**F12**)
2. Vá em **Console**
3. Procure por erros em vermelho

**Erros comuns:**
- `Failed to load manifest`
- `Service Worker registration failed`
- `MIME type error`

---

### 5️⃣ Executar Lighthouse Audit

1. Abra DevTools (**F12**)
2. Vá em **Lighthouse**
3. Selecione **Progressive Web App**
4. Clique em **Generate Report**

**✅ Esperado:** Score de 100% ou próximo

**❌ Se score baixo:** Veja os itens que falharam e corrija-os

---

## 🛠️ Soluções

### Solução 1: Limpar Cache e Dados do Navegador

```
Chrome/Edge:
1. Pressione Ctrl+Shift+Delete
2. Selecione "Todo o período"
3. Marque "Cookies" e "Cache"
4. Clique em "Limpar dados"
5. Recarregue a página (Ctrl+F5)
```

### Solução 2: Forçar Atualização do Service Worker

```javascript
// No Console do DevTools, execute:
navigator.serviceWorker.getRegistrations().then(function(registrations) {
  for(let registration of registrations) {
    registration.unregister();
  }
  location.reload();
});
```

### Solução 3: Resetar Prompt de Instalação

```javascript
// No Console do DevTools, execute:
localStorage.removeItem('pwa-install-dismissed');
location.reload();
```

### Solução 4: Verificar se Já Está Instalado

**Chrome/Edge Desktop:**
- Vá em `chrome://apps` ou `edge://apps`
- Veja se o OnliFin já está instalado

**Android:**
- Verifique a tela inicial
- Procure o ícone do OnliFin

**iOS:**
- Verifique a tela inicial
- Procure o ícone do OnliFin

---

## 📋 Checklist de Deploy

Para garantir que o PWA funcione em produção:

### Arquivos Obrigatórios
- [ ] `manifest.json` na raiz do site
- [ ] `sw.js` na raiz do site
- [ ] `offline.html` na raiz do site
- [ ] Pasta `/icons/` com todos os ícones
- [ ] `browserconfig.xml` na raiz
- [ ] `robots.txt` na raiz

### Configuração do Servidor
- [ ] HTTPS habilitado (obrigatório)
- [ ] MIME types corretos:
  - `manifest.json` → `application/manifest+json`
  - `sw.js` → `application/javascript`
- [ ] Headers CORS configurados (se necessário)
- [ ] Cache-Control configurado adequadamente

### HTML
- [ ] Tag `<link rel="manifest" href="/manifest.json">` presente
- [ ] Meta tags PWA presentes
- [ ] Script de registro do SW executando

---

## 🌐 Instalação Manual (Alternativa)

Se o prompt automático não aparecer, os usuários podem instalar manualmente:

### Chrome/Edge Desktop
1. Clique no menu (⋮) no canto superior direito
2. Selecione **"Instalar OnliFin..."**
3. Confirme

### Chrome/Edge Android
1. Toque no menu (⋮)
2. Selecione **"Adicionar à tela inicial"**
3. Confirme

### Safari iOS
1. Toque no botão de compartilhar (□↑)
2. Role para baixo
3. Toque em **"Adicionar à Tela de Início"**
4. Confirme

---

## 🔍 Comandos de Diagnóstico

Execute no Console do DevTools:

### Verificar se Service Worker está registrado
```javascript
navigator.serviceWorker.getRegistrations().then(registrations => {
  console.log('Service Workers:', registrations.length);
  registrations.forEach(reg => console.log(reg));
});
```

### Verificar se pode instalar
```javascript
window.addEventListener('beforeinstallprompt', (e) => {
  console.log('✅ App pode ser instalado!');
});
```

### Verificar se já está instalado
```javascript
if (window.matchMedia('(display-mode: standalone)').matches) {
  console.log('✅ App já está instalado!');
} else {
  console.log('❌ App não está instalado');
}
```

### Verificar manifest
```javascript
fetch('/manifest.json')
  .then(r => r.json())
  .then(manifest => console.log('Manifest:', manifest))
  .catch(e => console.error('Erro ao carregar manifest:', e));
```

---

## 📞 Suporte Adicional

### Logs Importantes

Ao reportar problemas, inclua:

1. **URL acessada**
2. **Navegador e versão**
3. **Sistema operacional**
4. **Erros do console** (screenshot)
5. **Resultado do Lighthouse**
6. **Status do Service Worker** (screenshot)

### Informações do Sistema

Execute no console:
```javascript
console.log({
  userAgent: navigator.userAgent,
  standalone: window.matchMedia('(display-mode: standalone)').matches,
  serviceWorker: 'serviceWorker' in navigator,
  manifest: document.querySelector('link[rel="manifest"]')?.href
});
```

---

## ✅ Verificação Final

Depois de aplicar as soluções, verifique:

- [ ] Manifest acessível em `/manifest.json`
- [ ] Service Worker registrado e ativo
- [ ] Console sem erros
- [ ] Lighthouse PWA score > 90%
- [ ] Prompt de instalação aparece (ou instalação manual funciona)

---

## 🎯 Próximos Passos

Se o problema persistir:

1. Verifique se todos os arquivos foram implantados corretamente
2. Confirme que o servidor está servindo os arquivos com MIME types corretos
3. Teste em diferentes navegadores
4. Use a instalação manual como alternativa
5. Consulte a documentação completa em `PWA_GUIDE.md`

---

**Última Atualização**: 2025-12-09  
**Versão**: 1.0.0
