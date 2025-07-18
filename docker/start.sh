#!/bin/bash

# Script de inicialização para o container Onlifin
set -e

echo "🚀 Iniciando Onlifin..."

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

# Configurar permissões apenas nos diretórios necessários
chown -R www:www /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache
chown www:www /var/www/html/.env
chmod 666 /var/www/html/.env

# Configurar permissões dos diretórios temporários do Nginx
chown -R www:www /var/lib/nginx/tmp
chmod -R 755 /var/lib/nginx/tmp

# Garantir que os diretórios de cache do Laravel tenham permissões corretas
chown -R www:www /var/www/html/storage/framework/views
chown -R www:www /var/www/html/storage/framework/cache
chown -R www:www /var/www/html/storage/framework/sessions
chmod -R 775 /var/www/html/storage/framework

# Função para corrigir permissões (pode ser chamada periodicamente)
fix_permissions() {
    echo "🔧 Corrigindo permissões..."
    chown -R www:www /var/www/html/storage
    chmod -R 775 /var/www/html/storage
    chown -R www:www /var/www/html/bootstrap/cache
    chmod -R 775 /var/www/html/bootstrap/cache
    echo "✅ Permissões corrigidas!"
}

# Executar correção de permissões inicial
fix_permissions

# Verificar se o arquivo .env existe, se não, criar a partir do .env.example
if [ ! -f /var/www/html/.env ]; then
    echo "📝 Criando arquivo .env..."
    cp /var/www/html/.env.example /var/www/html/.env
    chown www:www /var/www/html/.env
fi

# Gerar chave da aplicação se não existir
if ! grep -q "APP_KEY=base64:" /var/www/html/.env; then
    echo "🔑 Gerando chave da aplicação..."
    # Usar o comando artisan para gerar a chave
    php /var/www/html/artisan key:generate --force || true
fi

# Aguardar conexão com MariaDB
echo "🗄️ Conectando ao MariaDB..."
sleep 10
echo "✅ Tentando conectar ao MariaDB..."

# Executar migrações
echo "🔄 Executando migrações do banco de dados..."
php /var/www/html/artisan migrate --force || echo "⚠️ Algumas migrações falharam, mas continuando..."

# Executar seeders se necessário
echo "🌱 Executando seeders..."
php /var/www/html/artisan db:seed --force --class=DefaultAdminSeeder || true

# Limpar e otimizar cache
echo "🧹 Limpando e otimizando cache..."
php /var/www/html/artisan config:clear || true
php /var/www/html/artisan route:clear || true
php /var/www/html/artisan view:clear || true
php /var/www/html/artisan config:cache || true
php /var/www/html/artisan route:cache || true
php /var/www/html/artisan view:cache || true

# Criar link simbólico para storage se não existir
if [ ! -L /var/www/html/public/storage ]; then
    echo "🔗 Criando link simbólico para storage..."
    php /var/www/html/artisan storage:link
fi

# Configurar permissões finais apenas nos diretórios necessários
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

echo "✅ Onlifin inicializado com sucesso!"
echo "🌐 Aplicação disponível em http://localhost"

# Iniciar supervisor
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
