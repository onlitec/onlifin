#!/bin/bash

# 💾 Script de Backup Automático - Onlifin
# Executa backup diário do banco MySQL

set -e

# Configurações
BACKUP_DIR="/backups"
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="onlifin_backup_${DATE}.sql"
RETENTION_DAYS=7

# Cores para log
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR: $1${NC}"
}

warning() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] WARNING: $1${NC}"
}

# Função principal de backup
perform_backup() {
    log "Iniciando backup do banco Onlifin..."
    
    # Verificar se o MySQL está acessível
    if ! mysqladmin ping -h"$MYSQL_HOST" -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" --silent; then
        error "Não foi possível conectar ao MySQL"
        exit 1
    fi
    
    # Criar diretório de backup se não existir
    mkdir -p "$BACKUP_DIR"
    
    # Executar backup
    log "Criando backup: $BACKUP_FILE"
    
    mysqldump \
        -h"$MYSQL_HOST" \
        -u"$MYSQL_USER" \
        -p"$MYSQL_PASSWORD" \
        --single-transaction \
        --routines \
        --triggers \
        --add-drop-table \
        --extended-insert \
        --compress \
        "$MYSQL_DATABASE" > "$BACKUP_DIR/$BACKUP_FILE"
    
    # Verificar se o backup foi criado
    if [ -f "$BACKUP_DIR/$BACKUP_FILE" ]; then
        BACKUP_SIZE=$(du -h "$BACKUP_DIR/$BACKUP_FILE" | cut -f1)
        log "Backup criado com sucesso: $BACKUP_FILE ($BACKUP_SIZE)"
    else
        error "Falha ao criar backup"
        exit 1
    fi
    
    # Comprimir backup
    log "Comprimindo backup..."
    gzip "$BACKUP_DIR/$BACKUP_FILE"
    
    if [ -f "$BACKUP_DIR/$BACKUP_FILE.gz" ]; then
        COMPRESSED_SIZE=$(du -h "$BACKUP_DIR/$BACKUP_FILE.gz" | cut -f1)
        log "Backup comprimido: $BACKUP_FILE.gz ($COMPRESSED_SIZE)"
    fi
}

# Função para limpeza de backups antigos
cleanup_old_backups() {
    log "Limpando backups antigos (mais de $RETENTION_DAYS dias)..."
    
    find "$BACKUP_DIR" -name "onlifin_backup_*.sql.gz" -mtime +$RETENTION_DAYS -delete
    
    REMAINING_BACKUPS=$(find "$BACKUP_DIR" -name "onlifin_backup_*.sql.gz" | wc -l)
    log "Backups restantes: $REMAINING_BACKUPS"
}

# Função para verificar espaço em disco
check_disk_space() {
    AVAILABLE_SPACE=$(df "$BACKUP_DIR" | awk 'NR==2 {print $4}')
    AVAILABLE_MB=$((AVAILABLE_SPACE / 1024))
    
    if [ $AVAILABLE_MB -lt 100 ]; then
        warning "Pouco espaço em disco disponível: ${AVAILABLE_MB}MB"
    else
        log "Espaço disponível: ${AVAILABLE_MB}MB"
    fi
}

# Função para enviar notificação (opcional)
send_notification() {
    local status=$1
    local message=$2
    
    if [ -n "$SLACK_WEBHOOK_URL" ]; then
        curl -X POST -H 'Content-type: application/json' \
            --data "{\"text\":\"🗄️ Backup Onlifin: $status - $message\"}" \
            "$SLACK_WEBHOOK_URL" || true
    fi
}

# Execução principal
main() {
    log "=== Iniciando processo de backup ==="
    
    # Verificar variáveis de ambiente
    if [ -z "$MYSQL_HOST" ] || [ -z "$MYSQL_USER" ] || [ -z "$MYSQL_PASSWORD" ] || [ -z "$MYSQL_DATABASE" ]; then
        error "Variáveis de ambiente do MySQL não configuradas"
        exit 1
    fi
    
    # Verificar espaço em disco
    check_disk_space
    
    # Executar backup
    if perform_backup; then
        log "Backup executado com sucesso"
        send_notification "SUCCESS" "Backup $BACKUP_FILE.gz criado com sucesso"
    else
        error "Falha no backup"
        send_notification "FAILED" "Falha ao criar backup"
        exit 1
    fi
    
    # Limpeza de backups antigos
    cleanup_old_backups
    
    log "=== Processo de backup concluído ==="
}

# Executar se chamado diretamente
if [ "${BASH_SOURCE[0]}" == "${0}" ]; then
    main "$@"
fi
