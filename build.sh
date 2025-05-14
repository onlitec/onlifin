#!/bin/bash

# Compilar assets
npm run build

# Garantir que o diretório existe
mkdir -p public/build

# Copiar manifest
cp public/build/.vite/manifest.json public/build/manifest.json

# Ajustar permissões
sudo chown -R www-data:www-data public/build
sudo chmod -R 775 public/build

# Limpar cache
php artisan view:clear
php artisan cache:clear