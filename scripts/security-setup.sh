#!/bin/bash

# ========================================================================
# ONLIFIN - SCRIPT DE CONFIGURAÇÃO DE SEGURANÇA
# ========================================================================
# 
# Este script configura as principais medidas de segurança do Onlifin
# Execute apenas em ambiente de produção com cuidado
#
# ========================================================================

set -e

echo "🔒 Iniciando configuração de segurança do Onlifin..."

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

# Verificar se está em ambiente de produção
if [ "${APP_ENV:-local}" != "production" ]; then
    warn "Este script é recomendado apenas para produção"
    read -p "Deseja continuar? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

# 1. Verificar permissões de arquivos
log "Verificando permissões de arquivos..."
chmod 600 .env 2>/dev/null || warn "Arquivo .env não encontrado"
chmod 644 .env.example 2>/dev/null || warn "Arquivo .env.example não encontrado"
chmod 755 storage/
chmod 755 bootstrap/cache/

# 2. Configurar permissões do storage
log "Configurando permissões do storage..."
chown -R www-data:www-data storage/ 2>/dev/null || chown -R $(whoami):$(whoami) storage/
chmod -R 755 storage/

# 3. Limpar cache de configuração
log "Limpando cache de configuração..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# 4. Gerar chave da aplicação se não existir
if [ -z "$(grep 'APP_KEY=' .env | cut -d '=' -f2)" ]; then
    log "Gerando chave da aplicação..."
    php artisan key:generate
else
    log "Chave da aplicação já configurada"
fi

# 5. Verificar configurações de banco de dados
log "Verificando configurações de banco de dados..."
if ! php artisan migrate:status > /dev/null 2>&1; then
    warn "Não foi possível conectar ao banco de dados"
    warn "Verifique as configurações em .env"
fi

# 6. Configurar sessões seguras
log "Configurando sessões seguras..."
php artisan session:table 2>/dev/null || log "Tabela de sessões já existe"
php artisan migrate --force 2>/dev/null || warn "Erro ao executar migrações"

# 7. Configurar cache Redis
log "Verificando configuração do Redis..."
if command -v redis-cli > /dev/null 2>&1; then
    if redis-cli ping > /dev/null 2>&1; then
        log "Redis está funcionando"
    else
        warn "Redis não está respondendo"
    fi
else
    warn "Redis não está instalado"
fi

# 8. Verificar SSL/HTTPS
log "Verificando configuração SSL..."
if [ "${APP_URL:-}" = "https://"* ]; then
    log "HTTPS configurado corretamente"
else
    warn "APP_URL não está configurado para HTTPS"
fi

# 9. Configurar rate limiting
log "Configurando rate limiting..."
php artisan config:cache

# 10. Verificar logs de segurança
log "Verificando logs de segurança..."
mkdir -p storage/logs
chmod 755 storage/logs

# 11. Configurar backup automático
log "Configurando backup automático..."
if [ ! -f "backup.sh" ]; then
    cat > backup.sh << 'EOF'
#!/bin/bash
# Backup automático do Onlifin
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="backups"
mkdir -p $BACKUP_DIR

# Backup do banco de dados
php artisan backup:run --only-db

# Backup dos arquivos
tar -czf $BACKUP_DIR/onlifin_files_$DATE.tar.gz storage/app/public

# Manter apenas os últimos 7 backups
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete

echo "Backup concluído: $DATE"
EOF
    chmod +x backup.sh
    log "Script de backup criado"
fi

# 12. Configurar monitoramento
log "Configurando monitoramento..."
if [ ! -f "monitor.sh" ]; then
    cat > monitor.sh << 'EOF'
#!/bin/bash
# Monitoramento básico do Onlifin

# Verificar se a aplicação está respondendo
if ! curl -f http://localhost/health > /dev/null 2>&1; then
    echo "ALERTA: Aplicação não está respondendo"
    # Aqui você pode adicionar notificações (email, Slack, etc.)
fi

# Verificar uso de disco
DISK_USAGE=$(df / | awk 'NR==2 {print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 80 ]; then
    echo "ALERTA: Uso de disco acima de 80%"
fi

# Verificar memória
MEMORY_USAGE=$(free | awk 'NR==2{printf "%.0f", $3*100/$2}')
if [ $MEMORY_USAGE -gt 80 ]; then
    echo "ALERTA: Uso de memória acima de 80%"
fi
EOF
    chmod +x monitor.sh
    log "Script de monitoramento criado"
fi

# 13. Configurar firewall básico (se disponível)
log "Configurando firewall básico..."
if command -v ufw > /dev/null 2>&1; then
    ufw --force enable
    ufw allow 22/tcp   # SSH
    ufw allow 80/tcp   # HTTP
    ufw allow 443/tcp  # HTTPS
    ufw deny 3306/tcp  # MySQL (apenas local)
    ufw deny 6379/tcp  # Redis (apenas local)
    log "Firewall configurado"
else
    warn "UFW não está disponível"
fi

# 14. Verificar configurações de segurança
log "Verificando configurações de segurança..."
php artisan config:show | grep -E "(SESSION_|COOKIE_|SECURE)" || warn "Configurações de segurança não encontradas"

# 15. Testar configurações
log "Testando configurações..."
if php artisan route:list > /dev/null 2>&1; then
    log "Rotas carregadas corretamente"
else
    error "Erro ao carregar rotas"
fi

# 16. Configurar cron jobs
log "Configurando cron jobs..."
(crontab -l 2>/dev/null; echo "0 2 * * * $(pwd)/backup.sh") | crontab - 2>/dev/null || warn "Erro ao configurar cron job"
(crontab -l 2>/dev/null; echo "*/5 * * * * $(pwd)/monitor.sh") | crontab - 2>/dev/null || warn "Erro ao configurar cron job"

# 17. Finalizar
log "Configuração de segurança concluída!"
log "Próximos passos:"
log "1. Verifique o arquivo .env"
log "2. Configure SSL/HTTPS"
log "3. Teste todas as funcionalidades"
log "4. Configure monitoramento externo"
log "5. Faça backup das configurações"

echo
echo "🔒 Configuração de segurança do Onlifin concluída com sucesso!"
echo "📋 Verifique o arquivo SECURITY_CONFIG.md para mais detalhes"
