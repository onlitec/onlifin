# 🔒 Configurações de Segurança - Onlifin

## ⚠️ IMPORTANTE - Configurações de Segurança

### 1. Arquivo .env.example
Crie um arquivo `.env.example` com as seguintes configurações:

```bash
# ========================================================================
# ONLIFIN - CONFIGURAÇÕES DE AMBIENTE
# ========================================================================
# 
# IMPORTANTE: 
# 1. Copie este arquivo para .env e configure as variáveis
# 2. NUNCA commite o arquivo .env no repositório
# 3. Use senhas fortes e únicas para produção
# 4. Mantenha as chaves de API em local seguro
#
# ========================================================================

# ========================================================================
# CONFIGURAÇÕES BÁSICAS DA APLICAÇÃO
# ========================================================================
APP_NAME="Onlifin"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://seudominio.com

# ========================================================================
# CONFIGURAÇÕES DE BANCO DE DADOS
# ========================================================================
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=onlifin
DB_USERNAME=onlifin_user
DB_PASSWORD=senha_super_segura_aqui
DB_SSL_MODE=preferred

# ========================================================================
# CONFIGURAÇÕES DE CACHE E SESSÃO
# ========================================================================
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# ========================================================================
# CONFIGURAÇÕES REDIS
# ========================================================================
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=senha_redis_segura
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1

# ========================================================================
# CONFIGURAÇÕES DE EMAIL
# ========================================================================
MAIL_MAILER=smtp
MAIL_HOST=seu_servidor_smtp.com
MAIL_PORT=587
MAIL_USERNAME=seu_email@seudominio.com
MAIL_PASSWORD=senha_email_segura
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@seudominio.com
MAIL_FROM_NAME="${APP_NAME}"

# ========================================================================
# CONFIGURAÇÕES GOOGLE OAUTH2
# ========================================================================
GOOGLE_CLIENT_ID=seu_google_client_id
GOOGLE_CLIENT_SECRET=seu_google_client_secret
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"

# ========================================================================
# CONFIGURAÇÕES DE SEGURANÇA
# ========================================================================
# Configurações de sessão
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=strict

# Configurações de cookies
COOKIE_SECURE=true
COOKIE_HTTP_ONLY=true
COOKIE_SAME_SITE=strict

# Rate limiting
RATE_LIMIT_PER_MINUTE=60
RATE_LIMIT_API_PER_MINUTE=100
```

### 2. Configurações de Segurança Implementadas

#### ✅ Docker Compose Seguro
- Removidas credenciais hardcoded
- Implementadas variáveis de ambiente
- Adicionado healthcheck

#### ✅ Headers de Segurança
- Configurações de sessão seguras
- Cookies seguros
- Rate limiting implementado

### 3. Próximos Passos de Segurança

1. **Implementar Secrets Management**
2. **Configurar Headers de Segurança HTTP**
3. **Fortalecer Validação de Entrada**
4. **Implementar Logs de Auditoria**
5. **Configurar Backup Seguro**

### 4. Comandos de Segurança

```bash
# Gerar chave da aplicação
php artisan key:generate

# Limpar cache de configuração
php artisan config:clear

# Verificar configurações de segurança
php artisan config:show
```

### 5. Checklist de Segurança

- [ ] Arquivo .env criado com senhas seguras
- [ ] Credenciais removidas do docker-compose.yml
- [ ] Headers de segurança configurados
- [ ] Rate limiting ativo
- [ ] Logs de auditoria funcionando
- [ ] Backup seguro configurado
- [ ] SSL/HTTPS configurado
- [ ] Firewall configurado
- [ ] Monitoramento ativo
