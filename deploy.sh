#!/bin/bash
# ==========================================
# OnliFin - Deploy Script
# ==========================================

set -e

APP_DIR="/opt/onlifin"
LOG_FILE="$APP_DIR/deploy.log"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

cd "$APP_DIR"

log "=========================================="
log "🚀 INICIANDO DEPLOY - OnliFin"
log "=========================================="

# 1. Buscar atualizações do repositório
log "📥 Buscando atualizações do Git..."
git fetch origin main

# 2. Verificar se há mudanças
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

# 3. Aplicar atualizações
log "🔄 Aplicando atualizações..."
git reset --hard origin/main

# 4. Build do frontend
log "🔨 Construindo frontend..."
if [ -f "package.json" ]; then
    npm install --silent 2>/dev/null || true
    npm run build 2>&1 | tail -5
fi

# 5. Reiniciar container frontend
log "🐳 Reiniciando container frontend..."
docker compose restart frontend 2>&1 | grep -v "obsolete" || true

# 6. Verificar status
log "📊 Status dos containers:"
docker ps --format "table {{.Names}}\t{{.Status}}" | grep onlifin | while read line; do
    log "   $line"
done

log "=========================================="
log "✅ DEPLOY CONCLUÍDO COM SUCESSO"
log "📌 Versão atual: $(git rev-parse --short HEAD)"
log "=========================================="
