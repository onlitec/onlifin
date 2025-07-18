#!/bin/bash

# Script para corrigir permissões do Laravel no container Docker
# Execute quando houver erros de permissão

echo "🔧 Corrigindo Permissões do Laravel - Onlifin"
echo "============================================="
echo ""

# Verificar se o container está rodando
if ! docker ps | grep -q "onlifin-app"; then
    echo "❌ Container onlifin-app não está rodando!"
    echo "   Execute: docker-compose up -d"
    exit 1
fi

echo "📁 Corrigindo permissões dos diretórios de storage..."
docker exec onlifin-app chown -R www:www /var/www/html/storage
docker exec onlifin-app chmod -R 775 /var/www/html/storage

echo "📁 Corrigindo permissões do cache do Bootstrap..."
docker exec onlifin-app chown -R www:www /var/www/html/bootstrap/cache
docker exec onlifin-app chmod -R 775 /var/www/html/bootstrap/cache

echo "📄 Corrigindo permissões do arquivo .env..."
docker exec onlifin-app chown www:www /var/www/html/.env
docker exec onlifin-app chmod 666 /var/www/html/.env

echo "🗂️  Corrigindo permissões específicas dos diretórios de cache..."
docker exec onlifin-app chown -R www:www /var/www/html/storage/framework/views
docker exec onlifin-app chown -R www:www /var/www/html/storage/framework/cache
docker exec onlifin-app chown -R www:www /var/www/html/storage/framework/sessions
docker exec onlifin-app chmod -R 775 /var/www/html/storage/framework

echo "🧹 Limpando cache do Laravel..."
docker exec onlifin-app php artisan view:clear
docker exec onlifin-app php artisan config:clear
docker exec onlifin-app php artisan route:clear

echo "🔄 Recriando diretórios de cache se necessário..."
docker exec onlifin-app mkdir -p /var/www/html/storage/framework/views
docker exec onlifin-app mkdir -p /var/www/html/storage/framework/cache
docker exec onlifin-app mkdir -p /var/www/html/storage/framework/sessions
docker exec onlifin-app mkdir -p /var/www/html/storage/logs

echo "🔧 Aplicando permissões finais..."
docker exec onlifin-app chown -R www:www /var/www/html/storage
docker exec onlifin-app chmod -R 775 /var/www/html/storage

echo ""
echo "✅ Permissões corrigidas com sucesso!"
echo ""
echo "🧪 Testando aplicação..."
if curl -s -o /dev/null -w "%{http_code}" http://172.20.120.180:8080 | grep -q "200\|302"; then
    echo "✅ Aplicação respondendo corretamente!"
else
    echo "⚠️  Aplicação pode ainda ter problemas. Verifique os logs:"
    echo "   docker logs onlifin-app"
fi

echo ""
echo "📊 Status dos diretórios críticos:"
echo "Storage framework views:"
docker exec onlifin-app ls -la /var/www/html/storage/framework/views | head -3

echo ""
echo "Bootstrap cache:"
docker exec onlifin-app ls -la /var/www/html/bootstrap/cache | head -3

echo ""
echo "🎯 Se o problema persistir:"
echo "   1. Reinicie o container: docker-compose restart"
echo "   2. Rebuild o container: docker-compose up -d --build"
echo "   3. Execute este script novamente: ./fix-permissions.sh"
