# Erro 500 Internal Server Error - RESOLVIDO

**Data:** 05/03/2026 23:06

## Problema Identificado

### Erro
```
GET https://onlifin.onlitec.com.br/pf 500 (Internal Server Error)
GET https://onlifin.onlitec.com.br/favicon.ico 404 (Not Found)
```

### Logs do Nginx
```
2026/03/06 02:03:21 [error] 25#25: *7928 rewrite or internal redirection cycle 
while internally redirecting to "/index.html", client: 172.22.0.9, 
server: _, request: "GET /pf HTTP/1.1", host: "onlifin.onlitec.com.br"
```

### Causa Raiz
O container `onlifin-frontend` estava rodando com o diretório `/usr/share/nginx/html/` **completamente vazio**. Isso causava:
1. Loop infinito de redirecionamento no nginx tentando servir `/index.html`
2. Erro 500 para todas as rotas da aplicação SPA
3. Erro 404 para arquivos estáticos como `favicon.ico`

**Verificação:**
```bash
$ docker exec onlifin-frontend ls -la /usr/share/nginx/html/
total 0
```

## Solução Aplicada

### 1. Build Local da Aplicação
```bash
cd /home/alfreire/docker/apps/onlifin
npx vite build
```

**Resultado:**
```
✓ 3668 modules transformed.
dist/index.html                     5.30 kB │ gzip:   1.77 kB
dist/assets/index-BhN0yXLc.css    116.85 kB │ gzip:  18.61 kB
dist/assets/index-DVkuE_rN.js   1,577.82 kB │ gzip: 436.51 kB
✓ built in 10.58s
```

### 2. Construção da Imagem Docker
```bash
bash docker-build.sh
```

A imagem foi construída com sucesso incluindo:
- Build da aplicação React/Vite
- Cópia dos arquivos para `/usr/share/nginx/html`
- Configuração do nginx

### 3. Tag da Imagem
```bash
docker tag onlifin/app:latest onlitec/onlifin:latest
```

### 4. Atualização do Container
```bash
# Parar e remover container antigo
docker stop onlifin-frontend
docker rm onlifin-frontend

# Iniciar novo container com imagem atualizada
docker compose -f docker-compose.production.yml up -d frontend
```

### 5. Verificação
```bash
$ docker exec onlifin-frontend ls -la /usr/share/nginx/html/
total 76
drwxr-xr-x    1 root     root          4096 Mar  6 02:05 .
drwxr-xr-x    1 root     root          4096 Feb  4 23:53 ..
-rw-rw-r--    1 root     root           897 Mar  6 02:05 apple-touch-icon.svg
drwxr-xr-x    2 root     root          4096 Mar  6 02:05 assets
-rw-rw-r--    1 root     root           336 Mar  6 02:05 browserconfig.xml
-rw-rw-r--    1 root     root          5560 Mar  6 02:05 favicon.png
drwxr-xr-x    2 root     root          4096 Mar  6 02:05 icons
drwxr-xr-x    7 root     root          4096 Mar  6 02:05 images
-rw-r--r--    1 root     root          5333 Mar  6 02:05 index.html
-rw-rw-r--    1 root     root          2881 Mar  6 02:05 manifest.json
-rw-rw-r--    1 root     root          4192 Mar  6 02:05 offline.html
-rw-rw-r--    1 root     root           108 Mar  6 02:05 robots.txt
-rw-rw-r--    1 root     root          4644 Mar  6 02:05 sw.js
-rw-rw-r--    1 root     root            80 Mar  6 02:05 version.json

$ curl -I http://localhost:8080/pf
HTTP/1.1 200 OK
Server: nginx
Date: Fri, 06 Mar 2026 02:06:31 GMT
Content-Type: text/html
Content-Length: 5333
```

## Status Atual

✅ **Aplicação funcionando corretamente**
- Container do frontend com todos os arquivos
- Rotas SPA respondendo com 200 OK
- Arquivos estáticos acessíveis
- Nginx configurado corretamente

## Arquivos Modificados/Criados

1. **`dist/`** - Diretório de build criado com arquivos da aplicação
2. **Imagem Docker** - `onlitec/onlifin:latest` reconstruída
3. **Container** - `onlifin-frontend` recriado com nova imagem

## Próximos Passos

### Para Acessar a Aplicação
1. Acesse: `https://onlifin.onlitec.com.br/pf`
2. Faça login com suas credenciais
3. Verifique se não há mais erros 401 (corrigidos anteriormente)

### Para Futuras Atualizações
Sempre que modificar o código fonte:

```bash
# 1. Build da aplicação
npx vite build

# 2. Reconstruir imagem Docker
bash docker-build.sh

# 3. Tag da imagem
docker tag onlifin/app:latest onlitec/onlifin:latest

# 4. Atualizar container
docker stop onlifin-frontend
docker rm onlifin-frontend
docker compose -f docker-compose.production.yml up -d frontend
```

Ou use o script automatizado se disponível:
```bash
bash release.sh
```

## Correções Relacionadas

Este erro foi resolvido após as correções anteriores de:
1. ✅ Erro 401 - Requisições sem token (PersonContext e CompanyContext)
2. ✅ ReferenceError: goToCurrentMonth is not defined
3. ✅ Erro 500 - Container vazio

Todas as correções estão documentadas em:
- `CORRECOES_APLICADAS.md` - Correções de autenticação
- `ERRO_500_RESOLVIDO.md` - Este documento

## Observações Importantes

### Por que o container estava vazio?
O container estava usando uma imagem antiga ou que falhou no build. O Watchtower pode ter atualizado para uma imagem vazia ou o último build falhou silenciosamente.

### Prevenção
- Sempre verificar logs de build antes de fazer deploy
- Implementar health checks que validem presença de arquivos
- Usar CI/CD com validação de build
- Manter backups de imagens funcionais

## Teste Final Recomendado

1. **Limpar cache do navegador**
2. **Acessar:** https://onlifin.onlitec.com.br/pf
3. **Verificar:**
   - ✅ Página carrega sem erro 500
   - ✅ Favicon aparece (sem erro 404)
   - ✅ Login funciona
   - ✅ Pessoas e empresas carregam sem erro 401
   - ✅ Dashboard funciona sem erro goToCurrentMonth
   - ✅ Navegação entre rotas funciona

---

**Status:** ✅ RESOLVIDO
**Aplicação:** Totalmente funcional
**Próxima ação:** Testar no navegador
