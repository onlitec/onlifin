#!/bin/bash
# ===========================================
# Onlifin - Docker Build Script
# ===========================================
set -e

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configurações
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Carregar variáveis de ambiente
if [ -f .env ]; then
    export $(grep -v '^#' .env | xargs)
    echo -e "${GREEN}✓ Variáveis de ambiente carregadas de .env${NC}"
else
    echo -e "${RED}✗ Arquivo .env não encontrado${NC}"
    exit 1
fi

# Nome da imagem (pode ser sobrescrito via variável de ambiente)
IMAGE_NAME="${DOCKER_IMAGE:-onlifin/app}"
VERSION="${1:-latest}"

echo ""
echo -e "${YELLOW}🐳 Onlifin Docker Build${NC}"
echo "========================"
echo "Imagem: $IMAGE_NAME"
echo "Versão: $VERSION"
echo ""

# Verificar se Docker está disponível
if ! command -v docker &> /dev/null; then
    echo -e "${RED}✗ Docker não está instalado ou não está no PATH${NC}"
    exit 1
fi

# Build da imagem
echo -e "${YELLOW}→ Iniciando build...${NC}"
docker build \
    --build-arg VITE_SUPABASE_URL="$VITE_SUPABASE_URL" \
    --build-arg VITE_SUPABASE_ANON_KEY="$VITE_SUPABASE_ANON_KEY" \
    --build-arg VITE_APP_ID="${VITE_APP_ID:-app-7xkeeoe4bsap}" \
    -t "$IMAGE_NAME:$VERSION" \
    -t "$IMAGE_NAME:latest" \
    .

echo ""
echo -e "${GREEN}✅ Build completo!${NC}"
echo ""
echo "Imagens criadas:"
echo "  - $IMAGE_NAME:$VERSION"
echo "  - $IMAGE_NAME:latest"
echo ""
echo "Para executar:"
echo "  docker run -d -p 80:80 --name onlifin $IMAGE_NAME:$VERSION"
echo ""
echo "Para publicar no DockerHub:"
echo "  ./docker-push.sh $VERSION"
