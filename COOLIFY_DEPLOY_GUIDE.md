# 🚀 Guia de Deploy no Coolify - Onlifin

## 📋 Visão Geral

Este guia explica como fazer deploy da plataforma Onlifin no **Coolify** usando duas abordagens:

1. **Docker Image Única** (Mais simples - SQLite)
2. **Docker Compose Multi-Container** (Recomendado - MySQL + Redis)

## 🎯 **Opção 1: Docker Image Única (Simples)**

### **Passo 1: Criar Novo Serviço**
1. Acesse seu painel **Coolify**
2. Clique em **"New Resource"**
3. Selecione **"Docker Image"**
4. Configure:
   - **Name**: `onlifin-app`
   - **Docker Image**: `onlitec/onlifin:api`
   - **Port**: `80`

### **Passo 2: Configurar Variáveis de Ambiente**
```env
# Aplicação
APP_NAME=Onlifin
APP_ENV=production
APP_DEBUG=false
APP_URL=https://seu-app.coolify.app
APP_KEY=base64:SUA_CHAVE_GERADA_AQUI

# Banco SQLite (simples)
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/database/database.sqlite

# Cache arquivo (simples)
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

# API Sanctum
SANCTUM_STATEFUL_DOMAINS=seu-app.coolify.app
SESSION_DOMAIN=seu-app.coolify.app
API_RATE_LIMIT=60
API_RATE_LIMIT_UNAUTHENTICATED=10

# CORS
CORS_ALLOWED_ORIGINS=*
CORS_ALLOWED_METHODS=GET,POST,PUT,DELETE,OPTIONS
CORS_ALLOWED_HEADERS=Content-Type,Authorization,X-Requested-With,Accept,Origin

# IA (opcional)
GROQ_API_KEY=sua_chave_groq_aqui
GROQ_MODEL=llama3-8b-8192

# Localização
APP_TIMEZONE=America/Sao_Paulo
APP_LOCALE=pt_BR
```

### **Passo 3: Configurar Volumes**
```yaml
# Para persistir dados
Volumes:
  - /var/www/html/storage:/data/storage
  - /var/www/html/database:/data/database
  - /var/www/html/public/uploads:/data/uploads
```

### **Passo 4: Deploy**
1. Clique em **"Deploy"**
2. Aguarde o container inicializar (2-3 minutos)
3. Acesse a URL gerada pelo Coolify

### **✅ Resultado:**
- ✅ Setup em **5 minutos**
- ✅ **SQLite** como banco
- ✅ **API completa** funcionando
- ✅ Ideal para **desenvolvimento/testes**

---

## 🏗️ **Opção 2: Docker Compose Multi-Container (Recomendado)**

### **Passo 1: Criar Novo Projeto**
1. Acesse seu painel **Coolify**
2. Clique em **"New Resource"**
3. Selecione **"Docker Compose"**
4. Configure:
   - **Name**: `onlifin-production`
   - **Description**: `Onlifin Multi-Container with MySQL + Redis`

### **Passo 2: Adicionar Docker Compose**
Cole o conteúdo do arquivo `docker-compose.coolify.yml`:

```yaml
version: '3.8'

services:
  onlifin-app:
    image: onlitec/onlifin:api
    restart: unless-stopped
    environment:
      APP_NAME: "Onlifin"
      APP_ENV: production
      APP_DEBUG: "false"
      APP_URL: "${APP_URL}"
      APP_KEY: "${APP_KEY}"
      
      # MySQL
      DB_CONNECTION: mysql
      DB_HOST: onlifin-db
      DB_DATABASE: onlifin_production
      DB_USERNAME: onlifin_user
      DB_PASSWORD: "${DB_PASSWORD}"
      
      # Redis
      CACHE_DRIVER: redis
      SESSION_DRIVER: redis
      REDIS_HOST: onlifin-redis
      REDIS_PASSWORD: "${REDIS_PASSWORD}"
      
      # API
      SANCTUM_STATEFUL_DOMAINS: "${SANCTUM_DOMAINS}"
      API_RATE_LIMIT: 60
      
      # CORS
      CORS_ALLOWED_ORIGINS: "*"
      
      # IA
      GROQ_API_KEY: "${GROQ_API_KEY}"
    
    volumes:
      - onlifin_storage:/var/www/html/storage
      - onlifin_uploads:/var/www/html/public/uploads
    
    depends_on:
      - onlifin-db
      - onlifin-redis
    
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost/api/docs"]
      interval: 30s
      timeout: 10s
      retries: 3

  onlifin-db:
    image: mysql:8.0
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: "${MYSQL_ROOT_PASSWORD}"
      MYSQL_DATABASE: onlifin_production
      MYSQL_USER: onlifin_user
      MYSQL_PASSWORD: "${DB_PASSWORD}"
    volumes:
      - onlifin_mysql_data:/var/lib/mysql
    command: --default-authentication-plugin=mysql_native_password

  onlifin-redis:
    image: redis:7-alpine
    restart: unless-stopped
    command: redis-server --requirepass ${REDIS_PASSWORD}
    volumes:
      - onlifin_redis_data:/data

volumes:
  onlifin_storage:
  onlifin_uploads:
  onlifin_mysql_data:
  onlifin_redis_data:
```

### **Passo 3: Configurar Variáveis de Ambiente**
No painel do Coolify, adicione estas variáveis:

```env
# Obrigatórias
APP_URL=https://seu-app.coolify.app
APP_KEY=base64:8Xj9wGZvVQKxP2mN5cR7tY1uI3oA6sD9fG2hJ4kL8mN
DB_PASSWORD=Xy9Zk2Mn8Qp3Rt6Vw1Yx4Az7Bc5De2Fg
MYSQL_ROOT_PASSWORD=Lm9Pq3Rs6Uv2Yz5Bc8Ef1Gh4Jk7Mn0Pq
REDIS_PASSWORD=Cd6Fg9Hi2Kl5No8Qr1Tu4Vw7Xy0Za3Bc
SANCTUM_DOMAINS=seu-app.coolify.app
SESSION_DOMAIN=seu-app.coolify.app

# Opcionais
GROQ_API_KEY=sua_chave_groq
MAIL_HOST=smtp.gmail.com
MAIL_USERNAME=seu_email@gmail.com
MAIL_PASSWORD=sua_senha_app
```

### **Passo 4: Deploy**
1. Clique em **"Deploy"**
2. Aguarde todos os containers inicializarem (3-5 minutos)
3. Verifique se todos os serviços estão "healthy"

### **Passo 5: Executar Migrações**
1. Acesse o **terminal** do container `onlifin-app`
2. Execute: `php artisan migrate --force`

### **✅ Resultado:**
- ✅ **MySQL dedicado** para performance
- ✅ **Redis** para cache rápido
- ✅ **Backup** facilitado
- ✅ **Escalabilidade** horizontal
- ✅ **Produção** robusta

---

## 🔧 **Configurações Específicas do Coolify**

### **Domínio Personalizado**
1. No painel do Coolify, vá em **"Domains"**
2. Adicione seu domínio: `onlifin.seudominio.com`
3. Configure SSL automático
4. Atualize `APP_URL` e `SANCTUM_DOMAINS`

### **Backup Automático**
```yaml
# Adicionar ao docker-compose se necessário
onlifin-backup:
  image: mysql:8.0
  restart: unless-stopped
  environment:
    MYSQL_HOST: onlifin-db
    MYSQL_USER: onlifin_user
    MYSQL_PASSWORD: "${DB_PASSWORD}"
  volumes:
    - ./backups:/backups
  entrypoint: |
    sh -c '
      echo "0 2 * * * mysqldump -h onlifin-db -u onlifin_user -p${DB_PASSWORD} onlifin_production > /backups/backup_$(date +%Y%m%d).sql" | crontab -
      crond -f
    '
```

### **Monitoramento**
O Coolify fornece automaticamente:
- ✅ **Logs** em tempo real
- ✅ **Métricas** de recursos
- ✅ **Health checks**
- ✅ **Alertas** por email/webhook

---

## 📱 **Para o App Android**

### **URLs Finais:**
```kotlin
// Após deploy no Coolify
const val BASE_URL = "https://seu-app.coolify.app/api"
const val DOCS_URL = "https://seu-app.coolify.app/api/docs"
```

### **Headers Obrigatórios:**
```kotlin
headers = mapOf(
    "Authorization" to "Bearer $token",
    "Content-Type" to "application/json",
    "Accept" to "application/json",
    "User-Agent" to "OnlifinAndroid/1.0"
)
```

---

## 🚨 **Troubleshooting**

### **Container não inicia:**
1. Verifique **logs** no painel Coolify
2. Confirme **variáveis de ambiente**
3. Verifique **recursos** disponíveis

### **API não responde:**
```bash
# Testar no terminal do container
curl -f http://localhost/api/docs
php artisan route:list --path=api
```

### **Banco não conecta:**
```bash
# Testar conexão MySQL
mysql -h onlifin-db -u onlifin_user -p
```

---

## 📊 **Comparação das Opções**

| Aspecto | Docker Image | Docker Compose |
|---------|-------------|----------------|
| **Setup** | ⚡ 5 min | 🔧 10 min |
| **Performance** | 📊 Boa | 📊 Excelente |
| **Banco** | 📁 SQLite | 🗄️ MySQL |
| **Cache** | 📄 Arquivo | 🔴 Redis |
| **Backup** | ⚠️ Manual | ✅ Automático |
| **Escalabilidade** | ⚖️ Limitada | ⚖️ Horizontal |
| **Recursos** | 💾 ~500MB | 💾 ~1.5GB |
| **Produção** | ⚠️ Pequeno porte | ✅ Recomendado |

---

## 🎉 **Recomendação Final**

### **Para Desenvolvimento/Testes:**
✅ Use **Docker Image Única** com SQLite

### **Para Produção:**
✅ Use **Docker Compose Multi-Container** com MySQL + Redis

### **Vantagens do Coolify:**
- ✅ **Interface visual** amigável
- ✅ **SSL automático** com Let's Encrypt
- ✅ **Monitoramento** integrado
- ✅ **Backup** facilitado
- ✅ **Logs** centralizados
- ✅ **Deploy** com um clique

**🚀 Com o Coolify, você terá a plataforma Onlifin rodando em produção de forma profissional e fácil de gerenciar!**
