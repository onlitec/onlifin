#!/bin/bash

# Atualiza o repositório
git fetch origin
git reset --hard origin/beta

# Instala dependências
composer install --no-dev --optimize-autoloader
npm install
npm run build

# Limpa caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
php artisan optimize:clear

# Reinicia serviços
sudo service nginx restart
sudo service roadrunner restart
