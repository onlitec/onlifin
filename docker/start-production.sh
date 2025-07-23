#!/bin/bash

# 🚀 Script de Inicialização - Onlifin Production with API
set -e

echo "🐳 Iniciando Onlifin com API..."

# Criar diretórios necessários
mkdir -p /var/log/nginx
mkdir -p /var/log/php-fpm
mkdir -p /var/log/php
mkdir -p /var/log/supervisor
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/bootstrap/cache

# Criar diretórios temporários do Nginx
mkdir -p /var/lib/nginx/tmp/fastcgi
mkdir -p /var/lib/nginx/tmp/proxy
mkdir -p /var/lib/nginx/tmp/scgi
mkdir -p /var/lib/nginx/tmp/uwsgi

# Aguardar banco de dados se necessário
if [ "$DB_CONNECTION" = "mysql" ]; then
    echo "⏳ Aguardando MySQL..."
    while ! mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" --silent; do
        sleep 1
    done
    echo "✅ MySQL conectado!"
fi

if [ "$DB_CONNECTION" = "pgsql" ]; then
    echo "⏳ Aguardando PostgreSQL..."
    while ! pg_isready -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME"; do
        sleep 1
    done
    echo "✅ PostgreSQL conectado!"
fi

# Verificar se o arquivo .env existe
if [ ! -f /var/www/html/.env ]; then
    echo "📝 Criando arquivo .env..."
    cp /var/www/html/.env.example /var/www/html/.env
fi

# Gerar chave da aplicação se não existir
if [ -z "$APP_KEY" ]; then
    echo "🔑 Gerando chave da aplicação..."
    php artisan key:generate --force
fi

# Executar migrações
echo "🗄️ Executando migrações..."
php artisan migrate --force

# Publicar assets do Sanctum se necessário
if [ ! -f "config/sanctum.php" ]; then
    echo "🔐 Publicando configurações do Sanctum..."
    php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider" --force
fi

# Limpar e otimizar caches
echo "🧹 Otimizando caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Recriar caches para produção
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Configurar permissões
echo "🔒 Configurando permissões..."
chown -R www:www /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache
chown www:www /var/www/html/.env
chmod 666 /var/www/html/.env

# Configurar permissões dos diretórios temporários do Nginx
chown -R www:www /var/lib/nginx/tmp
chmod -R 755 /var/lib/nginx/tmp

# Garantir permissões corretas dos diretórios de cache do Laravel
chown -R www:www /var/www/html/storage/framework/views
chown -R www:www /var/www/html/storage/framework/cache
chown -R www:www /var/www/html/storage/framework/sessions
chmod -R 775 /var/www/html/storage/framework

# Verificar se a API está configurada
echo "🔍 Verificando configuração da API..."
if php artisan route:list --path=api | grep -q "api/auth/login"; then
    echo "✅ API configurada corretamente!"
else
    echo "⚠️ API pode não estar configurada corretamente"
fi

# Verificar saúde da aplicação
echo "🏥 Verificando saúde da aplicação..."
php artisan tinker --execute="
    try {
        \DB::connection()->getPdo();
        echo 'Conexão com banco: OK';
    } catch (Exception \$e) {
        echo 'Erro na conexão com banco: ' . \$e->getMessage();
        exit(1);
    }
"

# Criar usuário admin padrão se não existir
if [ "$APP_ENV" = "local" ] || [ "$CREATE_ADMIN_USER" = "true" ]; then
    echo "👤 Criando usuário administrador..."
    php artisan onlifin:create-admin --email=admin@onlifin.com --password=admin123 --name=Administrador
fi

# Mostrar informações importantes
echo ""
echo "🎉 Onlifin iniciado com sucesso!"
echo "📱 API Base URL: http://localhost/api"
echo "📚 Documentação: http://localhost/api/docs"
echo "🌐 Aplicação Web: http://localhost"
echo ""

# Iniciar supervisor (que gerencia nginx e php-fpm)
echo "🚀 Iniciando serviços..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
