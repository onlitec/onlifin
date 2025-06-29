#!/bin/bash
set -e

# Ajustar permissões
chown -R www-data:www-data /var/www/html/dev.onlifin

# Entrar no diretório do projeto
cd /var/www/html/dev.onlifin

# Instalar dependências PHP e Node se não existirem
if [ ! -f vendor/autoload.php ]; then
  composer install --no-interaction --prefer-dist --optimize-autoloader
  npm install
  npm run build
fi

# Gerar chave de aplicativo
php artisan key:generate --force

# Executar migrations e seeders
php artisan migrate --force --seed

# Criar usuário administrador padrão
php artisan tinker --execute "use App\\Models\\User; use Illuminate\\Support\\Facades\\Hash; User::firstOrCreate(['email'=>'admin@admin.com'], ['name'=>'Administrator','password'=>Hash::make('AdminMudar'),'is_admin'=>true]);"

# Ajustar permissões finais
chown -R www-data:www-data /var/www/html/dev.onlifin

echo "Pós-instalação concluída: plataforma configurada e usuário admin@admin.com criado." 