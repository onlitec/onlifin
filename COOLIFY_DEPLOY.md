# 🚀 Deploy do Onlifin no Coolify

Este guia explica como fazer o deploy da plataforma Onlifin em um VPS usando o Coolify.

## 📦 Arquitetura

A plataforma Onlifin usa uma arquitetura de containers Docker:

| Imagem | Descrição | DockerHub |
|--------|-----------|-----------|
| `onlitec/onlifin` | Frontend React + Nginx | [Link](https://hub.docker.com/r/onlitec/onlifin) |
| `onlitec/onlifin-db` | PostgreSQL com schemas | [Link](https://hub.docker.com/r/onlitec/onlifin-db) |
| `postgrest/postgrest` | API REST automática | Imagem oficial |
| `ollama/ollama` | IA Local | Imagem oficial |

**Nota:** Esta plataforma usa PostgreSQL nativo, não Supabase.

---

## 📋 Pré-requisitos

- VPS com mínimo **4GB RAM** (recomendado 8GB para IA)
- Coolify instalado e configurado
- Domínio configurado (opcional, mas recomendado)

---

## 🔧 Deploy via Docker Compose

### Passo 1: Criar novo projeto no Coolify

1. Acesse o painel do Coolify
2. Clique em **"New Project"**
3. Dê um nome: `Onlifin`

### Passo 2: Adicionar serviço Docker Compose

1. No projeto criado, clique em **"+ New"**
2. Selecione **"Docker Compose"**
3. Escolha **"Empty Docker Compose"**

### Passo 3: Configurar Docker Compose

Cole o seguinte conteúdo:

```yaml
version: '3.8'

services:
  app:
    image: onlitec/onlifin:4.0.0.0
    ports:
      - "80:80"
    depends_on:
      api:
        condition: service_started
      ollama:
        condition: service_started
    environment:
      - API_URL=http://api:3000
      - OLLAMA_URL=http://ollama:11434
    restart: unless-stopped
    networks:
      - onlifin-network

  db:
    image: onlitec/onlifin-db:4.0.0.0
    environment:
      POSTGRES_DB: onlifin
      POSTGRES_USER: onlifin
      POSTGRES_PASSWORD: ${POSTGRES_PASSWORD}
    volumes:
      - postgres_data:/var/lib/postgresql/data
    restart: unless-stopped
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U onlifin -d onlifin"]
      interval: 10s
      timeout: 5s
      retries: 5
      start_period: 30s
    networks:
      - onlifin-network

  api:
    image: postgrest/postgrest:v12.0.2
    depends_on:
      db:
        condition: service_healthy
    environment:
      PGRST_DB_URI: postgres://onlifin:${POSTGRES_PASSWORD}@db:5432/onlifin
      PGRST_DB_SCHEMA: public
      PGRST_DB_ANON_ROLE: anon
      PGRST_JWT_SECRET: ${JWT_SECRET}
    restart: unless-stopped
    networks:
      - onlifin-network

  ollama:
    image: ollama/ollama:latest
    volumes:
      - ollama_data:/root/.ollama
    restart: unless-stopped
    deploy:
      resources:
        limits:
          memory: 4G
    networks:
      - onlifin-network

networks:
  onlifin-network:
    driver: bridge

volumes:
  postgres_data:
  ollama_data:
```

### Passo 4: Configurar Variáveis de Ambiente

No Coolify, vá em **Environment Variables** e adicione:

| Variável | Valor | Descrição |
|----------|-------|-----------|
| `POSTGRES_PASSWORD` | `SuaSenhaSegura123!` | **⚠️ ALTERE!** Senha do banco |
| `JWT_SECRET` | `seu-jwt-secret-32-chars-min` | **⚠️ ALTERE!** Chave JWT |

**Gerar senhas seguras:**
```bash
# JWT Secret (32+ caracteres)
openssl rand -base64 32

# Senha do banco
openssl rand -base64 24
```

### Passo 5: Configurar Domínio (Opcional)

1. Vá em **Domains**
2. Adicione seu domínio: `onlifin.seudominio.com`
3. Ative **HTTPS** (Let's Encrypt)

### Passo 6: Deploy

Clique em **"Deploy"** e aguarde a inicialização dos containers.

---

## 🤖 Configuração do Ollama (IA)

**IMPORTANTE:** Após o primeiro deploy, você DEVE baixar o modelo de IA:

### Via Coolify Terminal

1. Acesse o container `ollama` pelo terminal
2. Execute:

```bash
ollama pull qwen2.5:0.5b
```

### Via SSH no VPS

```bash
docker exec -it onlifin-ollama ollama pull qwen2.5:0.5b
```

### Modelos Recomendados

| Modelo | RAM Mínima | Qualidade |
|--------|------------|-----------|
| `qwen2.5:0.5b` | 2GB | ⭐⭐ Básico |
| `qwen2.5:1.5b` | 4GB | ⭐⭐⭐ Bom |
| `llama3.2:3b` | 6GB | ⭐⭐⭐⭐ Muito bom |
| `llama3.2:7b` | 12GB | ⭐⭐⭐⭐⭐ Excelente |

---

## 📊 Requisitos do VPS

| Recurso | Mínimo | Recomendado |
|---------|--------|-------------|
| **RAM** | 4GB | 8GB+ |
| **CPU** | 2 cores | 4 cores |
| **Disco** | 20GB | 50GB |

---

## 🔒 Segurança

### Senhas e Secrets

⚠️ **IMPORTANTE**: Altere todas as senhas padrão antes do deploy!

### Firewall

Configure seu firewall para expor apenas:
- ✅ Porta 80/443 (HTTP/HTTPS) - Frontend

**NÃO exponha publicamente:**
- ❌ Porta 5432 - PostgreSQL
- ❌ Porta 3000 - API REST
- ❌ Porta 11434 - Ollama

---

## 📋 Verificação do Deploy

### Verificar containers

```bash
docker ps | grep onlifin
```

Você deve ver 4 containers:
- `onlifin-app` 
- `onlifin-db`
- `onlifin-api`
- `onlifin-ollama`

### Verificar banco de dados

```bash
docker exec -it onlifin-db psql -U onlifin -d onlifin -c "\dt"
```

Deve listar as tabelas: `users`, `accounts`, `categories`, `transactions`, etc.

### Verificar Ollama

```bash
docker exec -it onlifin-ollama ollama list
```

Deve mostrar o modelo baixado.

---

## 🔄 Atualizações

Para atualizar para uma nova versão:

1. No Coolify, vá ao serviço
2. Altere a tag das imagens (ex: `4.0.0.0` → `4.1.0.0`)
3. Clique em **"Redeploy"**

Ou use `latest` para sempre pegar a versão mais recente.

---

## 📝 Logs

Acesse os logs pelo painel do Coolify ou via SSH:

```bash
# Logs do frontend
docker logs -f onlifin-app

# Logs do banco
docker logs -f onlifin-db

# Logs da API
docker logs -f onlifin-api

# Logs do Ollama
docker logs -f onlifin-ollama
```

---

## ❓ Troubleshooting

### Container não inicia

```bash
# Verificar logs
docker logs onlifin-app

# Verificar uso de recursos
docker stats
```

### Erro de conexão com banco

1. Verifique se `onlifin-db` está saudável: `docker ps`
2. Confirme as credenciais nas variáveis de ambiente
3. Teste conexão:
```bash
docker exec -it onlifin-db psql -U onlifin -d onlifin -c "SELECT 1"
```

### Ollama sem resposta

1. Verifique se o modelo foi baixado:
```bash
docker exec -it onlifin-ollama ollama list
```

2. Verifique a memória disponível:
```bash
docker stats onlifin-ollama
```

### API retorna 503

A API depende do banco estar saudável. Verifique:
```bash
docker logs onlifin-api
docker exec -it onlifin-db pg_isready -U onlifin
```

---

## 📞 Suporte

- **GitHub**: https://github.com/onlitec/onlifin
- **Issues**: https://github.com/onlitec/onlifin/issues
- **DockerHub**: https://hub.docker.com/r/onlitec/onlifin
