#!/bin/bash
# ===========================================
# Onlifin - Script de Release
# ===========================================
# Uso: ./release.sh [patch|minor|major] "Descrição da release"
# Exemplo: ./release.sh minor "Adiciona atualização automática de saldos"

set -e

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Verificar argumentos
if [ -z "$1" ]; then
    echo -e "${RED}❌ Erro: Tipo de versão não especificado${NC}"
    echo "Uso: ./release.sh [patch|minor|major] \"Descrição\""
    echo "  patch: Correções de bugs (1.0.0 -> 1.0.1)"
    echo "  minor: Novas funcionalidades (1.0.0 -> 1.1.0)"
    echo "  major: Mudanças incompatíveis (1.0.0 -> 2.0.0)"
    exit 1
fi

VERSION_TYPE=$1
DESCRIPTION=${2:-"Release update"}

# Verificar se estamos em um branch válido
CURRENT_BRANCH=$(git branch --show-current)
echo -e "${BLUE}📍 Branch atual: ${CURRENT_BRANCH}${NC}"

# Verificar se há mudanças não commitadas
if [[ -n $(git status -s) ]]; then
    echo -e "${YELLOW}⚠️  Existem mudanças não commitadas:${NC}"
    git status -s
    read -p "Deseja continuar mesmo assim? (s/N) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Ss]$ ]]; then
        exit 1
    fi
fi

# Ler versão atual do package.json
CURRENT_VERSION=$(node -p "require('./package.json').version")
echo -e "${BLUE}📦 Versão atual: ${CURRENT_VERSION}${NC}"

# Calcular nova versão
IFS='.' read -ra VERSION_PARTS <<< "$CURRENT_VERSION"
MAJOR=${VERSION_PARTS[0]}
MINOR=${VERSION_PARTS[1]}
PATCH=${VERSION_PARTS[2]}

case $VERSION_TYPE in
    major)
        MAJOR=$((MAJOR + 1))
        MINOR=0
        PATCH=0
        ;;
    minor)
        MINOR=$((MINOR + 1))
        PATCH=0
        ;;
    patch)
        PATCH=$((PATCH + 1))
        ;;
    *)
        echo -e "${RED}❌ Tipo de versão inválido: $VERSION_TYPE${NC}"
        echo "Use: patch, minor ou major"
        exit 1
        ;;
esac

NEW_VERSION="${MAJOR}.${MINOR}.${PATCH}"
echo -e "${GREEN}🚀 Nova versão: ${NEW_VERSION}${NC}"

# Confirmar
read -p "Confirma a criação da release v${NEW_VERSION}? (s/N) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Ss]$ ]]; then
    echo "Cancelado."
    exit 0
fi

# Atualizar package.json
echo -e "${BLUE}📝 Atualizando package.json...${NC}"
node -e "
const fs = require('fs');
const pkg = require('./package.json');
pkg.version = '${NEW_VERSION}';
fs.writeFileSync('./package.json', JSON.stringify(pkg, null, 2) + '\n');
"

# Atualizar CHANGELOG.md
echo -e "${BLUE}📝 Atualizando CHANGELOG.md...${NC}"
DATE=$(date +%Y-%m-%d)
CHANGELOG_ENTRY="## [${NEW_VERSION}] - ${DATE}

### ${DESCRIPTION}

---

"

# Inserir nova entrada após a primeira linha (# Changelog)
node -e "
const fs = require('fs');
let content = fs.readFileSync('./CHANGELOG.md', 'utf8');
const lines = content.split('\n');
const headerEnd = lines.findIndex((line, i) => i > 0 && line.startsWith('## '));
if (headerEnd === -1) {
    content = lines[0] + '\n\n' + \`${CHANGELOG_ENTRY}\` + lines.slice(1).join('\n');
} else {
    content = lines.slice(0, headerEnd).join('\n') + '\n' + \`${CHANGELOG_ENTRY}\` + lines.slice(headerEnd).join('\n');
}
fs.writeFileSync('./CHANGELOG.md', content);
"

# Commit das mudanças
echo -e "${BLUE}📦 Criando commit...${NC}"
git add package.json CHANGELOG.md
git commit -m "chore(release): v${NEW_VERSION} - ${DESCRIPTION}"

# Criar tag
echo -e "${BLUE}🏷️  Criando tag v${NEW_VERSION}...${NC}"
git tag -a "v${NEW_VERSION}" -m "Release v${NEW_VERSION}

${DESCRIPTION}"

# Push
echo -e "${BLUE}🚀 Enviando para o GitHub...${NC}"
git push origin "${CURRENT_BRANCH}"
git push origin "v${NEW_VERSION}"

echo ""
echo -e "${GREEN}✅ Release v${NEW_VERSION} criada com sucesso!${NC}"
echo ""
echo -e "📍 Tag: https://github.com/onlitec/onlifin/releases/tag/v${NEW_VERSION}"
echo -e "📝 Para criar uma release no GitHub com notas detalhadas, acesse:"
echo -e "   https://github.com/onlitec/onlifin/releases/new?tag=v${NEW_VERSION}"
