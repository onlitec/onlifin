#!/bin/bash

# Script para corrigir permissões de cache do Laravel
# Uso: ./fix_cache_permissions.sh

echo "🔧 Corrigindo permissões de cache do Laravel..."

# Definir proprietário correto para storage
sudo chown -R www-data:www-data storage/

# Definir permissões corretas
sudo chmod -R 775 storage/

# Criar diretório de uploads temporários se não existir
mkdir -p storage/app/temp_uploads
sudo chown -R www-data:www-data storage/app/temp_uploads
sudo chmod -R 775 storage/app/temp_uploads

# Limpar caches
echo "🧹 Limpando caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Recriar caches
echo "🔄 Recriando caches..."
php artisan config:cache
php artisan route:cache

echo "✅ Correções de cache concluídas!"
echo "📝 Logs disponíveis em: storage/logs/laravel.log" 