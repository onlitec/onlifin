#!/bin/bash
# ===========================================
# Onlifin - Script de Release Completo
# ===========================================
# Este script automatiza o processo de:
# 1. Commit e push para GitHub
# 2. Build das imagens Docker
# 3. Push para DockerHub
# 4. Tag de release no GitHub
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

DOCKER_ORG="${DOCKER_ORG:-onlitec}"

# Verificar argumentos
if [ -z "$1" ]; then
    echo ""
    echo -e "${YELLOW}Uso: $0 <versão> [mensagem-commit]${NC}"
    echo ""
    echo "Exemplos:"
    echo "  $0 4.0.1.0"
    echo "  $0 4.0.1.0 \"feat: nova funcionalidade\""
    echo ""
    echo "Última versão/tag:"
    git describe --tags --abbrev=0 2>/dev/null || echo "  Nenhuma tag encontrada"
    echo ""
    exit 1
fi

VERSION="$1"
COMMIT_MSG="${2:-Release $VERSION}"

echo ""
echo -e "${BLUE}🚀 Onlifin - Release $VERSION${NC}"
echo "========================================"
echo ""

# ===========================================
# Verificações iniciais
# ===========================================
echo -e "${YELLOW}→ Verificando pré-requisitos...${NC}"

# Git
if ! command -v git &> /dev/null; then
    echo -e "${RED}✗ Git não está instalado${NC}"
    exit 1
fi

# Docker
if ! command -v docker &> /dev/null; then
    echo -e "${RED}✗ Docker não está instalado${NC}"
    exit 1
fi

# Login DockerHub
if ! docker info 2>/dev/null | grep -q "Username"; then
    echo -e "${RED}✗ Você precisa fazer login no DockerHub primeiro${NC}"
    echo "Execute: docker login"
    exit 1
fi

# Verificar se há mudanças
if [ -z "$(git status --porcelain)" ]; then
    echo -e "${YELLOW}⚠ Nenhuma mudança para commit${NC}"
    read -p "Deseja continuar mesmo assim? (s/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Ss]$ ]]; then
        exit 0
    fi
fi

echo -e "${GREEN}✓ Pré-requisitos OK${NC}"
echo ""

# ===========================================
# 1. Git: Add, Commit e Push
# ===========================================
echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${YELLOW}📝 1/4 - Git Commit e Push${NC}"
echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# Mostrar mudanças
echo "Arquivos modificados:"
git status --short
echo ""

# Add e commit
git add .
git commit -m "$COMMIT_MSG" || echo "Nenhuma mudança para commit"

# Push
echo -e "${YELLOW}→ Push para GitHub...${NC}"
git push origin master

echo -e "${GREEN}✓ Push concluído${NC}"
echo ""

# ===========================================
# 2. Build das imagens Docker
# ===========================================
echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${YELLOW}🐳 2/4 - Build das Imagens Docker${NC}"
echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# Carregar variáveis de ambiente
if [ -f .env ]; then
    export $(grep -v '^#' .env | xargs)
fi

# Build app
echo -e "${YELLOW}→ Build onlifin:$VERSION${NC}"
docker build \
    --build-arg VITE_SUPABASE_URL="${VITE_SUPABASE_URL:-http://localhost:3000}" \
    --build-arg VITE_SUPABASE_ANON_KEY="${VITE_SUPABASE_ANON_KEY:-}" \
    --build-arg VITE_APP_ID="${VITE_APP_ID:-app-7xkeeoe4bsap}" \
    -t "$DOCKER_ORG/onlifin:$VERSION" \
    -t "$DOCKER_ORG/onlifin:latest" \
    -f Dockerfile \
    .

echo -e "${GREEN}✓ Build onlifin concluído${NC}"

# Build db
echo -e "${YELLOW}→ Build onlifin-db:$VERSION${NC}"
docker build \
    -t "$DOCKER_ORG/onlifin-db:$VERSION" \
    -t "$DOCKER_ORG/onlifin-db:latest" \
    -f docker/Dockerfile.postgres \
    .

echo -e "${GREEN}✓ Build onlifin-db concluído${NC}"
echo ""

# ===========================================
# 3. Push para DockerHub
# ===========================================
echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${YELLOW}📤 3/4 - Push para DockerHub${NC}"
echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# Push onlifin
echo -e "${YELLOW}→ Push onlifin:$VERSION${NC}"
docker push "$DOCKER_ORG/onlifin:$VERSION"
docker push "$DOCKER_ORG/onlifin:latest"

# Push onlifin-db
echo -e "${YELLOW}→ Push onlifin-db:$VERSION${NC}"
docker push "$DOCKER_ORG/onlifin-db:$VERSION"
docker push "$DOCKER_ORG/onlifin-db:latest"

echo -e "${GREEN}✓ Push DockerHub concluído${NC}"
echo ""

# ===========================================
# 4. Tag de Release no GitHub
# ===========================================
echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${YELLOW}🏷️ 4/4 - Tag de Release${NC}"
echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# Criar tag
git tag -a "v$VERSION" -m "Release $VERSION"
git push origin "v$VERSION"

echo -e "${GREEN}✓ Tag v$VERSION criada${NC}"
echo ""

# ===========================================
# 5. Trigger Webhook do Coolify (opcional)
# ===========================================
# Configure as variáveis:
# COOLIFY_URL - URL do seu Coolify (ex: https://coolify.exemplo.com)
# COOLIFY_UUID - UUID do serviço (encontre no Coolify)
# COOLIFY_TOKEN - API Token (crie em Settings > API Tokens)
# ===========================================

COOLIFY_URL="${COOLIFY_URL:-}"
COOLIFY_UUID="${COOLIFY_UUID:-}"
COOLIFY_TOKEN="${COOLIFY_TOKEN:-}"

if [ -n "$COOLIFY_URL" ] && [ -n "$COOLIFY_UUID" ] && [ -n "$COOLIFY_TOKEN" ]; then
    echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${YELLOW}🔄 5/5 - Trigger Auto-Deploy no Coolify${NC}"
    echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    
    WEBHOOK_URL="$COOLIFY_URL/api/v1/deploy?uuid=$COOLIFY_UUID&force=true"
    
    RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" \
        --request GET "$WEBHOOK_URL" \
        --header "Authorization: Bearer $COOLIFY_TOKEN")
    
    if [ "$RESPONSE" = "200" ] || [ "$RESPONSE" = "202" ]; then
        echo -e "${GREEN}✓ Webhook disparado (HTTP $RESPONSE) - Deploy automático iniciado!${NC}"
    else
        echo -e "${YELLOW}⚠ Webhook retornou HTTP $RESPONSE - Verifique o Coolify${NC}"
    fi
    echo ""
fi

# ===========================================
# Resumo Final
# ===========================================
echo ""
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}✅ Release $VERSION concluída!${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo "📦 Imagens publicadas:"
echo "   • $DOCKER_ORG/onlifin:$VERSION (latest)"
echo "   • $DOCKER_ORG/onlifin-db:$VERSION (latest)"
echo ""
echo "🔗 Links:"
echo "   • GitHub: https://github.com/onlitec/onlifin/releases/tag/v$VERSION"
echo "   • DockerHub: https://hub.docker.com/r/$DOCKER_ORG/onlifin"
echo ""

if [ -n "$COOLIFY_URL" ] && [ -n "$COOLIFY_UUID" ] && [ -n "$COOLIFY_TOKEN" ]; then
    echo -e "${GREEN}🚀 Deploy automático iniciado no Coolify!${NC}"
else
    echo "🚀 Para atualizar produção:"
    echo "   • Acesse o Coolify e clique em 'Redeploy'"
    echo ""
    echo "   Para auto-deploy, configure as variáveis:"
    echo "   export COOLIFY_URL='https://seu-coolify.com'"
    echo "   export COOLIFY_UUID='uuid-do-servico'"
    echo "   export COOLIFY_TOKEN='seu-api-token'"
fi
echo ""
