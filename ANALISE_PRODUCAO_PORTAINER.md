# 🚀 Análise para Produção - Onlifin + Portainer

**Data:** 23/12/2024  
**Objetivo:** Identificar pendências e melhorias para deploy em VPS com Portainer

---

## 📊 Estado Atual da Plataforma

A plataforma Onlifin é uma aplicação de gestão financeira pessoal com:
- Frontend: React + Vite + TailwindCSS
- API: PostgREST (REST automático sobre PostgreSQL)
- Banco: PostgreSQL 16
- IA: Ollama (modelo local)
- Deploy: Docker multi-container

---

## 🔴 PENDÊNCIAS CRÍTICAS

### 1. ⚠️ Credenciais Hardcoded no Docker Compose

**Problema:** Senhas e secrets expostos diretamente no `docker-compose.yml`

```yaml
# PROBLEMA - docker-compose.yml linha 42
POSTGRES_PASSWORD: OnlifinDB2024Secure

# PROBLEMA - docker-compose.yml linha 66-69
PGRST_DB_URI: "postgres://onlifin:OnlifinDB2024Secure@db:5432/onlifin"
PGRST_JWT_SECRET: "A2U0nSRYTH1bdKB9rZpEHogZfd5OeGZX5kYp6sEIxU"
```

**Solução:**
```yaml
services:
  db:
    environment:
      POSTGRES_PASSWORD: ${POSTGRES_PASSWORD}
  
  api:
    environment:
      PGRST_DB_URI: "postgres://onlifin:${POSTGRES_PASSWORD}@db:5432/onlifin"
      PGRST_JWT_SECRET: ${JWT_SECRET}
```

**Ação no Portainer:**
- Usar "Stacks" com variáveis de ambiente
- Ou usar Docker Secrets (mais seguro)

---

### 2. ⚠️ Dockerfile do Postgres com Senha Padrão

**Problema:** `docker/Dockerfile.postgres` linha 23 tem senha hardcoded

```dockerfile
ENV POSTGRES_PASSWORD=onlifin123  # INSEGURO!
```

**Solução:**
```dockerfile
# Remover esta linha - a senha deve vir via variável de ambiente
# ENV POSTGRES_PASSWORD=onlifin123
```

---

### 3. ⚠️ JWT Secret Fraco

**Problema:** O JWT secret no `.env` é muito simples:
```
JWT_SECRET=super-secret-jwt-key-minimum-32-characters
```

**Solução:** Gerar um secret forte:
```bash
openssl rand -base64 64
```

---

### 4. ⚠️ Arquivo .env Não no .gitignore

**Problema:** O `.env` principal não está listado no `.gitignore`, podendo vazar para o repositório.

**Arquivo atual .gitignore:**
```
.env.coolify  # Apenas este está ignorado
```

**Solução:** Adicionar ao `.gitignore`:
```
.env
.env.local
.env.*.local
```

---

## 🟡 MELHORIAS IMPORTANTES

### 5. 📦 SSL/HTTPS - Não Configurado

**Problema:** O nginx.conf apenas escuta na porta 80 (HTTP).

**Solução para Portainer:**
1. Usar Traefik (já integrado com Portainer) para SSL automático
2. Ou adicionar proxy reverso com Let's Encrypt

**Configuração recomendada com Traefik:**
```yaml
services:
  app:
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.onlifin.rule=Host(`seu-dominio.com`)"
      - "traefik.http.routers.onlifin.tls.certresolver=letsencrypt"
```

---

### 6. 📊 Healthchecks Melhorados

**Estado Atual:** Healthchecks básicos configurados ✅

**Melhoria Sugerida:** Adicionar healthcheck para o Ollama:
```yaml
ollama:
  healthcheck:
    test: ["CMD", "curl", "-f", "http://localhost:11434/api/tags"]
    interval: 30s
    timeout: 10s
    retries: 3
    start_period: 60s
```

---

### 7. 📝 Logging Centralizado

**Problema:** Logs dispersos entre containers.

**Solução:** Configurar driver de logging:
```yaml
services:
  app:
    logging:
      driver: "json-file"
      options:
        max-size: "10m"
        max-file: "3"
```

---

### 8. 🔄 Backup Automatizado

**Estado Atual:** Script `scripts/backup_db.sh` existe mas não está automatizado.

**Solução:** Criar container de backup:
```yaml
backup:
  image: postgres:16-alpine
  volumes:
    - ./backups:/backups
    - postgres_data:/var/lib/postgresql/data:ro
  command: |
    sh -c 'while true; do
      pg_dump -h db -U onlifin -d onlifin > /backups/backup_$(date +%Y%m%d_%H%M%S).sql
      find /backups -mtime +7 -delete
      sleep 86400
    done'
  depends_on:
    - db
```

---

### 9. 🎯 Rate Limiting / DDoS Protection

**Problema:** Sem proteção contra ataques de força bruta.

**Solução:** Adicionar ao nginx.conf:
```nginx
# Rate limiting para API
limit_req_zone $binary_remote_addr zone=api_limit:10m rate=10r/s;

location /api/ {
    limit_req zone=api_limit burst=20 nodelay;
    # ... resto da config
}
```

---

### 10. 🔐 Content Security Policy

**Estado Atual:** Headers de segurança básicos ✅

**Melhoria:** Adicionar CSP completo ao nginx.conf:
```nginx
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self' https://*.supabase.co;" always;
```

---

## 🟢 PONTOS POSITIVOS (JÁ IMPLEMENTADOS)

✅ **Docker Multi-stage Build** - Dockerfile otimizado  
✅ **Healthchecks** - Configurados para app, db e api  
✅ **Gzip Compression** - Habilitado no nginx  
✅ **Static Asset Caching** - 1 ano para assets  
✅ **Security Headers** - X-Frame-Options, X-Content-Type-Options, etc.  
✅ **PWA Ready** - Service Worker e manifest configurados  
✅ **GitHub Actions** - Pipeline de CI/CD funcional  
✅ **Row Level Security** - Isolamento de dados por usuário  

---

## 📋 CHECKLIST PARA DEPLOY NO PORTAINER

### Pré-Deploy

- [ ] Gerar novas senhas fortes para produção
- [ ] Gerar novo JWT_SECRET (`openssl rand -base64 64`)
- [ ] Remover credenciais hardcoded do docker-compose.yml
- [ ] Adicionar .env ao .gitignore
- [ ] Configurar variáveis de ambiente no Portainer

### Configuração do Portainer

- [ ] Criar Stack "onlifin"
- [ ] Configurar variáveis de ambiente:
  - `POSTGRES_PASSWORD` - Senha forte do banco
  - `JWT_SECRET` - Secret JWT (mínimo 64 caracteres)
  - `VITE_SUPABASE_URL` - URL da API (ex: https://app.seudominio.com)
  - `VITE_SUPABASE_ANON_KEY` - Chave anônima
  - `OLLAMA_MODEL` - Modelo de IA (ex: qwen2.5:0.5b)
- [ ] Configurar volumes persistentes para:
  - `postgres_data` - Dados do PostgreSQL
  - `ollama_data` - Modelos do Ollama
- [ ] Configurar rede interna entre containers
- [ ] Expor apenas porta 80/443 do container app

### SSL/HTTPS

- [ ] Configurar domínio DNS apontando para o VPS
- [ ] Configurar Traefik ou Nginx Proxy Manager
- [ ] Obter certificado Let's Encrypt
- [ ] Forçar redirecionamento HTTP → HTTPS

### Pós-Deploy

- [ ] Verificar healthchecks de todos os containers
- [ ] Testar login e funcionalidades principais
- [ ] Configurar backup automatizado
- [ ] Configurar monitoramento (opcional: Uptime Kuma)
- [ ] Documentar acesso admin inicial

---

## 🔧 DOCKER-COMPOSE CORRIGIDO PARA PORTAINER

```yaml
version: '3.8'

# ===========================================
# Onlifin - Docker Compose para Produção
# Compatível com Portainer
# ===========================================

services:
  # Frontend - Aplicação React/Nginx
  app:
    image: onlitec/onlifin:latest
    ports:
      - "${APP_PORT:-80}:80"
    depends_on:
      api:
        condition: service_started
      ollama:
        condition: service_started
    environment:
      - API_URL=http://api:3000
      - OLLAMA_URL=http://ollama:11434
    restart: unless-stopped
    healthcheck:
      test: ["CMD", "wget", "--no-verbose", "--tries=1", "--spider", "http://localhost/"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 10s
    networks:
      - onlifin-network
    logging:
      driver: "json-file"
      options:
        max-size: "10m"
        max-file: "3"

  # Banco de Dados PostgreSQL
  db:
    image: onlitec/onlifin-db:latest
    environment:
      POSTGRES_DB: onlifin
      POSTGRES_USER: onlifin
      POSTGRES_PASSWORD: ${POSTGRES_PASSWORD}  # Via variável de ambiente
    volumes:
      - postgres_data:/var/lib/postgresql/data
    expose:
      - "5432"
    restart: unless-stopped
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U onlifin -d onlifin"]
      interval: 10s
      timeout: 5s
      retries: 5
      start_period: 30s
    networks:
      - onlifin-network
    logging:
      driver: "json-file"
      options:
        max-size: "10m"
        max-file: "3"

  # API REST - PostgREST
  api:
    image: postgrest/postgrest:v12.0.2
    depends_on:
      db:
        condition: service_healthy
    environment:
      PGRST_DB_URI: "postgres://onlifin:${POSTGRES_PASSWORD}@db:5432/onlifin"
      PGRST_DB_SCHEMA: "public"
      PGRST_DB_ANON_ROLE: "anon"
      PGRST_JWT_SECRET: ${JWT_SECRET}  # Via variável de ambiente
    expose:
      - "3000"
    restart: unless-stopped
    networks:
      - onlifin-network
    healthcheck:
      test: ["CMD", "wget", "--no-verbose", "--tries=1", "--spider", "http://localhost:3000/"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 10s
    logging:
      driver: "json-file"
      options:
        max-size: "5m"
        max-file: "3"

  # Ollama - IA Local
  ollama:
    image: ollama/ollama:latest
    volumes:
      - ollama_data:/root/.ollama
    expose:
      - "11434"
    restart: unless-stopped
    networks:
      - onlifin-network
    deploy:
      resources:
        limits:
          memory: ${OLLAMA_MEMORY_LIMIT:-4G}
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:11434/api/tags"]
      interval: 60s
      timeout: 10s
      retries: 3
      start_period: 120s
    logging:
      driver: "json-file"
      options:
        max-size: "10m"
        max-file: "3"

networks:
  onlifin-network:
    driver: bridge

volumes:
  postgres_data:
    driver: local
  ollama_data:
    driver: local
```

---

## 📊 VARIÁVEIS DE AMBIENTE PARA PORTAINER

| Variável | Descrição | Exemplo |
|----------|-----------|---------|
| `POSTGRES_PASSWORD` | Senha do PostgreSQL | `SenhaForte123!@#` |
| `JWT_SECRET` | Secret para tokens JWT | `openssl rand -base64 64` |
| `APP_PORT` | Porta externa da aplicação | `80` ou `8080` |
| `OLLAMA_MEMORY_LIMIT` | Limite de memória do Ollama | `4G` |
| `VITE_SUPABASE_URL` | URL da API | `https://app.seudominio.com` |
| `VITE_SUPABASE_ANON_KEY` | Chave anônima | (gerar nova chave) |

---

## 🔒 SEGURANÇA - RESUMO DE AÇÕES

| Ação | Prioridade | Status |
|------|------------|--------|
| Remover senhas hardcoded | 🔴 Crítico | Pendente |
| Gerar JWT secret forte | 🔴 Crítico | Pendente |
| Adicionar .env ao .gitignore | 🔴 Crítico | Pendente |
| Configurar HTTPS/SSL | 🟡 Importante | Pendente |
| Rate limiting na API | 🟡 Importante | Pendente |
| Backup automatizado | 🟡 Importante | Pendente |
| CSP headers completo | 🟢 Recomendado | Pendente |
| Logging centralizado | 🟢 Recomendado | Pendente |

---

## 📞 SUPORTE

Para dúvidas sobre o deploy:
1. Consulte `DOCKER.md` e `COOLIFY_DEPLOY.md`
2. Verifique os logs via Portainer
3. Execute healthchecks manualmente

---

**Última atualização:** 23/12/2024
