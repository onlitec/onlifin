#!/bin/bash

# Script de build otimizado para Onlifin Docker
set -e

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Função para log colorido
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

warn() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] WARNING: $1${NC}"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR: $1${NC}"
}

# Verificar se Docker está rodando
if ! docker info > /dev/null 2>&1; then
    error "Docker não está rodando. Por favor, inicie o Docker primeiro."
    exit 1
fi

# Verificar se docker-compose está disponível
if ! command -v docker-compose > /dev/null 2>&1; then
    error "docker-compose não encontrado. Por favor, instale o Docker Compose."
    exit 1
fi

log "🐳 Iniciando build do Onlifin Docker..."

# Limpar builds anteriores se solicitado
if [[ "$1" == "--clean" ]]; then
    log "🧹 Limpando imagens e containers anteriores..."
    docker-compose down --rmi all --volumes --remove-orphans 2>/dev/null || true
    docker system prune -f
fi

# Verificar se .env existe
if [[ ! -f .env ]]; then
    warn "Arquivo .env não encontrado. Criando a partir do .env.example..."
    cp .env.example .env
    log "✅ Arquivo .env criado. Por favor, configure as variáveis necessárias."
fi

# Build da imagem
log "🔨 Fazendo build da imagem Docker..."
docker-compose build --no-cache

# Verificar se o build foi bem-sucedido
if [[ $? -eq 0 ]]; then
    log "✅ Build concluído com sucesso!"
else
    error "❌ Falha no build da imagem."
    exit 1
fi

# Iniciar os serviços
log "🚀 Iniciando serviços..."
docker-compose up -d

# Aguardar a aplicação ficar pronta
log "⏳ Aguardando aplicação ficar pronta..."
sleep 10

# Verificar se a aplicação está respondendo
max_attempts=30
attempt=1

while [[ $attempt -le $max_attempts ]]; do
    if curl -f -s http://localhost:8080/ > /dev/null; then
        log "✅ Aplicação está rodando e respondendo!"
        break
    else
        if [[ $attempt -eq $max_attempts ]]; then
            error "❌ Aplicação não respondeu após $max_attempts tentativas."
            log "📋 Verificando logs..."
            docker-compose logs --tail=50
            exit 1
        fi
        log "⏳ Tentativa $attempt/$max_attempts - Aguardando aplicação..."
        sleep 5
        ((attempt++))
    fi
done

# Mostrar informações finais
log "🎉 Onlifin Docker está rodando com sucesso!"
echo ""
echo -e "${BLUE}📊 Informações do Deploy:${NC}"
echo -e "  🌐 URL: ${GREEN}http://localhost:8080${NC}"
echo -e "  📁 Banco: ${GREEN}SQLite (./database/database.sqlite)${NC}"
echo -e "  📝 Logs: ${GREEN}docker-compose logs -f${NC}"
echo ""
echo -e "${BLUE}🛠️  Comandos úteis:${NC}"
echo -e "  Parar:     ${GREEN}docker-compose stop${NC}"
echo -e "  Reiniciar: ${GREEN}docker-compose restart${NC}"
echo -e "  Logs:      ${GREEN}docker-compose logs -f${NC}"
echo -e "  Shell:     ${GREEN}docker-compose exec onlifin sh${NC}"
echo ""

# Verificar se há atualizações pendentes
if [[ -f composer.lock ]] && [[ -f package-lock.json ]]; then
    log "✅ Dependências PHP e Node.js estão atualizadas."
else
    warn "Algumas dependências podem estar desatualizadas. Execute 'composer install' e 'npm install' se necessário."
fi

log "🎯 Deploy concluído! Acesse http://localhost:8080 para usar o Onlifin."
