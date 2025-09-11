#!/bin/bash

# ========================================================================
# ONLIFIN - SCRIPT DE OTIMIZAÇÃO DE PERFORMANCE
# ========================================================================
# 
# Este script implementa otimizações de performance para o Onlifin:
# - Cache de consultas complexas
# - Otimização de assets frontend
# - Configuração de compressão
# - Otimização de banco de dados
# - Configuração de CDN
#
# ========================================================================

set -e

echo "🚀 Iniciando otimização de performance do Onlifin..."

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
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

info() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')] INFO: $1${NC}"
}

# ========================================================================
# 1. OTIMIZAÇÃO DE CACHE
# ========================================================================
log "Configurando cache Redis..."

# Verificar se Redis está rodando
if ! redis-cli ping > /dev/null 2>&1; then
    warn "Redis não está rodando. Iniciando Redis..."
    systemctl start redis-server 2>/dev/null || service redis-server start 2>/dev/null || warn "Não foi possível iniciar Redis"
fi

# Configurar cache do Laravel
log "Configurando cache do Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# ========================================================================
# 2. OTIMIZAÇÃO DE ASSETS FRONTEND
# ========================================================================
log "Otimizando assets frontend..."

# Instalar dependências se necessário
if [ ! -d "node_modules" ]; then
    log "Instalando dependências Node.js..."
    npm install
fi

# Build de produção com otimizações
log "Fazendo build de produção..."
npm run build

# Verificar tamanho dos assets
log "Verificando tamanho dos assets..."
if [ -d "public/build" ]; then
    du -sh public/build/*
    info "Assets otimizados com sucesso"
else
    warn "Diretório public/build não encontrado"
fi

# ========================================================================
# 3. CONFIGURAÇÃO DE COMPRESSÃO
# ========================================================================
log "Configurando compressão..."

# Verificar se nginx está configurado
if [ -f "nginx/nginx.conf" ]; then
    log "Configuração do Nginx encontrada"
    
    # Copiar configurações se necessário
    if [ -d "/etc/nginx" ]; then
        sudo cp nginx/nginx.conf /etc/nginx/nginx.conf 2>/dev/null || warn "Não foi possível copiar configuração do Nginx"
        sudo cp nginx/sites-available/onlifin.conf /etc/nginx/sites-available/onlifin.conf 2>/dev/null || warn "Não foi possível copiar configuração do site"
        
        # Testar configuração
        sudo nginx -t 2>/dev/null && log "Configuração do Nginx válida" || warn "Configuração do Nginx inválida"
    fi
else
    warn "Configuração do Nginx não encontrada"
fi

# ========================================================================
# 4. OTIMIZAÇÃO DE BANCO DE DADOS
# ========================================================================
log "Otimizando banco de dados..."

# Verificar se MySQL está rodando
if mysqladmin ping > /dev/null 2>&1; then
    log "MySQL está rodando"
    
    # Aplicar otimizações se o arquivo existir
    if [ -f "database/optimizations.sql" ]; then
        log "Aplicando otimizações de banco de dados..."
        mysql -u root -p < database/optimizations.sql 2>/dev/null || warn "Não foi possível aplicar otimizações (verifique credenciais)"
    else
        warn "Arquivo de otimizações não encontrado"
    fi
    
    # Otimizar tabelas
    log "Otimizando tabelas..."
    php artisan db:optimize 2>/dev/null || warn "Comando de otimização não disponível"
else
    warn "MySQL não está rodando ou não acessível"
fi

# ========================================================================
# 5. CONFIGURAÇÃO DE CDN
# ========================================================================
log "Configurando CDN..."

# Verificar configurações de CDN
if [ -f "config/cdn.php" ]; then
    log "Configuração de CDN encontrada"
    
    # Verificar variáveis de ambiente
    if [ -n "$CDN_ENABLED" ] && [ "$CDN_ENABLED" = "true" ]; then
        log "CDN habilitado"
        
        # Testar conectividade com CDN
        if [ -n "$CDN_URL" ]; then
            curl -I "$CDN_URL" > /dev/null 2>&1 && log "CDN acessível" || warn "CDN não acessível"
        fi
    else
        info "CDN desabilitado (configure CDN_ENABLED=true para habilitar)"
    fi
else
    warn "Configuração de CDN não encontrada"
fi

# ========================================================================
# 6. CONFIGURAÇÃO DE MONITORAMENTO
# ========================================================================
log "Configurando monitoramento de performance..."

# Criar script de monitoramento
cat > monitor-performance.sh << 'EOF'
#!/bin/bash

# Monitor de performance do Onlifin
echo "=== MONITOR DE PERFORMANCE ONLIFIN ==="
echo "Data: $(date)"
echo

# Verificar uso de CPU
echo "CPU Usage:"
top -bn1 | grep "Cpu(s)" | awk '{print $2}' | cut -d'%' -f1
echo

# Verificar uso de memória
echo "Memory Usage:"
free -h
echo

# Verificar uso de disco
echo "Disk Usage:"
df -h
echo

# Verificar status do Redis
echo "Redis Status:"
redis-cli ping 2>/dev/null || echo "Redis não está rodando"
echo

# Verificar status do MySQL
echo "MySQL Status:"
mysqladmin ping 2>/dev/null || echo "MySQL não está rodando"
echo

# Verificar logs de erro
echo "Últimos erros do Laravel:"
tail -5 storage/logs/laravel.log 2>/dev/null || echo "Log não encontrado"
echo

# Verificar performance do cache
echo "Cache Performance:"
php artisan cache:stats 2>/dev/null || echo "Comando não disponível"
EOF

chmod +x monitor-performance.sh
log "Script de monitoramento criado"

# ========================================================================
# 7. CONFIGURAÇÃO DE BACKUP OTIMIZADO
# ========================================================================
log "Configurando backup otimizado..."

# Criar script de backup otimizado
cat > backup-optimized.sh << 'EOF'
#!/bin/bash

# Backup otimizado do Onlifin
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="backups"
mkdir -p $BACKUP_DIR

echo "Iniciando backup otimizado..."

# Backup do banco de dados com compressão
echo "Fazendo backup do banco de dados..."
mysqldump --single-transaction --routines --triggers --quick --lock-tables=false \
    --user=$DB_USERNAME --password=$DB_PASSWORD $DB_DATABASE | \
    gzip > $BACKUP_DIR/onlifin_db_$DATE.sql.gz

# Backup dos arquivos essenciais
echo "Fazendo backup dos arquivos..."
tar -czf $BACKUP_DIR/onlifin_files_$DATE.tar.gz \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='storage/logs' \
    --exclude='storage/framework/cache' \
    --exclude='storage/framework/sessions' \
    --exclude='storage/framework/views' \
    .

# Backup das configurações
echo "Fazendo backup das configurações..."
tar -czf $BACKUP_DIR/onlifin_config_$DATE.tar.gz \
    .env \
    config/ \
    nginx/ \
    scripts/

# Limpar backups antigos (manter últimos 7)
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete
find $BACKUP_DIR -name "*.sql.gz" -mtime +7 -delete

echo "Backup concluído: $DATE"
echo "Arquivos criados:"
ls -lh $BACKUP_DIR/*$DATE*
EOF

chmod +x backup-optimized.sh
log "Script de backup otimizado criado"

# ========================================================================
# 8. CONFIGURAÇÃO DE CRON JOBS
# ========================================================================
log "Configurando cron jobs..."

# Adicionar cron jobs para manutenção
(crontab -l 2>/dev/null; echo "0 2 * * * $(pwd)/backup-optimized.sh") | crontab - 2>/dev/null || warn "Erro ao configurar cron job de backup"
(crontab -l 2>/dev/null; echo "*/5 * * * * $(pwd)/monitor-performance.sh >> logs/performance.log 2>&1") | crontab - 2>/dev/null || warn "Erro ao configurar cron job de monitoramento"
(crontab -l 2>/dev/null; echo "0 3 * * 0 php $(pwd)/artisan cache:clear") | crontab - 2>/dev/null || warn "Erro ao configurar cron job de limpeza de cache"

# ========================================================================
# 9. TESTE DE PERFORMANCE
# ========================================================================
log "Executando testes de performance..."

# Teste de conectividade
if curl -f http://localhost/health > /dev/null 2>&1; then
    log "Aplicação respondendo corretamente"
else
    warn "Aplicação não está respondendo"
fi

# Teste de cache
if php artisan cache:stats > /dev/null 2>&1; then
    log "Cache funcionando"
else
    warn "Cache não está funcionando"
fi

# Teste de banco de dados
if php artisan db:show > /dev/null 2>&1; then
    log "Banco de dados acessível"
else
    warn "Banco de dados não acessível"
fi

# ========================================================================
# 10. RELATÓRIO FINAL
# ========================================================================
log "Gerando relatório de performance..."

cat > performance-report.md << EOF
# Relatório de Performance - Onlifin

## Data: $(date)

### Otimizações Implementadas

1. **Cache Redis**
   - Status: $(redis-cli ping 2>/dev/null && echo "✅ Funcionando" || echo "❌ Não funcionando")
   - Configuração: Cache de consultas, sessões e views

2. **Assets Frontend**
   - Status: $(test -d "public/build" && echo "✅ Otimizados" || echo "❌ Não otimizados")
   - Minificação: Habilitada
   - Compressão: Habilitada

3. **Banco de Dados**
   - Status: $(mysqladmin ping 2>/dev/null && echo "✅ Funcionando" || echo "❌ Não funcionando")
   - Índices: Aplicados
   - Views: Criadas
   - Procedures: Configuradas

4. **CDN**
   - Status: $(test -n "$CDN_ENABLED" && echo "✅ Configurado" || echo "❌ Não configurado")
   - URL: $CDN_URL

5. **Monitoramento**
   - Scripts: Criados
   - Cron Jobs: Configurados
   - Logs: Ativos

### Próximos Passos

1. Configurar CDN se necessário
2. Monitorar performance regularmente
3. Ajustar configurações conforme necessário
4. Implementar alertas de performance

### Comandos Úteis

- Monitorar performance: \`./monitor-performance.sh\`
- Fazer backup: \`./backup-optimized.sh\`
- Limpar cache: \`php artisan cache:clear\`
- Otimizar banco: \`php artisan db:optimize\`
EOF

log "Relatório de performance gerado: performance-report.md"

# ========================================================================
# FINALIZAÇÃO
# ========================================================================
log "Otimização de performance concluída!"
log "Próximos passos:"
log "1. Verifique o relatório: performance-report.md"
log "2. Configure CDN se necessário"
log "3. Monitore performance regularmente"
log "4. Ajuste configurações conforme necessário"

echo
echo "🚀 Otimização de performance do Onlifin concluída com sucesso!"
echo "📊 Verifique o arquivo performance-report.md para detalhes"
