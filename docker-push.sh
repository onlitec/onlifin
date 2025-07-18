#!/bin/bash

# Script para enviar imagem Docker para o Docker Hub
# Repositório: https://hub.docker.com/repository/docker/onlitec/onlifin/general

echo "🐳 Enviando imagem Onlifin para Docker Hub..."
echo "📦 Repositório: onlitec/onlifin"
echo ""

# Verificar se está logado no Docker Hub
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

# Obter informações da versão
COMMIT_HASH=$(git rev-parse --short HEAD)
BRANCH=$(git branch --show-current)
DATE=$(date +%Y%m%d)

echo "📊 Informações da build:"
echo "   Commit: $COMMIT_HASH"
echo "   Branch: $BRANCH"
echo "   Data: $DATE"
echo ""

# Verificar se as imagens existem
echo "🔍 Verificando imagens taggeadas..."
docker images | grep onlitec/onlifin

echo ""
echo "🚀 Iniciando push das imagens..."

# Push da imagem latest
echo "📤 Enviando onlitec/onlifin:latest..."
docker push onlitec/onlifin:latest

# Push da imagem beta
echo "📤 Enviando onlitec/onlifin:beta..."
docker push onlitec/onlifin:beta

# Push da imagem com hash do commit
echo "📤 Enviando onlitec/onlifin:$COMMIT_HASH..."
docker push onlitec/onlifin:$COMMIT_HASH

echo ""
echo "✅ Push concluído!"
echo "🌐 Imagens disponíveis em: https://hub.docker.com/repository/docker/onlitec/onlifin/general"
echo ""
echo "📋 Tags enviadas:"
echo "   - onlitec/onlifin:latest"
echo "   - onlitec/onlifin:beta"
echo "   - onlitec/onlifin:$COMMIT_HASH"
