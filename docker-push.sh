#!/bin/bash
# ===========================================
# Onlifin - Docker Push Script
# ===========================================
set -e

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configurações
IMAGE_NAME="${DOCKER_IMAGE:-onlifin/app}"
VERSION="${1:-latest}"

echo ""
echo -e "${YELLOW}🚀 Onlifin Docker Push${NC}"
echo "========================"
echo "Imagem: $IMAGE_NAME"
echo "Versão: $VERSION"
echo ""

# Verificar se Docker está disponível
if ! command -v docker &> /dev/null; then
    echo -e "${RED}✗ Docker não está instalado ou não está no PATH${NC}"
    exit 1
fi

# Verificar se está logado no DockerHub
if ! docker info 2>/dev/null | grep -q "Username"; then
    echo -e "${YELLOW}⚠ Você precisa fazer login no DockerHub primeiro${NC}"
    echo "Execute: docker login"
    echo ""
    read -p "Deseja fazer login agora? (s/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Ss]$ ]]; then
        docker login
    else
        exit 1
    fi
fi

# Push da imagem
echo -e "${YELLOW}→ Enviando imagem para DockerHub...${NC}"

if [ "$VERSION" != "latest" ]; then
    docker push "$IMAGE_NAME:$VERSION"
fi

docker push "$IMAGE_NAME:latest"

echo ""
echo -e "${GREEN}✅ Imagem publicada com sucesso!${NC}"
echo ""
echo "Imagem disponível em:"
echo "  https://hub.docker.com/r/$IMAGE_NAME"
echo ""
echo "Para usar a imagem:"
echo "  docker pull $IMAGE_NAME:$VERSION"
echo "  docker run -d -p 80:80 --name onlifin $IMAGE_NAME:$VERSION"
