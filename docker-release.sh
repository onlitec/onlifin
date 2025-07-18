#!/bin/bash

# Script para criar releases versionados para Docker Hub
# Implementa versionamento semântico (MAJOR.MINOR.PATCH)

set -e

echo "🏷️  Onlifin Docker Release Manager"
echo "=================================="
echo ""

# Verificar se está no diretório correto
if [ ! -f "docker-compose.yml" ]; then
    echo "❌ Erro: Execute este script no diretório raiz do projeto Onlifin"
    exit 1
fi

# Verificar se há mudanças não commitadas
if ! git diff-index --quiet HEAD --; then
    echo "❌ Erro: Há mudanças não commitadas no repositório"
    echo "   Faça commit de todas as mudanças antes de criar uma release"
    exit 1
fi

# Obter versão atual do git tags
CURRENT_VERSION=$(git describe --tags --abbrev=0 2>/dev/null || echo "v0.0.0")
echo "📊 Versão atual: $CURRENT_VERSION"

# Remover 'v' prefix se existir
CURRENT_VERSION=${CURRENT_VERSION#v}

# Parse da versão atual
IFS='.' read -r MAJOR MINOR PATCH <<< "$CURRENT_VERSION"

echo ""
echo "🔢 Escolha o tipo de release:"
echo "   1) PATCH (bug fixes): $MAJOR.$MINOR.$((PATCH + 1))"
echo "   2) MINOR (new features): $MAJOR.$((MINOR + 1)).0"
echo "   3) MAJOR (breaking changes): $((MAJOR + 1)).0.0"
echo "   4) Custom version"
echo ""

read -p "Opção (1-4): " -n 1 -r
echo

case $REPLY in
    1)
        NEW_VERSION="$MAJOR.$MINOR.$((PATCH + 1))"
        RELEASE_TYPE="patch"
        ;;
    2)
        NEW_VERSION="$MAJOR.$((MINOR + 1)).0"
        RELEASE_TYPE="minor"
        ;;
    3)
        NEW_VERSION="$((MAJOR + 1)).0.0"
        RELEASE_TYPE="major"
        ;;
    4)
        read -p "Digite a nova versão (formato: X.Y.Z): " NEW_VERSION
        RELEASE_TYPE="custom"
        ;;
    *)
        echo "❌ Opção inválida"
        exit 1
        ;;
esac

echo ""
echo "🏷️  Nova versão: v$NEW_VERSION"
echo "📝 Tipo de release: $RELEASE_TYPE"
echo ""

# Confirmar release
read -p "Continuar com a release? (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "❌ Release cancelada"
    exit 1
fi

# Obter informações do commit
COMMIT_HASH=$(git rev-parse --short HEAD)
BRANCH=$(git branch --show-current)
DATE=$(date +%Y-%m-%d)

echo ""
echo "🏗️  Iniciando processo de release..."

# 1. Criar tag git
echo "📌 Criando tag git v$NEW_VERSION..."
git tag -a "v$NEW_VERSION" -m "Release v$NEW_VERSION

Release Type: $RELEASE_TYPE
Date: $DATE
Branch: $BRANCH
Commit: $COMMIT_HASH

Changes in this release:
- See CHANGELOG.md for detailed changes
- Docker image: onlitec/onlifin:$NEW_VERSION"

# 2. Push da tag
echo "📤 Enviando tag para repositório..."
git push origin "v$NEW_VERSION"

# 3. Build da imagem
echo "🏗️  Building Docker image..."
docker-compose build --no-cache

# 4. Criar tags Docker
echo "🏷️  Criando tags Docker..."

# Tag com versão específica
docker tag onlifin_onlifin:latest onlitec/onlifin:$NEW_VERSION
docker tag onlifin_onlifin:latest onlitec/onlifin:v$NEW_VERSION

# Tag latest (apenas para releases não-beta)
if [[ "$NEW_VERSION" != *"beta"* ]] && [[ "$NEW_VERSION" != *"alpha"* ]]; then
    docker tag onlifin_onlifin:latest onlitec/onlifin:latest
fi

# Tag com data
docker tag onlifin_onlifin:latest onlitec/onlifin:$DATE

# Tag com commit
docker tag onlifin_onlifin:latest onlitec/onlifin:$COMMIT_HASH

echo "✅ Tags criadas:"
echo "   - onlitec/onlifin:$NEW_VERSION"
echo "   - onlitec/onlifin:v$NEW_VERSION"
echo "   - onlitec/onlifin:$DATE"
echo "   - onlitec/onlifin:$COMMIT_HASH"
if [[ "$NEW_VERSION" != *"beta"* ]] && [[ "$NEW_VERSION" != *"alpha"* ]]; then
    echo "   - onlitec/onlifin:latest"
fi

# 5. Login no Docker Hub
echo ""
echo "🔐 Verificando login no Docker Hub..."
if ! docker info | grep -q "Username"; then
    echo "❌ Você precisa fazer login no Docker Hub:"
    echo "   docker login -u onlitec"
    exit 1
fi

# 6. Push das imagens
echo ""
echo "🚀 Enviando imagens para Docker Hub..."

docker push onlitec/onlifin:$NEW_VERSION
docker push onlitec/onlifin:v$NEW_VERSION
docker push onlitec/onlifin:$DATE
docker push onlitec/onlifin:$COMMIT_HASH

if [[ "$NEW_VERSION" != *"beta"* ]] && [[ "$NEW_VERSION" != *"alpha"* ]]; then
    docker push onlitec/onlifin:latest
fi

echo ""
echo "🎉 Release v$NEW_VERSION criada com sucesso!"
echo ""
echo "📋 Resumo:"
echo "   - Git tag: v$NEW_VERSION"
echo "   - Docker images enviadas para: https://hub.docker.com/r/onlitec/onlifin"
echo "   - Comando para usar: docker pull onlitec/onlifin:$NEW_VERSION"
echo ""
echo "📝 Próximos passos:"
echo "   1. Atualizar CHANGELOG.md com as mudanças desta versão"
echo "   2. Criar release no GitHub: https://github.com/onlitec/onlifin/releases"
echo "   3. Atualizar documentação se necessário"
echo "   4. Notificar usuários sobre a nova versão"
