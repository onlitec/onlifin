#!/bin/bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
MIGRATION_FILES=(
    "${PROJECT_ROOT}/migrations/20260321_notification_management_system.sql"
    "${PROJECT_ROOT}/migrations/20260321_notification_queue_admin_actions.sql"
    "${PROJECT_ROOT}/migrations/20260321_notification_worker_commands.sql"
    "${PROJECT_ROOT}/migrations/20260321_notification_admin_read_access.sql"
    "${PROJECT_ROOT}/migrations/20260321_fix_current_app_role_claims.sql"
)
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

for migration_file in "${MIGRATION_FILES[@]}"; do
    if [ ! -f "$migration_file" ]; then
        log_error "Migration nao encontrada: $migration_file"
        exit 1
    fi
done

if ! DOCKER_CMD="$(resolve_docker_cmd)"; then
    log_error "Nao foi possivel acessar o Docker."
    exit 1
fi

if ! DB_CONTAINER="$(resolve_container_name "$DOCKER_CMD")"; then
    log_error "Container do banco nao encontrado. Esperado: onlifin-db ou onlifin-database."
    exit 1
fi

log_info "Aplicando pacote de migrations de notificacao"
log_info "Container: $DB_CONTAINER"

for migration_file in "${MIGRATION_FILES[@]}"; do
    log_info "Aplicando $(basename "$migration_file")"
    cat "$migration_file" | $DOCKER_CMD exec -i "$DB_CONTAINER" psql -v ON_ERROR_STOP=1 -U "$DB_USER" -d "$DB_NAME"
done

log_info "Recarregando schema do PostgREST"
$DOCKER_CMD exec "$DB_CONTAINER" psql -U "$DB_USER" -d "$DB_NAME" -c "NOTIFY pgrst, 'reload schema';" >/dev/null

log_info "Validacao rapida"
$DOCKER_CMD exec "$DB_CONTAINER" psql -U "$DB_USER" -d "$DB_NAME" -c "
SELECT
  to_regclass('public.notification_settings') IS NOT NULL AS has_notification_settings,
  to_regclass('public.notification_templates') IS NOT NULL AS has_notification_templates,
  to_regclass('public.notification_delivery_queue') IS NOT NULL AS has_notification_delivery_queue,
  to_regclass('public.notification_deliveries') IS NOT NULL AS has_notification_deliveries,
  to_regclass('public.notification_worker_commands') IS NOT NULL AS has_notification_worker_commands;
"

log_warn "Proximo passo: redeployar app/frontend, worker e servicos relacionados antes da validacao fim a fim."
