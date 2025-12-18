# 🚀 Deploy do Onlifin no Coolify

Este guia explica como fazer o deploy da plataforma Onlifin em um VPS usando o Coolify.

## 📋 Pré-requisitos

- VPS com mínimo 4GB RAM (recomendado 8GB para IA)
- Coolify instalado e configurado
- Domínio configurado (opcional, mas recomendado)

## 🔧 Opção 1: Deploy via Docker Compose (Recomendado)

### Passo 1: Criar novo projeto no Coolify

1. Acesse o painel do Coolify
2. Clique em **"New Project"**
3. Dê um nome: `Onlifin`

### Passo 2: Adicionar serviço Docker Compose

1. No projeto criado, clique em **"+ New"**
2. Selecione **"Docker Compose"**
3. Escolha **"Empty Docker Compose"**

### Passo 3: Configurar Docker Compose

Cole o conteúdo do arquivo `docker-compose.coolify.yml`:

```yaml
version: '3.8'

services:
  app:
    image: onlitec/onlifin:latest
    ports:
      - "80:80"
    depends_on:
      - api
      - ollama
    environment:
      - API_URL=http://api:3000
      - OLLAMA_URL=http://ollama:11434
    restart: unless-stopped
    networks:
      - onlifin-network

  db:
    image: postgres:16-alpine
    environment:
      POSTGRES_DB: ${POSTGRES_DB:-onlifin}
      POSTGRES_USER: ${POSTGRES_USER:-onlifin}
      POSTGRES_PASSWORD: ${POSTGRES_PASSWORD}
    volumes:
      - postgres_data:/var/lib/postgresql/data
    restart: unless-stopped
    networks:
      - onlifin-network

  api:
    image: postgrest/postgrest
    depends_on:
      - db
    environment:
      PGRST_DB_URI: postgres://${POSTGRES_USER:-onlifin}:${POSTGRES_PASSWORD}@db:5432/${POSTGRES_DB:-onlifin}
      PGRST_DB_SCHEMA: public
      PGRST_DB_ANON_ROLE: anon
      PGRST_JWT_SECRET: ${JWT_SECRET}
    restart: unless-stopped
    networks:
      - onlifin-network

  ollama:
    image: ollama/ollama
    volumes:
      - ollama_data:/root/.ollama
    restart: unless-stopped
    networks:
      - onlifin-network
    deploy:
      resources:
        limits:
          memory: 4G

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
| `POSTGRES_DB` | `onlifin` | Nome do banco de dados |
| `POSTGRES_USER` | `onlifin` | Usuário do banco |
| `POSTGRES_PASSWORD` | `SuaSenhaSegura123!` | **⚠️ ALTERE PARA UMA SENHA FORTE** |
| `JWT_SECRET` | `seu-jwt-secret-minimo-32-caracteres` | **⚠️ ALTERE PARA UMA CHAVE SEGURA** |

### Passo 5: Configurar Domínio (Opcional)

1. Vá em **Domains**
2. Adicione seu domínio: `onlifin.seudominio.com`
3. Ative **HTTPS** (Let's Encrypt)

### Passo 6: Deploy

Clique em **"Deploy"** e aguarde a inicialização dos containers.

---

## 🔧 Opção 2: Deploy via Imagem Docker Simples

Se você quer apenas o frontend (sem banco de dados local):

### Passo 1: Adicionar serviço Docker

1. No Coolify, clique em **"+ New"**
2. Selecione **"Docker Image"**
3. Use a imagem: `onlitec/onlifin:latest`

### Passo 2: Configurar porta

- **Porta exposta:** `80`
- **Porta pública:** `80` ou `443` (com HTTPS)

### Passo 3: Variáveis de Ambiente

Configure as variáveis para apontar para seu Supabase externo:

| Variável | Valor |
|----------|-------|
| `VITE_SUPABASE_URL` | `https://seu-projeto.supabase.co` |
| `VITE_SUPABASE_ANON_KEY` | `sua-anon-key` |

---

## 🗄️ Inicialização do Banco de Dados

Após o primeiro deploy, você precisa inicializar o banco de dados:

### Via Coolify Terminal

1. Acesse o container `db` pelo terminal do Coolify
2. Execute:

```bash
psql -U onlifin -d onlifin -f /docker-entrypoint-initdb.d/01-auth-schema.sql
psql -U onlifin -d onlifin -f /docker-entrypoint-initdb.d/02-main-schema.sql
```

### Via Script Remoto

```bash
# Conectar ao banco via psql
docker exec -it onlifin-db psql -U onlifin -d onlifin

# Dentro do psql, execute os scripts de criação
```

---

## 🤖 Configuração do Ollama (IA)

Após o deploy, você precisa baixar o modelo de IA:

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

| Modelo | RAM Mínima | Uso |
|--------|------------|-----|
| `qwen2.5:0.5b` | 2GB | Leve, respostas rápidas |
| `qwen2.5:1.5b` | 4GB | Balanceado |
| `llama3.2:3b` | 6GB | Mais inteligente |
| `llama3.2:7b` | 12GB | Melhor qualidade |

---

## 🔒 Segurança

### Senhas e Secrets

⚠️ **IMPORTANTE**: Altere todas as senhas padrão!

Gere senhas seguras:
```bash
# JWT Secret (32+ caracteres)
openssl rand -base64 32

# Senha do banco
openssl rand -base64 24
```

### Firewall

Configure seu firewall para expor apenas:
- Porta 80/443 (HTTP/HTTPS) - Frontend
- Porta 3000 (opcional) - API REST

**NÃO exponha:**
- Porta 5432 - PostgreSQL
- Porta 11434 - Ollama

---

## 📊 Monitoramento

### Health Checks

Os containers têm health checks configurados. Verifique no Coolify:

- 🟢 **Healthy** - Container funcionando
- 🟡 **Starting** - Iniciando
- 🔴 **Unhealthy** - Problema detectado

### Logs

Acesse os logs pelo painel do Coolify ou via SSH:

```bash
# Logs do frontend
docker logs onlifin-app

# Logs do banco
docker logs onlifin-db

# Logs da API
docker logs onlifin-api

# Logs do Ollama
docker logs onlifin-ollama
```

---

## 🔄 Atualizações

Para atualizar para uma nova versão:

1. No Coolify, vá ao serviço
2. Altere a tag da imagem (ex: `onlitec/onlifin:4.0.0.0` → `onlitec/onlifin:4.1.0.0`)
3. Clique em **"Redeploy"**

Ou use `latest` para sempre pegar a versão mais recente.

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

1. Verifique se o container `db` está rodando
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

2. Baixe o modelo se necessário:
```bash
docker exec -it onlifin-ollama ollama pull qwen2.5:0.5b
```

---

## 📞 Suporte

- **GitHub Issues**: https://github.com/onlitec/onlifin/issues
- **DockerHub**: https://hub.docker.com/r/onlitec/onlifin
