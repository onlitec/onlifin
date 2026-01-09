#!/bin/bash
# ==========================================
# OnliFin - Auto Deploy Script
# ==========================================

set -e

APP_DIR="/home/alfreire/docker/apps/onlifin"
LOG_FILE="$APP_DIR/deploy.log"
BACKUP_DIR="$APP_DIR/backups"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

cd "$APP_DIR"

log "=========================================="
log "🚀 INICIANDO DEPLOY AUTOMÁTICO - OnliFin"
log "=========================================="

# 1. Backup de configurações locais
log "📦 Criando backup de configurações..."
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p "$BACKUP_DIR"
if [ -f ".env" ]; then
    cp .env "$BACKUP_DIR/.env.backup.$DATE"
fi

# 2. Buscar atualizações do repositório
log "📥 Buscando atualizações do Git..."
git fetch origin main

# 3. Verificar se há mudanças
LOCAL=$(git rev-parse HEAD)
REMOTE=$(git rev-parse origin/main)

if [ "$LOCAL" = "$REMOTE" ]; then
    log "✅ Repositório já está atualizado"
    exit 0
fi

log "📊 Commits a serem aplicados:"
git log --oneline HEAD..origin/main | while read line; do
    log "   - $line"
done

# 4. Aplicar atualizações
log "🔄 Aplicando atualizações..."
git reset --hard origin/main

# 5. Restaurar configurações locais de produção
log "🔧 Aplicando configurações de produção..."

# Garantir APP_PORT=8080
if grep -q "APP_PORT=80$" .env 2>/dev/null; then
    sed -i 's/APP_PORT=80$/APP_PORT=8080/' .env
    log "   ✅ APP_PORT corrigido para 8080"
fi

# Garantir rede externa
if grep -q "driver: bridge" docker-compose.production.yml 2>/dev/null; then
    sed -i 's/driver: bridge/external: true/' docker-compose.production.yml
    log "   ✅ Rede configurada como externa"
fi

# Garantir DOCKER_API_VERSION no watchtower
if ! grep -q "DOCKER_API_VERSION" docker-compose.production.yml 2>/dev/null; then
    sed -i '/command: --interval 300 --cleanup --include-stopped/a\    environment:\n      - DOCKER_API_VERSION=1.45' docker-compose.production.yml
    log "   ✅ DOCKER_API_VERSION adicionado ao watchtower"
fi

# Garantir SELECT 1 no migrator
if grep -q "\\\\q" docker-compose.production.yml 2>/dev/null; then
    sed -i 's/\\q/SELECT 1/g' docker-compose.production.yml
    log "   ✅ Migrator corrigido para usar SELECT 1"
fi

# 6. Baixar novas imagens
log "🐳 Baixando novas imagens Docker..."
docker compose -f docker-compose.production.yml pull

# 7. Reiniciar containers
log "🔄 Reiniciando containers..."
docker compose -f docker-compose.production.yml up -d

# 8. Aguardar containers
log "⏳ Aguardando containers iniciarem..."
sleep 5

# 9. Verificar status
log "📊 Status dos containers:"
docker ps --format "table {{.Names}}\t{{.Status}}" | grep onlifin | while read line; do
    log "   $line"
done

# 10. Limpar backups antigos (manter últimos 5)
log "🧹 Limpando backups antigos..."
ls -t "$BACKUP_DIR"/.env.backup.* 2>/dev/null | tail -n +6 | xargs -r rm

log "=========================================="
log "✅ DEPLOY CONCLUÍDO COM SUCESSO"
log "📌 Versão atual: $(git rev-parse --short HEAD)"
log "🌐 Aplicação: https://onlifin.onlitec.com.br"
log "=========================================="
