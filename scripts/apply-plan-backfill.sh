#!/bin/bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SQL_FILE="${SCRIPT_DIR}/backfill-profile-plan-codes.sql"
DB_USER="${DB_USER:-onlifin}"
DB_NAME="${DB_NAME:-onlifin}"

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

resolve_docker_cmd() {
    if docker ps >/dev/null 2>&1; then
        echo "docker"
        return 0
    fi

    if command -v sudo >/dev/null 2>&1 && sudo docker ps >/dev/null 2>&1; then
        echo "sudo docker"
        return 0
    fi

    return 1
}

resolve_container_name() {
    local docker_cmd="$1"

    if $docker_cmd inspect onlifin-db >/dev/null 2>&1; then
        echo "onlifin-db"
        return 0
    fi

    if $docker_cmd inspect onlifin-database >/dev/null 2>&1; then
        echo "onlifin-database"
        return 0
    fi

    return 1
}

if [ ! -f "$SQL_FILE" ]; then
    log_error "Arquivo SQL nao encontrado: $SQL_FILE"
    exit 1
fi

if ! DOCKER_CMD="$(resolve_docker_cmd)"; then
    log_error "Nao foi possivel acessar o Docker."
    exit 1
fi

if ! DB_CONTAINER="$(resolve_container_name "$DOCKER_CMD")"; then
    log_error "Container do banco nao encontrado. Esperado: onlifin-db ou onlifin-database."
    exit 1
fi

log_info "Sincronizando plan_code dos perfis com base nos tenants"
log_info "Arquivo: $SQL_FILE"
log_info "Container: $DB_CONTAINER"

cat "$SQL_FILE" | $DOCKER_CMD exec -i "$DB_CONTAINER" psql -v ON_ERROR_STOP=1 -U "$DB_USER" -d "$DB_NAME"

log_info "Sincronizacao concluida"
log_warn "Se houver cache de sessao aberto no navegador, faca logout/login para refletir o plano atualizado."
