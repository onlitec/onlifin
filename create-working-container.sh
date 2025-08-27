#!/bin/bash

echo "🚀 Criando container Onlifin FUNCIONAL"

# Limpar containers antigos
docker rm -f onlifin-app onlifin-working 2>/dev/null || true

echo "🐳 Criando container funcional..."

# Criar container com configuração funcional
docker run -d \
  --name onlifin-working \
  -p 127.0.0.1:8080:80 \
  -p 172.20.120.180:8080:80 \
  -e APP_ENV=production \
  -e APP_DEBUG=false \
  -e DB_CONNECTION=sqlite \
  -e DB_DATABASE=/var/www/html/database/database.sqlite \
  -e FORCE_HTTPS=false \
  -e APP_URL=http://172.20.120.180:8080 \
  -e CACHE_DRIVER=file \
  -e SESSION_DRIVER=file \
  -e QUEUE_CONNECTION=sync \
  -e REDIS_HOST=127.0.0.1 \
  -e REDIS_PASSWORD=null \
  --restart unless-stopped \
  onlitec/onlifin:latest

echo "⏳ Aguardando inicialização (2 minutos)..."
sleep 120

# Verificar status
if docker ps | grep -q onlifin-working; then
    echo "✅ Container está rodando!"
    
    # Testar ambas as URLs
    echo "🔍 Testando conectividade..."
    
    LOCAL_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8080 2>/dev/null || echo "000")
    NETWORK_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://172.20.120.180:8080 2>/dev/null || echo "000")
    
    echo "📊 Resultados dos testes:"
    echo "   localhost:8080 = $LOCAL_CODE"
    echo "   172.20.120.180:8080 = $NETWORK_CODE"
    
    if [ "$LOCAL_CODE" != "000" ] || [ "$NETWORK_CODE" != "000" ]; then
        echo "🎉 SUCESSO! Container funcionando!"
        echo ""
        echo "🌐 URLs de acesso:"
        echo "   http://localhost:8080"
        echo "   http://172.20.120.180:8080"
        echo ""
        echo "🔐 Credenciais:"
        echo "   Email: admin@onlifin.com"
        echo "   Senha: admin123"
        echo ""
        echo "📋 Gerenciamento:"
        echo "   Ver logs: docker logs -f onlifin-working"
        echo "   Reiniciar: docker restart onlifin-working"
        echo "   Parar: docker stop onlifin-working"
    else
        echo "⚠️ Container rodando mas não responde HTTP"
        echo "📋 Últimos logs:"
        docker logs --tail 20 onlifin-working
    fi
else
    echo "❌ Erro ao criar container!"
    echo "📋 Logs do erro:"
    docker logs onlifin-working
fi
