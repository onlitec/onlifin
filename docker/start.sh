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

# Corrigir permissões de forma RADICAL
echo "🔧 Corrigindo permissões..."

# SOLUÇÃO RADICAL: Dar permissões máximas para TUDO
chmod -R 777 /var/www/html/storage
chmod -R 777 /var/www/html/bootstrap
chmod -R 755 /var/www/html/public
chmod 666 /var/www/html/.env 2>/dev/null || true
chmod +x /var/www/html/artisan

# Garantir que TODOS os diretórios críticos existam
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache

# Aplicar permissões máximas novamente
chmod -R 777 /var/www/html/storage
chmod -R 777 /var/www/html/bootstrap

# Permissões para logs do sistema
chmod -R 755 /var/log/nginx 2>/dev/null || true
chmod -R 755 /var/log/php-fpm 2>/dev/null || true
chmod -R 755 /var/lib/nginx/tmp 2>/dev/null || true

echo "✅ Permissões MÁXIMAS aplicadas!"

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

# Gerar chave da aplicação se não existir ou for inválida
if ! grep -q "APP_KEY=base64:" /var/www/html/.env || grep -q "GERE_UMA_CHAVE_AQUI" /var/www/html/.env; then
    echo "🔑 Gerando chave da aplicação..."
    # Gerar uma nova chave válida
    NEW_KEY=$(php /var/www/html/artisan key:generate --show)
    if [ ! -z "$NEW_KEY" ]; then
        # Substituir a chave no arquivo .env
        sed -i "s|APP_KEY=.*|APP_KEY=$NEW_KEY|g" /var/www/html/.env
        echo "✅ Chave gerada: $NEW_KEY"
    else
        echo "❌ Falha ao gerar chave - usando comando direto"
        php /var/www/html/artisan key:generate --force || true
    fi
fi

# Limpar caches antes de conectar ao banco
echo "🧹 Limpando caches iniciais..."
php /var/www/html/artisan config:clear || true
php /var/www/html/artisan route:clear || true
php /var/www/html/artisan view:clear || true
php /var/www/html/artisan cache:clear || true

# Verificar se a chave está funcionando
echo "🔍 Verificando configuração de criptografia..."
if php /var/www/html/artisan tinker --execute="echo 'Cipher: ' . config('app.cipher') . PHP_EOL; echo 'Key length: ' . strlen(config('app.key')) . PHP_EOL;" 2>/dev/null; then
    echo "✅ Configuração de criptografia OK"
else
    echo "❌ Problema na configuração de criptografia"
fi

# Verificar se Redis está disponível e configurar drivers adequados
echo "🔍 Verificando disponibilidade do Redis..."
if php -r "try { new Redis(); echo 'Redis disponível'; } catch (Error \$e) { echo 'Redis não disponível'; }" 2>/dev/null | grep -q "Redis disponível"; then
    echo "✅ Redis disponível - mantendo configurações"
else
    echo "⚠️ Redis não disponível - configurando drivers alternativos"
    # Alterar para drivers que não dependem do Redis
    sed -i 's/CACHE_DRIVER=redis/CACHE_DRIVER=file/g' /var/www/html/.env
    sed -i 's/SESSION_DRIVER=redis/SESSION_DRIVER=file/g' /var/www/html/.env
    sed -i 's/QUEUE_CONNECTION=redis/QUEUE_CONNECTION=sync/g' /var/www/html/.env
    echo "✅ Drivers alternativos configurados"
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
php /var/www/html/artisan db:seed --force --class=AdminUserSeeder || true

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

# Configurar permissões finais MÁXIMAS
echo "🔧 Aplicando permissões finais MÁXIMAS..."

# Aplicar permissões 777 em TUDO que o Laravel precisa
chmod -R 777 /var/www/html/storage
chmod -R 777 /var/www/html/bootstrap

# Garantir que TODOS os diretórios existam
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/app/public

# Aplicar permissões 777 novamente para garantir
chmod -R 777 /var/www/html/storage
chmod -R 777 /var/www/html/bootstrap

# Criar um arquivo de teste para verificar permissões
echo "teste" > /var/www/html/storage/framework/views/test-write.txt 2>/dev/null && rm -f /var/www/html/storage/framework/views/test-write.txt
if [ $? -eq 0 ]; then
    echo "✅ Teste de escrita: SUCESSO"
else
    echo "❌ Teste de escrita: FALHOU - aplicando correção extrema"
    chmod -R 777 /var/www/html/
fi

# Verificar se as permissões estão corretas
echo "🔍 Verificando permissões..."
if [ -w "/var/www/html/storage/framework/views" ]; then
    echo "✅ Diretório views é gravável"
else
    echo "❌ Diretório views NÃO é gravável - aplicando correção"
    chmod -R 777 /var/www/html/storage
fi

echo "✅ Onlifin inicializado com sucesso!"
echo "🌐 Aplicação disponível em http://localhost"

# Iniciar supervisor
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
