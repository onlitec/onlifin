#!/bin/bash

echo "🔄 Atualizando container existente onlifin-app..."

# Parar container atual
echo "⏸️ Parando container atual..."
docker stop onlifin-app

# Fazer backup do container (opcional)
echo "💾 Fazendo backup..."
docker commit onlifin-app onlifin-app-backup

# Remover container antigo
echo "🗑️ Removendo container antigo..."
docker rm onlifin-app

# Baixar imagem mais recente
echo "📥 Baixando imagem mais recente..."
docker pull onlitec/onlifin:latest

# Recriar container com mesmas configurações
echo "🚀 Recriando container..."
docker run -d \
  --name onlifin-app \
  -p 127.0.0.1:8080:80 \
  -p 172.20.120.180:8080:80 \
  -e APP_ENV=production \
  -e APP_DEBUG=false \
  -e DB_CONNECTION=sqlite \
  -e DB_DATABASE=/var/www/html/database/database.sqlite \
  -e FORCE_HTTPS=false \
  onlitec/onlifin:latest

echo "⏳ Aguardando inicialização..."
sleep 30

if docker ps | grep -q onlifin-app; then
    echo "✅ Container atualizado com sucesso!"
    echo "🌐 Acesse em: http://localhost:8080"
else
    echo "❌ Erro na atualização!"
    echo "🔄 Restaurando backup..."
    docker run -d --name onlifin-app -p 127.0.0.1:8080:80 -p 172.20.120.180:8080:80 onlifin-app-backup
fi
