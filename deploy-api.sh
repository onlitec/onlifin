#!/bin/bash

# 🚀 Script de Deploy da API Onlifin para Produção
# Autor: Onlifin Development Team
# Data: $(date +%Y-%m-%d)

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
LOG_FILE="/var/log/onlifin-deploy.log"

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

# Função para verificar se comando existe
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Verificar pré-requisitos
check_prerequisites() {
    log "Verificando pré-requisitos..."
    
    if [ ! -d "$PROJECT_PATH" ]; then
        error "Diretório do projeto não encontrado: $PROJECT_PATH"
        exit 1
    fi
    
    if ! command_exists php; then
        error "PHP não encontrado"
        exit 1
    fi
    
    if ! command_exists composer; then
        error "Composer não encontrado"
        exit 1
    fi
    
    if ! command_exists git; then
        error "Git não encontrado"
        exit 1
    fi
    
    log "Pré-requisitos verificados com sucesso!"
}

# Criar backup
create_backup() {
    log "Criando backup..."
    
    TIMESTAMP=$(date +%Y%m%d_%H%M%S)
    BACKUP_DIR="$BACKUP_PATH/backup_$TIMESTAMP"
    
    mkdir -p $BACKUP_DIR
    
    # Backup do banco de dados
    if command_exists mysqldump; then
        info "Fazendo backup do banco MySQL..."
        mysqldump -u root -p onlifin_production > "$BACKUP_DIR/database_backup.sql" 2>/dev/null || {
            warning "Não foi possível fazer backup do MySQL automaticamente"
            info "Execute manualmente: mysqldump -u [user] -p[pass] onlifin_production > backup.sql"
        }
    fi
    
    # Backup dos arquivos críticos
    info "Fazendo backup dos arquivos..."
    cp -r "$PROJECT_PATH/.env" "$BACKUP_DIR/" 2>/dev/null || warning "Arquivo .env não encontrado"
    cp -r "$PROJECT_PATH/storage" "$BACKUP_DIR/" 2>/dev/null || warning "Diretório storage não encontrado"
    
    log "Backup criado em: $BACKUP_DIR"
    echo $BACKUP_DIR > /tmp/onlifin_last_backup
}

# Ativar modo de manutenção
enable_maintenance() {
    log "Ativando modo de manutenção..."
    cd $PROJECT_PATH
    php artisan down --message="Atualizando sistema com nova API" --retry=60 || {
        error "Falha ao ativar modo de manutenção"
        exit 1
    }
}

# Desativar modo de manutenção
disable_maintenance() {
    log "Desativando modo de manutenção..."
    cd $PROJECT_PATH
    php artisan up || {
        error "Falha ao desativar modo de manutenção"
    }
}

# Atualizar código
update_code() {
    log "Atualizando código..."
    cd $PROJECT_PATH
    
    # Criar branch de backup
    BACKUP_BRANCH="backup-pre-api-$(date +%Y%m%d_%H%M%S)"
    git branch $BACKUP_BRANCH || warning "Não foi possível criar branch de backup"
    
    # Atualizar código
    git fetch origin || {
        error "Falha ao fazer fetch do repositório"
        exit 1
    }
    
    git pull origin main || {
        error "Falha ao fazer pull do repositório"
        exit 1
    }
    
    log "Código atualizado com sucesso!"
}

# Instalar dependências
install_dependencies() {
    log "Instalando/atualizando dependências..."
    cd $PROJECT_PATH
    
    composer install --no-dev --optimize-autoloader || {
        error "Falha ao instalar dependências do Composer"
        exit 1
    }
    
    log "Dependências instaladas com sucesso!"
}

# Executar migrações
run_migrations() {
    log "Executando migrações do banco de dados..."
    cd $PROJECT_PATH
    
    # Verificar migrações pendentes
    php artisan migrate:status
    
    # Executar migrações
    php artisan migrate --force || {
        error "Falha ao executar migrações"
        exit 1
    }
    
    log "Migrações executadas com sucesso!"
}

# Configurar permissões
set_permissions() {
    log "Configurando permissões..."
    cd $PROJECT_PATH
    
    chown -R www-data:www-data storage/ bootstrap/cache/ || {
        warning "Não foi possível alterar proprietário (pode precisar de sudo)"
    }
    
    chmod -R 775 storage/ bootstrap/cache/ || {
        warning "Não foi possível alterar permissões"
    }
    
    log "Permissões configuradas!"
}

# Otimizar aplicação
optimize_application() {
    log "Otimizando aplicação para produção..."
    cd $PROJECT_PATH
    
    # Limpar caches
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    php artisan cache:clear
    
    # Otimizar autoloader
    composer dump-autoload --optimize
    
    # Criar caches para produção
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
    log "Aplicação otimizada!"
}

# Testar API
test_api() {
    log "Testando API..."
    
    # Aguardar alguns segundos para estabilizar
    sleep 5
    
    # Testar endpoint de documentação
    if command_exists curl; then
        RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost/api/docs" || echo "000")
        if [ "$RESPONSE" = "200" ]; then
            log "API respondendo corretamente!"
        else
            warning "API pode não estar respondendo corretamente (HTTP: $RESPONSE)"
        fi
    else
        warning "curl não encontrado, não foi possível testar API automaticamente"
    fi
}

# Verificar logs
check_logs() {
    log "Verificando logs por erros..."
    cd $PROJECT_PATH
    
    if [ -f "storage/logs/laravel.log" ]; then
        ERROR_COUNT=$(tail -100 storage/logs/laravel.log | grep -i "error\|exception\|fatal" | wc -l)
        if [ $ERROR_COUNT -gt 0 ]; then
            warning "Encontrados $ERROR_COUNT possíveis erros nos logs recentes"
            info "Verifique: tail -f storage/logs/laravel.log"
        else
            log "Nenhum erro encontrado nos logs recentes"
        fi
    fi
}

# Rollback em caso de erro
rollback() {
    error "Executando rollback devido a falha no deploy..."
    
    cd $PROJECT_PATH
    
    # Ativar modo de manutenção
    php artisan down --message="Revertendo alterações" --retry=30
    
    # Restaurar código se possível
    if [ -f /tmp/onlifin_last_backup ]; then
        BACKUP_DIR=$(cat /tmp/onlifin_last_backup)
        if [ -d "$BACKUP_DIR" ]; then
            warning "Restaurando arquivos do backup..."
            cp -r "$BACKUP_DIR/.env" "$PROJECT_PATH/" 2>/dev/null
            cp -r "$BACKUP_DIR/storage" "$PROJECT_PATH/" 2>/dev/null
        fi
    fi
    
    # Desativar modo de manutenção
    php artisan up
    
    error "Rollback concluído. Verifique os logs para mais detalhes."
}

# Função principal
main() {
    log "🚀 Iniciando deploy da API Onlifin..."
    
    # Configurar trap para rollback em caso de erro
    trap rollback ERR
    
    check_prerequisites
    create_backup
    enable_maintenance
    update_code
    install_dependencies
    run_migrations
    set_permissions
    optimize_application
    disable_maintenance
    test_api
    check_logs
    
    log "✅ Deploy concluído com sucesso!"
    log "📚 Documentação da API disponível em: http://seu-dominio.com/api/docs"
    log "🔗 Base URL da API: http://seu-dominio.com/api"
    
    info "Próximos passos:"
    info "1. Testar endpoints críticos manualmente"
    info "2. Verificar se app web continua funcionando"
    info "3. Monitorar logs por algumas horas"
    info "4. Atualizar documentação do app Android com nova URL"
}

# Verificar se está sendo executado como root ou com sudo
if [ "$EUID" -ne 0 ]; then
    warning "Script não está sendo executado como root"
    warning "Algumas operações podem falhar (permissões, serviços)"
    read -p "Continuar mesmo assim? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

# Executar função principal
main "$@"
