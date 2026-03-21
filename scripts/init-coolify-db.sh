#!/bin/bash
# ===========================================
# Onlifin - Script de Inicialização do Banco
# ===========================================
# Use este script após o primeiro deploy no Coolify
# para inicializar o banco de dados
# ===========================================

set -e

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo ""
echo -e "${YELLOW}🗄️ Onlifin - Inicialização do Banco de Dados${NC}"
echo "================================================"
echo ""

# Verificar se estamos dentro do container ou fora
if [ -f /.dockerenv ]; then
    echo -e "${GREEN}✓ Executando dentro do container${NC}"
    DB_HOST="localhost"
    CONTAINER_MODE=true
else
    echo -e "${YELLOW}→ Executando fora do container${NC}"
    DB_HOST="onlifin-db"
    CONTAINER_MODE=false
fi

# Configurações
DB_USER="${POSTGRES_USER:-onlifin}"
DB_NAME="${POSTGRES_DB:-onlifin}"
DB_PASSWORD="${POSTGRES_PASSWORD:-onlifin123}"

echo "Banco: $DB_NAME"
echo "Usuário: $DB_USER"
echo ""

# Função para executar SQL
run_sql() {
    local file=$1
    local description=$2
    
    echo -e "${YELLOW}→ $description${NC}"
    
    if [ "$CONTAINER_MODE" = true ]; then
        PGPASSWORD="$DB_PASSWORD" psql -h localhost -U "$DB_USER" -d "$DB_NAME" -f "$file"
    else
        docker exec -i onlifin-db psql -U "$DB_USER" -d "$DB_NAME" < "$file"
    fi
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ $description - Concluído${NC}"
    else
        echo -e "${RED}✗ Erro em: $description${NC}"
        exit 1
    fi
    echo ""
}

# Verificar conexão com o banco
echo -e "${YELLOW}→ Verificando conexão com o banco...${NC}"

if [ "$CONTAINER_MODE" = true ]; then
    PGPASSWORD="$DB_PASSWORD" psql -h localhost -U "$DB_USER" -d "$DB_NAME" -c "SELECT 1" > /dev/null 2>&1
else
    docker exec onlifin-db psql -U "$DB_USER" -d "$DB_NAME" -c "SELECT 1" > /dev/null 2>&1
fi

if [ $? -ne 0 ]; then
    echo -e "${RED}✗ Não foi possível conectar ao banco de dados${NC}"
    echo "Verifique se o container db está rodando:"
    echo "  docker ps | grep onlifin-db"
    exit 1
fi

echo -e "${GREEN}✓ Conexão estabelecida${NC}"
echo ""

# Diretório dos scripts SQL
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Se os arquivos estiverem no diretório docker/init-db
if [ -d "$SCRIPT_DIR/../docker/init-db" ]; then
    SQL_DIR="$SCRIPT_DIR/../docker/init-db"
elif [ -d "/docker-entrypoint-initdb.d" ]; then
    SQL_DIR="/docker-entrypoint-initdb.d"
else
    SQL_DIR="$SCRIPT_DIR"
fi

# Executar scripts de inicialização
if [ -f "$SQL_DIR/01-auth-schema.sql" ]; then
    run_sql "$SQL_DIR/01-auth-schema.sql" "Criando schema de autenticação"
fi

if [ -f "$SQL_DIR/02-main-schema.sql" ]; then
    run_sql "$SQL_DIR/02-main-schema.sql" "Criando schema principal"
fi

if [ -f "$SQL_DIR/03-seed-data.sql" ]; then
    run_sql "$SQL_DIR/03-seed-data.sql" "Inserindo dados iniciais"
fi

if [ -f "$SCRIPT_DIR/../migrations/20260321_notification_management_system.sql" ]; then
    run_sql "$SCRIPT_DIR/../migrations/20260321_notification_management_system.sql" "Aplicando sistema de notificações"
fi

if [ -f "$SCRIPT_DIR/../migrations/20260321_notification_queue_admin_actions.sql" ]; then
    run_sql "$SCRIPT_DIR/../migrations/20260321_notification_queue_admin_actions.sql" "Aplicando ações administrativas da fila de notificações"
fi

if [ -f "$SCRIPT_DIR/../migrations/20260321_notification_worker_commands.sql" ]; then
    run_sql "$SCRIPT_DIR/../migrations/20260321_notification_worker_commands.sql" "Aplicando comandos manuais do notification worker"
fi

if [ -f "$SCRIPT_DIR/../migrations/20260321_notification_admin_read_access.sql" ]; then
    run_sql "$SCRIPT_DIR/../migrations/20260321_notification_admin_read_access.sql" "Aplicando leitura administrativa de notificações"
fi

if [ -f "$SCRIPT_DIR/../migrations/20260321_fix_current_app_role_claims.sql" ]; then
    run_sql "$SCRIPT_DIR/../migrations/20260321_fix_current_app_role_claims.sql" "Corrigindo leitura de papel admin nas claims JWT"
fi

echo ""
echo -e "${GREEN}✅ Banco de dados inicializado com sucesso!${NC}"
echo ""
echo "Próximos passos:"
echo "  1. Baixe o modelo do Ollama:"
echo "     docker exec -it onlifin-ollama ollama pull qwen2.5:0.5b"
echo ""
echo "  2. Acesse a aplicação:"
echo "     http://localhost (ou seu domínio)"
echo ""
