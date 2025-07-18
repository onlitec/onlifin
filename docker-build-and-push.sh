#!/bin/bash

# Script completo para build e push da imagem Onlifin
# Repositório: https://hub.docker.com/repository/docker/onlitec/onlifin/general

set -e  # Parar em caso de erro

echo "🏗️  Build e Push da Imagem Onlifin para Docker Hub"
echo "=================================================="
echo ""

# Verificar se está no diretório correto
if [ ! -f "docker-compose.yml" ]; then
    echo "❌ Erro: Execute este script no diretório raiz do projeto Onlifin"
    exit 1
fi

# Obter informações da versão
COMMIT_HASH=$(git rev-parse --short HEAD)
BRANCH=$(git branch --show-current)
DATE=$(date +%Y%m%d-%H%M%S)

echo "📊 Informações da build:"
echo "   Commit: $COMMIT_HASH"
echo "   Branch: $BRANCH"
echo "   Data: $DATE"
echo ""

# Verificar se há mudanças não commitadas
if ! git diff-index --quiet HEAD --; then
    echo "⚠️  Aviso: Há mudanças não commitadas no repositório"
    echo "   Recomenda-se fazer commit antes do build"
    read -p "   Continuar mesmo assim? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

echo "🏗️  Iniciando build da imagem..."
docker-compose build --no-cache

echo ""
echo "🏷️  Criando tags para Docker Hub..."

# Tag latest
docker tag onlifin_onlifin:latest onlitec/onlifin:latest
echo "   ✅ onlitec/onlifin:latest"

# Tag branch
docker tag onlifin_onlifin:latest onlitec/onlifin:$BRANCH
echo "   ✅ onlitec/onlifin:$BRANCH"

# Tag commit
docker tag onlifin_onlifin:latest onlitec/onlifin:$COMMIT_HASH
echo "   ✅ onlitec/onlifin:$COMMIT_HASH"

# Tag com data
docker tag onlifin_onlifin:latest onlitec/onlifin:$DATE
echo "   ✅ onlitec/onlifin:$DATE"

echo ""
echo "🔐 Verificando login no Docker Hub..."
if ! docker info | grep -q "Username"; then
    echo "❌ Você precisa fazer login no Docker Hub primeiro:"
    echo "   docker login -u onlitec"
    echo ""
    echo "🌐 Ou use login via web:"
    echo "   docker login"
    exit 1
fi

echo "✅ Login verificado!"
echo ""

echo "🚀 Iniciando push das imagens para Docker Hub..."

# Push de todas as tags
docker push onlitec/onlifin:latest
docker push onlitec/onlifin:$BRANCH
docker push onlitec/onlifin:$COMMIT_HASH
docker push onlitec/onlifin:$DATE

echo ""
echo "🎉 Build e Push concluídos com sucesso!"
echo ""
echo "🌐 Imagens disponíveis em:"
echo "   https://hub.docker.com/repository/docker/onlitec/onlifin/general"
echo ""
echo "📋 Tags enviadas:"
echo "   - onlitec/onlifin:latest"
echo "   - onlitec/onlifin:$BRANCH"
echo "   - onlitec/onlifin:$COMMIT_HASH"
echo "   - onlitec/onlifin:$DATE"
echo ""
echo "🐳 Para usar a imagem:"
echo "   docker pull onlitec/onlifin:latest"
echo "   docker run -p 8080:80 onlitec/onlifin:latest"
