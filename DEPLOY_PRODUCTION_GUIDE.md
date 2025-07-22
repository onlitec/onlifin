# 🚀 Guia de Deploy para Produção - Onlifin API

## Visão Geral

Este guia detalha como atualizar a versão online de produção da plataforma Onlifin com todas as implementações da API para o app Android.

## ⚠️ Pré-requisitos

- [ ] Acesso SSH ao servidor de produção
- [ ] Backup completo do banco de dados atual
- [ ] Backup dos arquivos da aplicação
- [ ] Acesso ao repositório Git
- [ ] Verificação de que não há usuários críticos online

## 📋 Checklist de Deploy

### 1. Preparação (Ambiente Local)

#### 1.1 Verificar Implementações
```bash
# Verificar se todos os arquivos foram criados
ls -la app/Http/Controllers/Api/
ls -la app/Http/Resources/Api/
ls -la app/Http/Middleware/Api*
ls -la tests/Feature/Api/
```

#### 1.2 Executar Testes Locais
```bash
# Executar testes da API
php artisan test tests/Feature/Api/

# Verificar rotas da API
php artisan route:list --path=api
```

#### 1.3 Commit e Push das Alterações
```bash
# Adicionar todos os arquivos novos
git add .

# Commit com mensagem descritiva
git commit -m "feat: Implementar API completa para app Android

- Adicionar autenticação com Laravel Sanctum
- Implementar CRUD completo para transações, contas e categorias
- Adicionar sistema de relatórios via API
- Integrar funcionalidades de IA
- Configurar middleware CORS e rate limiting
- Adicionar testes automatizados
- Criar documentação completa da API"

# Push para repositório
git push origin main
```

### 2. Backup de Segurança

#### 2.1 Backup do Banco de Dados
```bash
# No servidor de produção
mysqldump -u [usuario] -p[senha] onlifin_production > backup_pre_api_$(date +%Y%m%d_%H%M%S).sql

# Ou se usando PostgreSQL
pg_dump -U [usuario] -h localhost onlifin_production > backup_pre_api_$(date +%Y%m%d_%H%M%S).sql
```

#### 2.2 Backup dos Arquivos
```bash
# Backup completo da aplicação
tar -czf backup_onlifin_$(date +%Y%m%d_%H%M%S).tar.gz /var/www/html/onlifin/

# Backup apenas dos arquivos críticos
cp -r /var/www/html/onlifin/.env /backup/
cp -r /var/www/html/onlifin/storage/ /backup/storage_backup/
```

### 3. Deploy no Servidor

#### 3.1 Conectar ao Servidor
```bash
ssh usuario@seu-servidor.com
cd /var/www/html/onlifin
```

#### 3.2 Ativar Modo de Manutenção
```bash
php artisan down --message="Atualizando sistema com nova API" --retry=60
```

#### 3.3 Atualizar Código
```bash
# Fazer backup da branch atual
git branch backup-pre-api-$(date +%Y%m%d_%H%M%S)

# Atualizar código
git fetch origin
git pull origin main

# Verificar se todos os arquivos foram baixados
ls -la app/Http/Controllers/Api/
```

#### 3.4 Instalar/Atualizar Dependências
```bash
# Atualizar dependências do Composer
composer install --no-dev --optimize-autoloader

# Limpar caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

#### 3.5 Executar Migrações
```bash
# Verificar migrações pendentes
php artisan migrate:status

# Executar migrações (CUIDADO: sempre fazer backup antes!)
php artisan migrate --force

# Verificar se a tabela personal_access_tokens foi criada
php artisan tinker
>>> \Schema::hasTable('personal_access_tokens')
>>> exit
```

#### 3.6 Configurar Permissões
```bash
# Ajustar permissões dos diretórios
chown -R www-data:www-data storage/ bootstrap/cache/
chmod -R 775 storage/ bootstrap/cache/

# Verificar permissões
ls -la storage/
ls -la bootstrap/cache/
```

#### 3.7 Otimizar para Produção
```bash
# Otimizar autoloader
composer dump-autoload --optimize

# Cache de configuração
php artisan config:cache

# Cache de rotas
php artisan route:cache

# Cache de views
php artisan view:cache
```

### 4. Configuração do Ambiente

#### 4.1 Atualizar .env para Produção
```bash
# Editar arquivo .env
nano .env
```

Configurações importantes para API:
```env
# URL da aplicação (ajustar para seu domínio)
APP_URL=https://onlifin.onlitec.com.br

# Configurações do Sanctum
SANCTUM_STATEFUL_DOMAINS=onlifin.onlitec.com.br,www.onlifin.onlitec.com.br
SESSION_DOMAIN=onlifin.onlitec.com.br

# Rate limiting para API
API_RATE_LIMIT=60

# CORS para app Android
CORS_ALLOWED_ORIGINS=*
```

#### 4.2 Configurar HTTPS (se necessário)
```bash
# Verificar certificado SSL
certbot certificates

# Renovar se necessário
certbot renew
```

### 5. Configuração do Servidor Web

#### 5.1 Nginx - Adicionar Configurações para API
```nginx
# /etc/nginx/sites-available/onlifin
server {
    listen 80;
    listen 443 ssl;
    server_name onlifin.onlitec.com.br;
    
    root /var/www/html/onlifin/public;
    index index.php index.html;
    
    # Configurações específicas para API
    location /api {
        try_files $uri $uri/ /index.php?$query_string;
        
        # Headers CORS
        add_header 'Access-Control-Allow-Origin' '*' always;
        add_header 'Access-Control-Allow-Methods' 'GET, POST, PUT, DELETE, OPTIONS' always;
        add_header 'Access-Control-Allow-Headers' 'Authorization, Content-Type, Accept, Origin, X-Requested-With' always;
        
        # Preflight requests
        if ($request_method = 'OPTIONS') {
            add_header 'Access-Control-Allow-Origin' '*';
            add_header 'Access-Control-Allow-Methods' 'GET, POST, PUT, DELETE, OPTIONS';
            add_header 'Access-Control-Allow-Headers' 'Authorization, Content-Type, Accept, Origin, X-Requested-With';
            add_header 'Access-Control-Max-Age' 86400;
            add_header 'Content-Length' 0;
            add_header 'Content-Type' 'text/plain';
            return 204;
        }
    }
    
    # Configuração PHP
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        
        # Timeout aumentado para IA
        fastcgi_read_timeout 300;
    }
    
    # SSL configuration
    ssl_certificate /etc/letsencrypt/live/onlifin.onlitec.com.br/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/onlifin.onlitec.com.br/privkey.pem;
}
```

#### 5.2 Reiniciar Serviços
```bash
# Testar configuração do Nginx
nginx -t

# Reiniciar Nginx
systemctl reload nginx

# Reiniciar PHP-FPM
systemctl restart php8.2-fpm
```

### 6. Testes em Produção

#### 6.1 Desativar Modo de Manutenção
```bash
php artisan up
```

#### 6.2 Testes Básicos da API
```bash
# Teste de documentação
curl -X GET "https://onlifin.onlitec.com.br/api/docs" -H "Accept: application/json"

# Teste de registro (usar dados de teste)
curl -X POST "https://onlifin.onlitec.com.br/api/auth/register" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Teste API",
    "email": "teste-api@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "device_name": "Teste Deploy"
  }'

# Teste de login
curl -X POST "https://onlifin.onlitec.com.br/api/auth/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "teste-api@example.com",
    "password": "password123",
    "device_name": "Teste Deploy"
  }'
```

#### 6.3 Verificar Logs
```bash
# Logs do Laravel
tail -f storage/logs/laravel.log

# Logs do Nginx
tail -f /var/log/nginx/error.log
tail -f /var/log/nginx/access.log

# Logs do PHP
tail -f /var/log/php8.2-fpm.log
```

### 7. Monitoramento Pós-Deploy

#### 7.1 Verificar Performance
```bash
# Verificar uso de recursos
htop

# Verificar conexões de banco
mysql -u root -p -e "SHOW PROCESSLIST;"

# Verificar espaço em disco
df -h
```

#### 7.2 Configurar Monitoramento
```bash
# Adicionar cron job para limpeza de logs
crontab -e

# Adicionar linha:
0 2 * * * cd /var/www/html/onlifin && php artisan telescope:prune --hours=48
```

### 8. Rollback (Se Necessário)

#### 8.1 Rollback do Código
```bash
# Ativar modo de manutenção
php artisan down

# Voltar para commit anterior
git reset --hard backup-pre-api-YYYYMMDD_HHMMSS

# Restaurar dependências
composer install --no-dev --optimize-autoloader

# Limpar caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

#### 8.2 Rollback do Banco (Se Necessário)
```bash
# CUIDADO: Isso apagará dados criados após o backup
mysql -u [usuario] -p[senha] onlifin_production < backup_pre_api_YYYYMMDD_HHMMSS.sql
```

## 📞 Contatos de Emergência

- **Desenvolvedor**: [seu-email@example.com]
- **Servidor**: [provedor-hosting]
- **Banco de Dados**: [admin-db]

## 📝 Checklist Final

- [ ] Backup realizado com sucesso
- [ ] Código atualizado sem erros
- [ ] Migrações executadas
- [ ] Permissões configuradas
- [ ] API funcionando (testes básicos)
- [ ] Logs sem erros críticos
- [ ] Performance normal
- [ ] Usuários conseguem acessar normalmente
- [ ] App web funcionando normalmente
- [ ] Documentação da API acessível

## 🎉 Deploy Concluído!

Após seguir todos os passos, a API estará disponível em produção nos endpoints:

- **Base URL**: `https://onlifin.onlitec.com.br/api`
- **Documentação**: `https://onlifin.onlitec.com.br/api/docs`
- **OpenAPI**: `https://onlifin.onlitec.com.br/api/docs/openapi`

O app Android poderá se conectar usando a URL de produção e todos os recursos implementados estarão disponíveis!
