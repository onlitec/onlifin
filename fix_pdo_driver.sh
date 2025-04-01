#!/bin/bash
echo 'Corrigindo problema do driver PDO...'

# Instalar extensões necessárias
sudo apt-get update
sudo apt-get install -y php-pdo php-mysql

# Habilitar extensões nos arquivos php.ini
for phpini in /etc/php/*/fpm/php.ini /etc/php/*/cli/php.ini /etc/php/*/apache2/php.ini; do
  if [ -f "$phpini" ]; then
    echo "Configurando $phpini"
    sudo sed -i 's/;extension=pdo_mysql/extension=pdo_mysql/g' "$phpini"
    sudo sed -i 's/;extension=pdo/extension=pdo/g' "$phpini"
  fi
done

# Reiniciar serviços
for phpver in 7.4 8.0 8.1 8.2 8.3; do
  if systemctl list-units --full -all | grep -Fq "php$phpver-fpm"; then
    echo "Reiniciando php$phpver-fpm"
    sudo systemctl restart php$phpver-fpm
  fi
done

if systemctl list-units --full -all | grep -Fq "apache2"; then
  echo "Reiniciando Apache"
  sudo systemctl restart apache2
fi

if systemctl list-units --full -all | grep -Fq "nginx"; then
  echo "Reiniciando Nginx"
  sudo systemctl restart nginx
fi

# Limpar cache Laravel
php artisan config:clear
php artisan cache:clear

echo 'Correção concluída. Teste sua aplicação novamente.'
