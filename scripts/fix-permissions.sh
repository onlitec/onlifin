#!/bin/bash

# ========================================================================
# ONLIFIN - SCRIPT DE CORREÇÃO DE PERMISSÕES
# ========================================================================
# 
# Este script corrige problemas de permissão comuns no Laravel
# que podem causar erros HTTP 500.
#
# ========================================================================

set -e

echo "🔧 Corrigindo permissões do Onlifin..."

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Função para log
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

warn() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] WARNING: $1${NC}"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR: $1${NC}"
    exit 1
}

# Verificar se está rodando como root ou com sudo
if [ "$EUID" -ne 0 ]; then
    error "Este script precisa ser executado com sudo"
fi

# Obter usuário atual (não root)
CURRENT_USER=${SUDO_USER:-$(whoami)}

log "Usuário atual: $CURRENT_USER"
log "Configurando permissões para Laravel..."

# 1. Adicionar usuário ao grupo www-data
log "Adicionando usuário ao grupo www-data..."
usermod -a -G www-data $CURRENT_USER

# 2. Configurar permissões do storage
log "Configurando permissões do storage..."
chown -R $CURRENT_USER:www-data storage/
chmod -R 2775 storage/

# 3. Configurar permissões do bootstrap/cache
log "Configurando permissões do bootstrap/cache..."
chown -R $CURRENT_USER:www-data bootstrap/cache/
chmod -R 2775 bootstrap/cache/

# 4. Configurar permissões do public
log "Configurando permissões do public..."
chown -R $CURRENT_USER:www-data public/
chmod -R 2775 public/

# 5. Configurar permissões de arquivos específicos
log "Configurando permissões de arquivos específicos..."
chmod 644 .env
chmod 644 composer.json
chmod 644 package.json

# 6. Limpar cache do Laravel
log "Limpando cache do Laravel..."
sudo -u $CURRENT_USER php artisan config:clear
sudo -u $CURRENT_USER php artisan view:clear
sudo -u $CURRENT_USER php artisan cache:clear
sudo -u $CURRENT_USER php artisan route:clear

# 7. Verificar permissões
log "Verificando permissões..."
echo "Storage:"
ls -la storage/framework/
echo
echo "Bootstrap cache:"
ls -la bootstrap/cache/
echo

# 8. Testar aplicação
log "Testando aplicação..."
if curl -f http://localhost/ > /dev/null 2>&1; then
    log "✅ Aplicação funcionando corretamente"
else
    warn "⚠️ Aplicação pode não estar funcionando corretamente"
fi

log "✅ Permissões corrigidas com sucesso!"
log "Próximos passos:"
log "1. Faça logout e login novamente para aplicar as mudanças de grupo"
log "2. Teste a aplicação no navegador"
log "3. Se ainda houver problemas, verifique os logs: tail -f storage/logs/laravel.log"