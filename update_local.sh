#!/bin/bash

# Atualiza o repositório local
ssh onlifin.onlitec.com.br "cd /var/www/html/onlifin && git fetch origin"
ssh onlifin.onlitec.com.br "cd /var/www/html/onlifin && git reset --hard origin/main"

# Limpa os caches e recompila os assets
ssh onlifin.onlitec.com.br "cd /var/www/html/onlifin && php artisan cache:clear"
ssh onlifin.onlitec.com.br "cd /var/www/html/onlifin && php artisan config:clear"
ssh onlifin.onlitec.com.br "cd /var/www/html/onlifin && php artisan view:clear"
ssh onlifin.onlitec.com.br "cd /var/www/html/onlifin && php artisan route:clear"
ssh onlifin.onlitec.com.br "cd /var/www/html/onlifin && php artisan optimize:clear"

# Reinicia os serviços
ssh onlifin.onlitec.com.br "sudo service nginx restart"
ssh onlifin.onlitec.com.br "sudo service roadrunner restart"

# Mensagem de conclusão
echo "Atualização local concluída com sucesso!"
