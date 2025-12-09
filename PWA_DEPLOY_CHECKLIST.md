# 📦 PWA Deploy Checklist - OnliFin

## 🎯 Objetivo

Garantir que todos os arquivos PWA sejam corretamente implantados em produção.

---

## 📋 Checklist de Arquivos

### Arquivos na Raiz do Site (public/)

Certifique-se de que estes arquivos estão acessíveis em `https://onlifin.onlitec.com.br/`:

- [ ] **manifest.json** - Configuração do PWA
  - URL: `https://onlifin.onlitec.com.br/manifest.json`
  - MIME type: `application/manifest+json`
  
- [ ] **sw.js** - Service Worker
  - URL: `https://onlifin.onlitec.com.br/sw.js`
  - MIME type: `application/javascript`
  
- [ ] **offline.html** - Página offline
  - URL: `https://onlifin.onlitec.com.br/offline.html`
  - MIME type: `text/html`
  
- [ ] **browserconfig.xml** - Configuração Windows
  - URL: `https://onlifin.onlitec.com.br/browserconfig.xml`
  - MIME type: `application/xml`
  
- [ ] **robots.txt** - SEO
  - URL: `https://onlifin.onlitec.com.br/robots.txt`
  - MIME type: `text/plain`

### Pasta de Ícones (public/icons/)

- [ ] **icon-72x72.png**
- [ ] **icon-96x96.png**
- [ ] **icon-128x128.png**
- [ ] **icon-144x144.png**
- [ ] **icon-152x152.png**
- [ ] **icon-192x192.png**
- [ ] **icon-384x384.png**
- [ ] **icon-512x512.png**
- [ ] **icon-maskable-192x192.png**
- [ ] **icon-maskable-512x512.png**
- [ ] **shortcut-transaction.png**
- [ ] **shortcut-dashboard.png**

**Nota:** Use `/icons/generate-icons.html` para gerar todos os ícones.

---

## 🔧 Configuração do Servidor

### HTTPS (Obrigatório)
- [ ] Site acessível via HTTPS
- [ ] Certificado SSL válido
- [ ] Redirecionamento HTTP → HTTPS configurado

### MIME Types
Configure o servidor para servir os arquivos com os tipos corretos:

```nginx
# Nginx
location ~* \.json$ {
    add_header Content-Type application/manifest+json;
}

location ~* sw\.js$ {
    add_header Content-Type application/javascript;
    add_header Service-Worker-Allowed /;
}
```

```apache
# Apache (.htaccess)
<IfModule mod_mime.c>
    AddType application/manifest+json .json
    AddType application/javascript .js
</IfModule>
```

### Headers de Cache

```nginx
# Nginx
location ~* sw\.js$ {
    add_header Cache-Control "no-cache, no-store, must-revalidate";
}

location ~* manifest\.json$ {
    add_header Cache-Control "max-age=3600";
}
```

---

## 🧪 Testes de Verificação

### 1. Teste de Acessibilidade dos Arquivos

Execute no terminal ou navegador:

```bash
# Manifest
curl -I https://onlifin.onlitec.com.br/manifest.json

# Service Worker
curl -I https://onlifin.onlitec.com.br/sw.js

# Página Offline
curl -I https://onlifin.onlitec.com.br/offline.html
```

**Esperado:** Status 200 OK para todos

### 2. Teste do Manifest

Abra no navegador:
```
https://onlifin.onlitec.com.br/manifest.json
```

**Esperado:** JSON válido com todas as configurações

### 3. Teste do Service Worker

1. Abra: `https://onlifin.onlitec.com.br/`
2. Pressione F12 → Application → Service Workers
3. Verifique se está "activated and running"

### 4. Lighthouse Audit

1. Abra: `https://onlifin.onlitec.com.br/`
2. F12 → Lighthouse
3. Selecione "Progressive Web App"
4. Generate Report

**Esperado:** Score ≥ 90%

---

## 🚀 Processo de Deploy

### Opção 1: Deploy Manual

1. **Build da aplicação:**
```bash
npm run build
```

2. **Copiar arquivos para o servidor:**
```bash
# Copie todo o conteúdo da pasta dist/ para o servidor
scp -r dist/* user@server:/var/www/onlifin/
```

3. **Verificar permissões:**
```bash
chmod 644 /var/www/onlifin/*.json
chmod 644 /var/www/onlifin/*.js
chmod 644 /var/www/onlifin/*.html
```

### Opção 2: Deploy Automatizado (CI/CD)

Adicione ao seu pipeline:

```yaml
# GitHub Actions exemplo
- name: Build
  run: npm run build

- name: Deploy
  run: |
    # Seu comando de deploy aqui
    # Certifique-se de incluir todos os arquivos PWA
```

---

## ✅ Verificação Pós-Deploy

### Checklist Final

- [ ] Site acessível via HTTPS
- [ ] Manifest.json carrega sem erros
- [ ] Service Worker registrado
- [ ] Ícones carregam corretamente
- [ ] Página offline funciona
- [ ] Console sem erros
- [ ] Lighthouse PWA score ≥ 90%
- [ ] Prompt de instalação aparece (ou instalação manual funciona)

### Teste em Múltiplos Dispositivos

- [ ] **Desktop Chrome** - Prompt de instalação
- [ ] **Desktop Edge** - Prompt de instalação
- [ ] **Android Chrome** - Adicionar à tela inicial
- [ ] **iOS Safari** - Adicionar à tela de início

---

## 🐛 Problemas Comuns

### Problema 1: Manifest não carrega
**Causa:** MIME type incorreto  
**Solução:** Configure servidor para `application/manifest+json`

### Problema 2: Service Worker não registra
**Causa:** Arquivo sw.js não encontrado ou HTTPS não configurado  
**Solução:** Verifique se sw.js está na raiz e HTTPS está ativo

### Problema 3: Ícones não aparecem
**Causa:** Caminhos incorretos no manifest  
**Solução:** Verifique se os ícones estão em `/icons/` e acessíveis

### Problema 4: Prompt não aparece
**Causa:** Critérios PWA não atendidos  
**Solução:** Execute Lighthouse e corrija os itens falhados

---

## 📊 Comandos de Verificação Rápida

Execute no Console do DevTools após deploy:

```javascript
// Verificar manifest
fetch('/manifest.json')
  .then(r => r.json())
  .then(m => console.log('✅ Manifest OK:', m))
  .catch(e => console.error('❌ Manifest Error:', e));

// Verificar Service Worker
navigator.serviceWorker.getRegistrations()
  .then(regs => console.log('✅ SW Registrations:', regs.length))
  .catch(e => console.error('❌ SW Error:', e));

// Verificar ícones
fetch('/icons/icon-192x192.png')
  .then(r => console.log('✅ Ícones OK'))
  .catch(e => console.error('❌ Ícones Error:', e));
```

---

## 📞 Suporte

Se encontrar problemas durante o deploy:

1. Consulte `PWA_TROUBLESHOOTING.md`
2. Verifique os logs do servidor
3. Execute Lighthouse para diagnóstico
4. Verifique o console do navegador

---

## 🎯 Próximos Passos Após Deploy

1. **Monitorar:** Verifique analytics de instalações
2. **Testar:** Teste em diferentes dispositivos e navegadores
3. **Otimizar:** Ajuste cache e performance conforme necessário
4. **Atualizar:** Incremente versão do cache quando fizer updates

---

**Última Atualização**: 2025-12-09  
**Versão**: 1.0.0
