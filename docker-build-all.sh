#!/bin/bash
# ===========================================
# Onlifin - Build e Push de Todas as Imagens
# ===========================================
# Este script faz build e push de todas as
# imagens Docker para o DockerHub
# ===========================================

set -e

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configurações
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Versão (pode ser passada como argumento)
VERSION="${1:-latest}"
DOCKER_ORG="${DOCKER_ORG:-onlitec}"

echo ""
echo -e "${BLUE}🐳 Onlifin - Build & Push All Images${NC}"
echo "========================================"
echo "Organização: $DOCKER_ORG"
echo "Versão: $VERSION"
echo ""

# Verificar se Docker está disponível
if ! command -v docker &> /dev/null; then
    echo -e "${RED}✗ Docker não está instalado ou não está no PATH${NC}"
    exit 1
fi

# Verificar login no DockerHub
if ! docker info 2>/dev/null | grep -q "Username"; then
    echo -e "${YELLOW}⚠ Você precisa fazer login no DockerHub primeiro${NC}"
    echo "Execute: docker login"
    exit 1
fi

echo -e "${GREEN}✓ Docker configurado e logado${NC}"
echo ""

# ===========================================
# Build da imagem do App (Frontend)
# ===========================================
echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${YELLOW}📦 1/2 - Build da imagem: onlifin${NC}"
echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# Carregar variáveis de ambiente
if [ -f .env ]; then
    export $(grep -v '^#' .env | xargs)
    echo -e "${GREEN}✓ Variáveis de ambiente carregadas${NC}"
fi

docker build \
    --build-arg VITE_SUPABASE_URL="${VITE_SUPABASE_URL:-http://localhost:3000}" \
    --build-arg VITE_SUPABASE_ANON_KEY="${VITE_SUPABASE_ANON_KEY:-eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9}" \
    --build-arg VITE_APP_ID="${VITE_APP_ID:-app-7xkeeoe4bsap}" \
    -t "$DOCKER_ORG/onlifin:$VERSION" \
    -t "$DOCKER_ORG/onlifin:latest" \
    -f Dockerfile \
    .

echo -e "${GREEN}✓ Build onlifin concluído${NC}"
echo ""

# ===========================================
# Build da imagem do PostgreSQL
# ===========================================
echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${YELLOW}📦 2/2 - Build da imagem: onlifin-db${NC}"
echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

docker build \
    -t "$DOCKER_ORG/onlifin-db:$VERSION" \
    -t "$DOCKER_ORG/onlifin-db:latest" \
    -f docker/Dockerfile.postgres \
    .

echo -e "${GREEN}✓ Build onlifin-db concluído${NC}"
echo ""

# ===========================================
# Push das imagens
# ===========================================
echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${YELLOW}🚀 Enviando imagens para DockerHub${NC}"
echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# Push onlifin
echo -e "${YELLOW}→ Push onlifin:$VERSION${NC}"
docker push "$DOCKER_ORG/onlifin:$VERSION"
docker push "$DOCKER_ORG/onlifin:latest"
echo -e "${GREEN}✓ onlifin enviado${NC}"
echo ""

# Push onlifin-db
echo -e "${YELLOW}→ Push onlifin-db:$VERSION${NC}"
docker push "$DOCKER_ORG/onlifin-db:$VERSION"
docker push "$DOCKER_ORG/onlifin-db:latest"
echo -e "${GREEN}✓ onlifin-db enviado${NC}"
echo ""

# ===========================================
# Resumo
# ===========================================
echo ""
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}✅ Todas as imagens foram publicadas!${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo "Imagens disponíveis:"
echo ""
echo "  📦 $DOCKER_ORG/onlifin:$VERSION"
echo "     https://hub.docker.com/r/$DOCKER_ORG/onlifin"
echo ""
echo "  🗄️ $DOCKER_ORG/onlifin-db:$VERSION"
echo "     https://hub.docker.com/r/$DOCKER_ORG/onlifin-db"
echo ""
echo "Para usar no Coolify:"
echo "  Use o arquivo docker-compose.coolify.yml"
echo ""
