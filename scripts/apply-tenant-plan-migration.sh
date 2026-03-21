#!/bin/bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
MIGRATION_FILE="${PROJECT_ROOT}/migrations/20260321_add_plan_columns_to_tenants.sql"
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

if [ ! -f "$MIGRATION_FILE" ]; then
    log_error "Migration nao encontrada: $MIGRATION_FILE"
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

log_info "Aplicando migration de planos em tenants"
log_info "Arquivo: $MIGRATION_FILE"
log_info "Container: $DB_CONTAINER"

cat "$MIGRATION_FILE" | $DOCKER_CMD exec -i "$DB_CONTAINER" psql -v ON_ERROR_STOP=1 -U "$DB_USER" -d "$DB_NAME"

log_info "Migration aplicada com sucesso"
log_info "Recarregando schema do PostgREST"
$DOCKER_CMD exec "$DB_CONTAINER" psql -U "$DB_USER" -d "$DB_NAME" -c "NOTIFY pgrst, 'reload schema';" >/dev/null

log_info "Validacao rapida"
$DOCKER_CMD exec "$DB_CONTAINER" psql -U "$DB_USER" -d "$DB_NAME" -c "
DO \$\$
DECLARE
    has_tenants boolean;
    normalized_rows integer;
BEGIN
    SELECT to_regclass('public.tenants') IS NOT NULL INTO has_tenants;

    IF NOT has_tenants THEN
        RAISE NOTICE 'Tabela public.tenants nao encontrada apos migration.';
        RETURN;
    END IF;

    SELECT COUNT(*)
    INTO normalized_rows
    FROM public.tenants
    WHERE COALESCE(plan_code, '') IN ('basic', 'medium', 'full');

    RAISE NOTICE 'has_plan_code=%, has_plan=%, normalized_rows=%',
        EXISTS (
            SELECT 1
            FROM information_schema.columns
            WHERE table_schema = 'public'
              AND table_name = 'tenants'
              AND column_name = 'plan_code'
        ),
        EXISTS (
            SELECT 1
            FROM information_schema.columns
            WHERE table_schema = 'public'
              AND table_name = 'tenants'
              AND column_name = 'plan'
        ),
        normalized_rows;
END
\$\$;
"

log_warn "Se existirem tenants antigos sem plano no perfil, rode tambem ./scripts/apply-plan-backfill.sh"
