# ✅ Checklist Pré-Deploy - Onlifin API

## 🔍 Verificações Obrigatórias

### 1. Ambiente Local
- [ ] Todos os testes passando: `php artisan test tests/Feature/Api/`
- [ ] Rotas da API carregando: `php artisan route:list --path=api`
- [ ] Sem erros de sintaxe: `php artisan config:clear && php artisan route:clear`
- [ ] Migrações funcionando: `php artisan migrate:status`
- [ ] Sanctum configurado: verificar `config/sanctum.php`

### 2. Código e Arquivos
- [ ] Todos os controladores API criados em `app/Http/Controllers/Api/`
- [ ] Middleware personalizado em `app/Http/Middleware/`
- [ ] Resources criados em `app/Http/Resources/Api/`
- [ ] Testes criados em `tests/Feature/Api/`
- [ ] Documentação atualizada (`API_DOCUMENTATION.md`)

### 3. Configurações
- [ ] Bootstrap/app.php atualizado com rotas API
- [ ] Kernel.php com middlewares registrados
- [ ] Routes/api.php com todas as rotas
- [ ] Config/sanctum.php configurado
- [ ] .env.production.example criado

### 4. Banco de Dados
- [ ] Migration do Sanctum existe: `database/migrations/*_create_personal_access_tokens_table.php`
- [ ] Todas as tabelas necessárias existem (users, accounts, categories, transactions)
- [ ] Backup do banco de produção realizado

## 🚀 Processo de Deploy

### Passo 1: Preparação
```bash
# 1. Commit final
git add .
git commit -m "feat: API completa para app Android - pronta para deploy"
git push origin main

# 2. Verificar se tudo foi enviado
git status
git log --oneline -5
```

### Passo 2: Backup de Produção
```bash
# No servidor de produção
mysqldump -u [user] -p[pass] onlifin_production > backup_$(date +%Y%m%d_%H%M%S).sql
tar -czf backup_files_$(date +%Y%m%d_%H%M%S).tar.gz /var/www/html/onlifin/
```

### Passo 3: Deploy Automatizado
```bash
# Usar o script de deploy
sudo ./deploy-api.sh
```

### Passo 4: Deploy Manual (alternativa)
```bash
# 1. Ativar manutenção
php artisan down --message="Atualizando API" --retry=60

# 2. Atualizar código
git pull origin main

# 3. Instalar dependências
composer install --no-dev --optimize-autoloader

# 4. Executar migrações
php artisan migrate --force

# 5. Configurar permissões
chown -R www-data:www-data storage/ bootstrap/cache/
chmod -R 775 storage/ bootstrap/cache/

# 6. Otimizar
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Desativar manutenção
php artisan up
```

## 🧪 Testes Pós-Deploy

### Testes Básicos da API
```bash
# 1. Documentação
curl -X GET "https://onlifin.onlitec.com.br/api/docs"

# 2. Registro de usuário teste
curl -X POST "https://onlifin.onlitec.com.br/api/auth/register" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Teste Deploy",
    "email": "teste-deploy@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "device_name": "Deploy Test"
  }'

# 3. Login
curl -X POST "https://onlifin.onlitec.com.br/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "teste-deploy@example.com",
    "password": "password123",
    "device_name": "Deploy Test"
  }'

# 4. Usar token retornado para testar endpoint protegido
curl -X GET "https://onlifin.onlitec.com.br/api/auth/me" \
  -H "Authorization: Bearer SEU_TOKEN_AQUI"
```

### Verificações de Funcionamento
- [ ] API respondendo corretamente
- [ ] Autenticação funcionando
- [ ] CORS configurado para Android
- [ ] Rate limiting ativo
- [ ] Logs sem erros críticos
- [ ] App web ainda funcionando
- [ ] Performance normal

## 🔧 Configurações Específicas de Produção

### Nginx
```nginx
# Adicionar ao arquivo de configuração do site
location /api {
    try_files $uri $uri/ /index.php?$query_string;
    
    # CORS Headers
    add_header 'Access-Control-Allow-Origin' '*' always;
    add_header 'Access-Control-Allow-Methods' 'GET, POST, PUT, DELETE, OPTIONS' always;
    add_header 'Access-Control-Allow-Headers' 'Authorization, Content-Type, Accept' always;
    
    if ($request_method = 'OPTIONS') {
        add_header 'Access-Control-Allow-Origin' '*';
        add_header 'Access-Control-Allow-Methods' 'GET, POST, PUT, DELETE, OPTIONS';
        add_header 'Access-Control-Allow-Headers' 'Authorization, Content-Type, Accept';
        add_header 'Access-Control-Max-Age' 86400;
        return 204;
    }
}
```

### .env Produção
```env
# Configurações críticas para API
APP_ENV=production
APP_DEBUG=false
APP_URL=https://onlifin.onlitec.com.br

# Sanctum
SANCTUM_STATEFUL_DOMAINS=onlifin.onlitec.com.br,www.onlifin.onlitec.com.br
SESSION_DOMAIN=onlifin.onlitec.com.br

# API
API_RATE_LIMIT=60
CORS_ALLOWED_ORIGINS=*
```

## 🚨 Plano de Rollback

### Se algo der errado:
```bash
# 1. Ativar manutenção
php artisan down

# 2. Restaurar código
git reset --hard COMMIT_ANTERIOR

# 3. Restaurar banco (se necessário)
mysql -u [user] -p[pass] onlifin_production < backup_YYYYMMDD_HHMMSS.sql

# 4. Restaurar dependências
composer install --no-dev --optimize-autoloader

# 5. Limpar caches
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 6. Desativar manutenção
php artisan up
```

## 📞 Contatos de Emergência

- **Desenvolvedor**: [seu-email]
- **Servidor**: [provedor-hosting]
- **Suporte**: [contato-suporte]

## 📋 Checklist Final

- [ ] Backup realizado com sucesso
- [ ] Deploy executado sem erros
- [ ] API funcionando corretamente
- [ ] Testes básicos passando
- [ ] App web funcionando normalmente
- [ ] Logs sem erros críticos
- [ ] Performance normal
- [ ] Documentação acessível
- [ ] Equipe notificada sobre nova API

## 🎯 URLs Importantes Pós-Deploy

- **API Base**: `https://onlifin.onlitec.com.br/api`
- **Documentação**: `https://onlifin.onlitec.com.br/api/docs`
- **OpenAPI**: `https://onlifin.onlitec.com.br/api/docs/openapi`
- **Health Check**: `https://onlifin.onlitec.com.br/up`

## 📱 Para o Desenvolvedor Android

Após o deploy bem-sucedido, informar ao desenvolvedor do app Android:

1. **Nova Base URL**: `https://onlifin.onlitec.com.br/api`
2. **Documentação completa**: Disponível nos arquivos `API_DOCUMENTATION.md` e `ANDROID_INTEGRATION_EXAMPLE.md`
3. **Endpoints disponíveis**: Todos os recursos implementados estão funcionais
4. **Autenticação**: Laravel Sanctum com tokens Bearer
5. **Rate Limiting**: 60 requisições por minuto para usuários autenticados

✅ **API pronta para desenvolvimento do app Android!**
