#!/bin/bash

echo "🚀 Teste rápido - Container alternativo na porta 8083"

# Limpar containers de teste
docker stop onlifin-quick 2>/dev/null || true
docker rm onlifin-quick 2>/dev/null || true

# Executar container com override do comando de inicialização
echo "🐳 Iniciando container de teste..."
docker run -d \
  --name onlifin-quick \
  -p 8083:80 \
  -e APP_ENV=local \
  -e APP_DEBUG=true \
  -e DB_CONNECTION=sqlite \
  -e DB_DATABASE=/var/www/html/database/database.sqlite \
  -e FORCE_HTTPS=false \
  -e APP_URL=http://localhost:8083 \
  -e CACHE_DRIVER=file \
  -e SESSION_DRIVER=file \
  -e QUEUE_CONNECTION=sync \
  --entrypoint="" \
  onlitec/onlifin:latest \
  /bin/bash -c "
    # Configurar permissões básicas rapidamente
    chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
    
    # Gerar chave se necessário
    if ! grep -q 'APP_KEY=base64:' /var/www/html/.env; then
      php /var/www/html/artisan key:generate --force 2>/dev/null || true
    fi
    
    # Executar migrações básicas
    php /var/www/html/artisan migrate --force 2>/dev/null || true
    php /var/www/html/artisan db:seed --force 2>/dev/null || true
    
    # Iniciar serviços
    php-fpm -D
    nginx -g 'daemon off;'
  "

echo "⏳ Aguardando 30 segundos..."
sleep 30

# Testar
if docker ps | grep -q onlifin-quick; then
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8083 2>/dev/null || echo "000")
    
    if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "302" ]; then
        echo "🎉 SUCESSO! Container de teste funcionando!"
        echo "🌐 Acesse: http://localhost:8083"
        echo "🔐 Login: admin@onlifin.com / admin123"
    else
        echo "⚠️ Container rodando mas HTTP retorna: $HTTP_CODE"
        echo "📋 Logs:"
        docker logs --tail 10 onlifin-quick
    fi
else
    echo "❌ Container não está rodando"
    docker logs onlifin-quick
fi
