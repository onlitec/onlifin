#!/bin/bash

# Diretório do projeto no servidor de produção
PROD_DIR="/var/www/onlifin"

# Atualiza o repositório
ssh onlifin.onlitec.com.br "cd $PROD_DIR && git pull origin beta"

# Instala as dependências
ssh onlifin.onlitec.com.br "cd $PROD_DIR && composer install --no-dev --optimize-autoloader"

# Executa as migrações do banco de dados
ssh onlifin.onlitec.com.br "cd $PROD_DIR && php artisan migrate --force"

# Limpa os caches
ssh onlifin.onlitec.com.br "cd $PROD_DIR && php artisan cache:clear"
ssh onlifin.onlitec.com.br "cd $PROD_DIR && php artisan config:clear"
ssh onlifin.onlitec.com.br "cd $PROD_DIR && php artisan view:clear"
ssh onlifin.onlitec.com.br "cd $PROD_DIR && php artisan route:clear"
ssh onlifin.onlitec.com.br "cd $PROD_DIR && php artisan optimize:clear"

# Compila os assets
ssh onlifin.onlitec.com.br "cd $PROD_DIR && npm install"
ssh onlifin.onlitec.com.br "cd $PROD_DIR && npm run build"

# Reinicia o servidor
ssh onlifin.onlitec.com.br "sudo service nginx restart"
ssh onlifin.onlitec.com.br "sudo service roadrunner restart"

# Verifica se tudo está funcionando
ssh onlifin.onlitec.com.br "cd $PROD_DIR && php artisan route:list | head -n 5"

# Mensagem de conclusão
echo "Atualização concluída com sucesso!"
