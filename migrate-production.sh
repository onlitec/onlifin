#!/bin/bash

# 🗄️ Script de Migração Segura para Produção - Onlifin API
# Este script executa migrações de forma segura no ambiente de produção

set -e  # Parar execução em caso de erro

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configurações
PROJECT_PATH="/var/www/html/onlifin"
BACKUP_PATH="/backup/onlifin"
LOG_FILE="/var/log/onlifin-migration.log"

# Função para logging
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1" >> $LOG_FILE
}

error() {
    echo -e "${RED}[ERROR] $1${NC}"
    echo "[ERROR] $1" >> $LOG_FILE
}

warning() {
    echo -e "${YELLOW}[WARNING] $1${NC}"
    echo "[WARNING] $1" >> $LOG_FILE
}

info() {
    echo -e "${BLUE}[INFO] $1${NC}"
    echo "[INFO] $1" >> $LOG_FILE
}

# Verificar se está no diretório correto
check_environment() {
    log "Verificando ambiente..."
    
    if [ ! -d "$PROJECT_PATH" ]; then
        error "Diretório do projeto não encontrado: $PROJECT_PATH"
        exit 1
    fi
    
    cd $PROJECT_PATH
    
    if [ ! -f "artisan" ]; then
        error "Arquivo artisan não encontrado. Certifique-se de estar no diretório correto do Laravel."
        exit 1
    fi
    
    if [ ! -f ".env" ]; then
        error "Arquivo .env não encontrado."
        exit 1
    fi
    
    # Verificar se é ambiente de produção
    ENV=$(grep "APP_ENV=" .env | cut -d '=' -f2)
    if [ "$ENV" != "production" ]; then
        warning "APP_ENV não está definido como 'production'. Ambiente atual: $ENV"
        read -p "Continuar mesmo assim? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            exit 1
        fi
    fi
    
    log "Ambiente verificado com sucesso!"
}

# Criar backup do banco de dados
backup_database() {
    log "Criando backup do banco de dados..."
    
    TIMESTAMP=$(date +%Y%m%d_%H%M%S)
    BACKUP_DIR="$BACKUP_PATH/migration_backup_$TIMESTAMP"
    
    mkdir -p $BACKUP_DIR
    
    # Obter configurações do banco do .env
    DB_HOST=$(grep "DB_HOST=" .env | cut -d '=' -f2)
    DB_PORT=$(grep "DB_PORT=" .env | cut -d '=' -f2)
    DB_DATABASE=$(grep "DB_DATABASE=" .env | cut -d '=' -f2)
    DB_USERNAME=$(grep "DB_USERNAME=" .env | cut -d '=' -f2)
    DB_PASSWORD=$(grep "DB_PASSWORD=" .env | cut -d '=' -f2)
    
    # Remover aspas se existirem
    DB_HOST=$(echo $DB_HOST | tr -d '"')
    DB_PORT=$(echo $DB_PORT | tr -d '"')
    DB_DATABASE=$(echo $DB_DATABASE | tr -d '"')
    DB_USERNAME=$(echo $DB_USERNAME | tr -d '"')
    DB_PASSWORD=$(echo $DB_PASSWORD | tr -d '"')
    
    info "Fazendo backup do banco: $DB_DATABASE"
    
    # Fazer backup do MySQL
    if command -v mysqldump >/dev/null 2>&1; then
        if [ -n "$DB_PASSWORD" ]; then
            mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "$BACKUP_DIR/database_backup.sql" 2>/dev/null || {
                error "Falha ao criar backup do banco de dados"
                exit 1
            }
        else
            mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" "$DB_DATABASE" > "$BACKUP_DIR/database_backup.sql" 2>/dev/null || {
                error "Falha ao criar backup do banco de dados"
                exit 1
            }
        fi
        
        log "Backup do banco criado: $BACKUP_DIR/database_backup.sql"
    else
        error "mysqldump não encontrado. Instale o cliente MySQL."
        exit 1
    fi
    
    # Salvar caminho do backup para possível rollback
    echo $BACKUP_DIR > /tmp/onlifin_migration_backup
    
    log "Backup concluído com sucesso!"
}

# Verificar migrações pendentes
check_pending_migrations() {
    log "Verificando migrações pendentes..."
    
    # Verificar status das migrações
    php artisan migrate:status
    
    # Contar migrações pendentes
    PENDING_COUNT=$(php artisan migrate:status --pending | grep -c "Pending" || echo "0")
    
    if [ "$PENDING_COUNT" -eq 0 ]; then
        info "Nenhuma migração pendente encontrada."
        read -p "Continuar mesmo assim? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            log "Operação cancelada pelo usuário."
            exit 0
        fi
    else
        info "Encontradas $PENDING_COUNT migração(ões) pendente(s)."
    fi
}

# Verificar se tabela do Sanctum será criada
check_sanctum_migration() {
    log "Verificando migração do Laravel Sanctum..."
    
    # Verificar se a migração do Sanctum existe
    if ls database/migrations/*_create_personal_access_tokens_table.php 1> /dev/null 2>&1; then
        info "Migração do Sanctum encontrada."
        
        # Verificar se a tabela já existe
        TABLE_EXISTS=$(php artisan tinker --execute="echo Schema::hasTable('personal_access_tokens') ? 'yes' : 'no';" 2>/dev/null | grep -o "yes\|no" || echo "no")
        
        if [ "$TABLE_EXISTS" = "yes" ]; then
            info "Tabela 'personal_access_tokens' já existe."
        else
            info "Tabela 'personal_access_tokens' será criada."
        fi
    else
        warning "Migração do Sanctum não encontrada."
        info "Execute: php artisan vendor:publish --provider=\"Laravel\\Sanctum\\SanctumServiceProvider\""
    fi
}

# Executar migrações
run_migrations() {
    log "Executando migrações..."
    
    # Confirmar execução
    echo -e "${YELLOW}ATENÇÃO: As migrações serão executadas no banco de produção!${NC}"
    echo "Banco: $DB_DATABASE"
    echo "Host: $DB_HOST"
    read -p "Tem certeza que deseja continuar? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        log "Operação cancelada pelo usuário."
        exit 0
    fi
    
    # Executar migrações
    php artisan migrate --force || {
        error "Falha ao executar migrações!"
        
        # Oferecer rollback
        echo -e "${RED}Erro durante a migração!${NC}"
        read -p "Deseja fazer rollback do banco de dados? (y/N): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            rollback_database
        fi
        
        exit 1
    }
    
    log "Migrações executadas com sucesso!"
}

# Verificar integridade pós-migração
verify_migration() {
    log "Verificando integridade pós-migração..."
    
    # Verificar se as tabelas principais existem
    TABLES=("users" "accounts" "categories" "transactions" "personal_access_tokens")
    
    for table in "${TABLES[@]}"; do
        TABLE_EXISTS=$(php artisan tinker --execute="echo Schema::hasTable('$table') ? 'yes' : 'no';" 2>/dev/null | grep -o "yes\|no" || echo "no")
        
        if [ "$TABLE_EXISTS" = "yes" ]; then
            info "✓ Tabela '$table' existe"
        else
            error "✗ Tabela '$table' não encontrada"
        fi
    done
    
    # Verificar se é possível conectar ao banco
    php artisan tinker --execute="DB::connection()->getPdo(); echo 'Conexão OK';" 2>/dev/null || {
        error "Falha na conexão com o banco de dados"
        exit 1
    }
    
    log "Verificação de integridade concluída!"
}

# Rollback do banco de dados
rollback_database() {
    error "Executando rollback do banco de dados..."
    
    if [ -f /tmp/onlifin_migration_backup ]; then
        BACKUP_DIR=$(cat /tmp/onlifin_migration_backup)
        BACKUP_FILE="$BACKUP_DIR/database_backup.sql"
        
        if [ -f "$BACKUP_FILE" ]; then
            warning "Restaurando banco de dados do backup: $BACKUP_FILE"
            
            if [ -n "$DB_PASSWORD" ]; then
                mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" < "$BACKUP_FILE" || {
                    error "Falha ao restaurar backup do banco"
                    exit 1
                }
            else
                mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" "$DB_DATABASE" < "$BACKUP_FILE" || {
                    error "Falha ao restaurar backup do banco"
                    exit 1
                }
            fi
            
            log "Banco de dados restaurado com sucesso!"
        else
            error "Arquivo de backup não encontrado: $BACKUP_FILE"
        fi
    else
        error "Caminho do backup não encontrado"
    fi
}

# Limpeza pós-migração
cleanup() {
    log "Executando limpeza pós-migração..."
    
    # Limpar caches
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    
    # Otimizar para produção
    php artisan config:cache
    php artisan route:cache
    
    log "Limpeza concluída!"
}

# Função principal
main() {
    log "🗄️ Iniciando migração segura para produção..."
    
    check_environment
    backup_database
    check_pending_migrations
    check_sanctum_migration
    run_migrations
    verify_migration
    cleanup
    
    log "✅ Migração concluída com sucesso!"
    log "📊 Status das migrações:"
    php artisan migrate:status
    
    info "Backup salvo em: $(cat /tmp/onlifin_migration_backup 2>/dev/null || echo 'N/A')"
    info "Log completo em: $LOG_FILE"
}

# Verificar se está sendo executado como usuário apropriado
if [ "$EUID" -eq 0 ]; then
    warning "Executando como root. Considere usar o usuário www-data."
fi

# Executar função principal
main "$@"
