#!/bin/bash

# 🚀 Script para executar Onlifin localmente
echo "🐳 Iniciando Onlifin localmente..."

# Parar e remover container existente se houver
echo "🧹 Limpando containers antigos..."
docker stop onlifin-local 2>/dev/null || true
docker rm onlifin-local 2>/dev/null || true

# Fazer pull da imagem mais recente
echo "📥 Baixando imagem mais recente..."
docker pull onlitec/onlifin:latest

# Encontrar porta disponível
PORT=8081
while netstat -tuln | grep -q ":$PORT "; do
    PORT=$((PORT + 1))
done

echo "🔌 Usando porta: $PORT"

# Executar container local
echo "🚀 Iniciando container..."
docker run -d \
  --name onlifin-local \
  -p $PORT:80 \
  -e APP_ENV=local \
  -e APP_DEBUG=true \
  -e DB_CONNECTION=sqlite \
  -e DB_DATABASE=/var/www/html/database/database.sqlite \
  -e FORCE_HTTPS=false \
  -e APP_URL=http://localhost:$PORT \
  onlitec/onlifin:latest

# Aguardar inicialização
echo "⏳ Aguardando inicialização..."
sleep 30

# Verificar status
echo "🔍 Verificando status..."
if docker ps | grep -q onlifin-local; then
    echo "✅ Container está rodando!"
    echo ""
    echo "🌐 Acesse a aplicação em:"
    echo "   http://localhost:$PORT"
    echo ""
    echo "🔐 Credenciais de login:"
    echo "   Email: admin@onlifin.com"
    echo "   Senha: admin123"
    echo ""
    echo "📋 Comandos úteis:"
    echo "   Ver logs: docker logs -f onlifin-local"
    echo "   Parar: docker stop onlifin-local"
    echo "   Remover: docker rm onlifin-local"
else
    echo "❌ Erro ao iniciar container!"
    echo "📋 Verificar logs:"
    docker logs onlifin-local
fi
