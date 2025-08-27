#!/bin/bash

# 🚀 Script SIMPLES para executar Onlifin localmente
echo "🐳 Iniciando Onlifin LOCAL (versão simples)..."

# Parar containers existentes
echo "🧹 Parando containers existentes..."
docker stop onlifin-local onlifin-simple 2>/dev/null || true
docker rm onlifin-local onlifin-simple 2>/dev/null || true

# Usar porta 8082 para evitar conflitos
PORT=8082

echo "🔌 Usando porta: $PORT"
echo "🚀 Iniciando container simples..."

# Executar com configurações mínimas
docker run -d \
  --name onlifin-simple \
  -p $PORT:80 \
  -e APP_ENV=local \
  -e APP_DEBUG=true \
  -e DB_CONNECTION=sqlite \
  -e DB_DATABASE=/var/www/html/database/database.sqlite \
  -e FORCE_HTTPS=false \
  -e APP_URL=http://localhost:$PORT \
  -e CACHE_DRIVER=file \
  -e SESSION_DRIVER=file \
  -e QUEUE_CONNECTION=sync \
  onlitec/onlifin:latest

echo "⏳ Aguardando 60 segundos para inicialização..."
sleep 60

# Verificar status
if docker ps | grep -q onlifin-simple; then
    echo "✅ Container está rodando!"
    
    # Testar conectividade
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:$PORT || echo "000")
    
    if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "302" ]; then
        echo "🎉 SUCESSO! Aplicação está respondendo!"
        echo ""
        echo "🌐 Acesse a aplicação em:"
        echo "   http://localhost:$PORT"
        echo ""
        echo "🔐 Credenciais de login:"
        echo "   Email: admin@onlifin.com"
        echo "   Senha: admin123"
        echo ""
        echo "📋 Comandos úteis:"
        echo "   Ver logs: docker logs -f onlifin-simple"
        echo "   Parar: docker stop onlifin-simple"
        echo "   Remover: docker rm onlifin-simple"
    else
        echo "⚠️ Container rodando mas não responde HTTP (código: $HTTP_CODE)"
        echo "📋 Verificar logs:"
        docker logs --tail 20 onlifin-simple
    fi
else
    echo "❌ Erro ao iniciar container!"
    echo "📋 Logs do erro:"
    docker logs onlifin-simple
fi
