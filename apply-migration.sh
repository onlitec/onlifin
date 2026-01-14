#!/bin/bash
# ==============================================================================
# Script para aplicar migração em produção
# ==============================================================================

set -e

MIGRATION_FILE="${1:-migrations/002_fix_account_balance_system.sql}"
BACKUP_DIR="backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Verificar se o arquivo de migração existe
if [ ! -f "$MIGRATION_FILE" ]; then
    log_error "Arquivo de migração não encontrado: $MIGRATION_FILE"
    exit 1
fi

log_info "===================================================================="
log_info "APLICANDO MIGRAÇÃO EM PRODUÇÃO"
log_info "===================================================================="
log_info "Arquivo: $MIGRATION_FILE"
log_info "Data: $(date '+%Y-%m-%d %H:%M:%S')"
log_info "===================================================================="

# Criar diretório de backups se não existir
mkdir -p "$BACKUP_DIR"

# 1. Fazer backup do banco de dados
log_info "1. Fazendo backup do banco de dados..."
BACKUP_FILE="$BACKUP_DIR/backup_${TIMESTAMP}.sql"

docker exec onlifin-database pg_dump -U onlifin -d onlifin > "$BACKUP_FILE"

if [ $? -eq 0 ]; then
    log_info "✅ Backup criado: $BACKUP_FILE"
    log_info "   Tamanho: $(du -h "$BACKUP_FILE" | cut -f1)"
else
    log_error "❌ Falha ao criar backup!"
    exit 1
fi

# 2. Verificar conexão com o banco
log_info "2. Verificando conexão com o banco..."
docker exec onlifin-database psql -U onlifin -d onlifin -c "SELECT 1" > /dev/null 2>&1

if [ $? -eq 0 ]; then
    log_info "✅ Conexão OK"
else
    log_error "❌ Não foi possível conectar ao banco!"
    exit 1
fi

# 3. Aplicar migração
log_info "3. Aplicando migração..."
log_warn "   Aguarde... Isto pode levar alguns minutos"

cat "$MIGRATION_FILE" | docker exec -i onlifin-database psql -U onlifin -d onlifin

if [ $? -eq 0 ]; then
    log_info "✅ Migração aplicada com sucesso!"
else
    log_error "❌ Erro ao aplicar migração!"
    log_error "   Você pode restaurar o backup com:"
    log_error "   cat $BACKUP_FILE | docker exec -i onlifin-database psql -U onlifin -d onlifin"
    exit 1
fi

# 4. Verificar estrutura
log_info "4. Verificando estrutura do banco..."

# Verificar coluna initial_balance
INITIAL_BALANCE_EXISTS=$(docker exec onlifin-database psql -U onlifin -d onlifin -t -c "SELECT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'accounts' AND column_name = 'initial_balance');")

if [[ "$INITIAL_BALANCE_EXISTS" == *"t"* ]]; then
    log_info "✅ Coluna initial_balance criada"
else
    log_warn "⚠️  Coluna initial_balance não encontrada"
fi

# Verificar transaction_id em bills
BILLS_TRANSACTION_ID=$(docker exec onlifin-database psql -U onlifin -d onlifin -t -c "SELECT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'bills_to_pay' AND column_name = 'transaction_id');")

if [[ "$BILLS_TRANSACTION_ID" == *"t"* ]]; then
    log_info "✅ Coluna transaction_id criada em bills_to_pay"
else
    log_warn "⚠️  Coluna transaction_id não encontrada em bills_to_pay"
fi

# Contar triggers
TRIGGER_COUNT=$(docker exec onlifin-database psql -U onlifin -d onlifin -t -c "SELECT COUNT(*) FROM information_schema.triggers WHERE event_object_table IN ('accounts', 'transactions', 'bills_to_pay', 'bills_to_receive');")

log_info "✅ Triggers criados: $TRIGGER_COUNT"

# 5. Recarregar schema do PostgREST
log_info "5. Recarregando schema do PostgREST..."
docker exec onlifin-database psql -U onlifin -d onlifin -c "NOTIFY pgrst, 'reload schema';" > /dev/null 2>&1
log_info "✅ Schema recarregado"

# 6. Mostrar resumo
log_info "===================================================================="
log_info "RESUMO DA MIGRAÇÃO"
log_info "===================================================================="
log_info "Status: ✅ SUCESSO"
log_info "Backup: $BACKUP_FILE"
log_info "Triggers instalados: $TRIGGER_COUNT"
log_info "===================================================================="
log_info ""
log_info "📊 Próximos passos:"
log_info "   1. Teste criar uma transação de receita"
log_info "   2. Verifique se o saldo da conta aumentou"
log_info "   3. Teste criar uma despesa"
log_info "   4. Verifique se o saldo da conta diminuiu"
log_info "   5. Teste marcar uma conta a pagar como 'paga'"
log_info "   6. Verifique se o saldo foi debitado"
log_info ""
log_info "🔍 Para verificar saldos:"
log_info "   docker exec onlifin-database psql -U onlifin -d onlifin -c \"SELECT id, name, balance, initial_balance FROM accounts;\""
log_info ""
log_info "⚠️  Se houver problemas, restaure o backup:"
log_info "   cat $BACKUP_FILE | docker exec -i onlifin-database psql -U onlifin -d onlifin"
log_info "===================================================================="
