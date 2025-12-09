# 🚀 Guia de Deploy PWA - OnliFin

## 📋 Problema Atual

O prompt de instalação do PWA não aparece em produção (`https://onlifin.onlitec.com.br/`).

## 🔍 Diagnóstico

### Passo 1: Verificar Arquivos PWA

Abra no navegador e verifique se retornam **200 OK**:

```
✅ https://onlifin.onlitec.com.br/manifest.json
✅ https://onlifin.onlitec.com.br/sw.js
✅ https://onlifin.onlitec.com.br/offline.html
✅ https://onlifin.onlitec.com.br/icons/icon-192x192.svg
✅ https://onlifin.onlitec.com.br/apple-touch-icon.svg
```

**❌ Se algum retornar 404:** Os arquivos não foram implantados corretamente.

### Passo 2: Verificar Service Worker

1. Abra `https://onlifin.onlitec.com.br/`
2. Pressione **F12** (DevTools)
3. Vá em **Application** → **Service Workers**

**Esperado:** Service Worker "activated and running"

### Passo 3: Verificar Manifest

1. DevTools → **Application** → **Manifest**

**Esperado:** Todas as informações do manifest visíveis

### Passo 4: Lighthouse Audit

1. DevTools → **Lighthouse**
2. Selecione **Progressive Web App**
3. **Generate Report**

**Esperado:** Score ≥ 90%

---

## 🛠️ Solução: Deploy Correto

### Arquivos que DEVEM estar em produção:

```
/
├── index.html                    ✅ (com meta tags PWA)
├── manifest.json                 ✅
├── sw.js                         ✅
├── offline.html                  ✅
├── browserconfig.xml             ✅
├── robots.txt                    ✅
├── apple-touch-icon.svg          ✅
└── icons/
    ├── icon-72x72.svg           ✅
    ├── icon-96x96.svg           ✅
    ├── icon-128x128.svg         ✅
    ├── icon-144x144.svg         ✅
    ├── icon-152x152.svg         ✅
    ├── icon-192x192.svg         ✅
    ├── icon-384x384.svg         ✅
    ├── icon-512x512.svg         ✅
    ├── icon-maskable-192x192.svg ✅
    ├── icon-maskable-512x512.svg ✅
    ├── shortcut-transaction.svg  ✅
    └── shortcut-dashboard.svg    ✅
```

---

## 📦 Processo de Deploy

### Opção 1: Build e Deploy Manual

```bash
# 1. Fazer build da aplicação
cd /workspace/app-7xkeeoe4bsap
npm run build

# 2. Verificar se os arquivos PWA estão no dist/
ls -la dist/manifest.json
ls -la dist/sw.js
ls -la dist/offline.html
ls -la dist/icons/

# 3. Fazer deploy de TODOS os arquivos do dist/ para produção
# (Use seu método de deploy: FTP, rsync, CI/CD, etc.)
```

### Opção 2: Verificar Configuração do Vite

O Vite deve copiar os arquivos da pasta `public/` para o `dist/` automaticamente.

Verifique se `vite.config.ts` está correto:

```typescript
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig({
  plugins: [react()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  // Vite copia automaticamente arquivos de public/ para dist/
  publicDir: 'public',
});
```

---

## 🔧 Configuração do Servidor

### Nginx

Adicione ao seu `nginx.conf`:

```nginx
server {
    listen 443 ssl http2;
    server_name onlifin.onlitec.com.br;
    
    root /var/www/onlifin;
    index index.html;
    
    # PWA - Manifest
    location ~* \.json$ {
        add_header Content-Type application/manifest+json;
        add_header Cache-Control "max-age=3600";
    }
    
    # PWA - Service Worker (sem cache)
    location ~* sw\.js$ {
        add_header Content-Type application/javascript;
        add_header Cache-Control "no-cache, no-store, must-revalidate";
        add_header Service-Worker-Allowed /;
    }
    
    # PWA - Ícones SVG
    location ~* \.svg$ {
        add_header Content-Type image/svg+xml;
        add_header Cache-Control "max-age=86400";
    }
    
    # SPA - Redirecionar tudo para index.html
    location / {
        try_files $uri $uri/ /index.html;
    }
}
```

### Apache

Adicione ao `.htaccess`:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    
    # SPA - Redirecionar para index.html
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule . /index.html [L]
</IfModule>

<IfModule mod_mime.c>
    # PWA - MIME types
    AddType application/manifest+json .json
    AddType application/javascript .js
    AddType image/svg+xml .svg
</IfModule>

<IfModule mod_headers.c>
    # PWA - Service Worker sem cache
    <FilesMatch "sw\.js$">
        Header set Cache-Control "no-cache, no-store, must-revalidate"
        Header set Service-Worker-Allowed "/"
    </FilesMatch>
    
    # PWA - Manifest com cache curto
    <FilesMatch "manifest\.json$">
        Header set Cache-Control "max-age=3600"
    </FilesMatch>
</IfModule>
```

---

## ✅ Checklist de Verificação

Após o deploy, verifique:

- [ ] Site acessível via HTTPS
- [ ] `manifest.json` retorna 200 OK
- [ ] `sw.js` retorna 200 OK
- [ ] `offline.html` retorna 200 OK
- [ ] Ícones SVG retornam 200 OK
- [ ] Service Worker registrado (DevTools)
- [ ] Manifest válido (DevTools)
- [ ] Console sem erros
- [ ] Lighthouse PWA score ≥ 90%

---

## 🎯 Instalação Manual (Alternativa)

Se o prompt automático não aparecer, usuários podem instalar manualmente:

### Chrome/Edge Desktop
1. Menu (⋮) → **"Instalar OnliFin..."**

### Chrome/Edge Android
1. Menu (⋮) → **"Adicionar à tela inicial"**

### Safari iOS
1. Compartilhar (□↑) → **"Adicionar à Tela de Início"**

---

## 🐛 Troubleshooting

### Problema: Manifest não carrega

**Causa:** MIME type incorreto ou arquivo não existe

**Solução:**
```bash
# Verificar se arquivo existe
curl -I https://onlifin.onlitec.com.br/manifest.json

# Deve retornar:
# HTTP/2 200
# content-type: application/manifest+json
```

### Problema: Service Worker não registra

**Causa:** Arquivo não existe ou HTTPS não configurado

**Solução:**
```bash
# Verificar arquivo
curl -I https://onlifin.onlitec.com.br/sw.js

# Verificar console por erros
# DevTools → Console
```

### Problema: Ícones não aparecem

**Causa:** Caminhos incorretos ou arquivos não existem

**Solução:**
```bash
# Verificar ícones
curl -I https://onlifin.onlitec.com.br/icons/icon-192x192.svg

# Verificar manifest aponta para caminhos corretos
```

### Problema: Prompt não aparece mesmo com tudo OK

**Causas possíveis:**
1. Usuário já dispensou o prompt anteriormente
2. App já está instalado
3. Navegador não suporta

**Solução:**
```javascript
// No Console do DevTools, execute:
localStorage.removeItem('pwa-install-dismissed');
location.reload();
```

---

## 📊 Comandos de Diagnóstico

Execute no Console do DevTools:

### Verificar Service Worker
```javascript
navigator.serviceWorker.getRegistrations().then(regs => {
  console.log('Service Workers:', regs);
});
```

### Verificar Manifest
```javascript
fetch('/manifest.json')
  .then(r => r.json())
  .then(m => console.log('Manifest:', m))
  .catch(e => console.error('Erro:', e));
```

### Verificar se pode instalar
```javascript
window.addEventListener('beforeinstallprompt', (e) => {
  console.log('✅ Pode instalar!');
});
```

### Verificar se já está instalado
```javascript
if (window.matchMedia('(display-mode: standalone)').matches) {
  console.log('✅ Já instalado!');
} else {
  console.log('❌ Não instalado');
}
```

---

## 🎓 Recursos Adicionais

- **PWA_TROUBLESHOOTING.md** - Guia completo de troubleshooting
- **PWA_DEPLOY_CHECKLIST.md** - Checklist detalhado de deploy
- **PWA_GUIDE.md** - Documentação técnica completa

---

## 📞 Próximos Passos

1. **Fazer build:** `npm run build`
2. **Verificar dist/:** Confirmar que todos os arquivos PWA estão lá
3. **Deploy:** Enviar TODOS os arquivos para produção
4. **Testar:** Verificar URLs e Lighthouse
5. **Instalar:** Testar instalação em diferentes dispositivos

---

**Última Atualização:** 2025-12-09  
**Versão:** 1.0.0
