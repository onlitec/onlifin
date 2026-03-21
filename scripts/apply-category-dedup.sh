#!/bin/bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
MIGRATION_FILES=(
    "${PROJECT_ROOT}/migrations/20260321_deduplicate_categories.sql"
    "${PROJECT_ROOT}/migrations/20260321_remove_category_visibility_overlaps.sql"
)
DB_USER="${DB_USER:-onlifin}"
DB_NAME="${DB_NAME:-onlifin}"

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

if ! DOCKER_CMD="$(resolve_docker_cmd)"; then
    echo "[ERROR] Docker indisponivel"
    exit 1
fi

if ! DB_CONTAINER="$(resolve_container_name "$DOCKER_CMD")"; then
    echo "[ERROR] Container do banco nao encontrado"
    exit 1
fi

for migration_file in "${MIGRATION_FILES[@]}"; do
    echo "[INFO] Aplicando $(basename "$migration_file")"
    cat "$migration_file" | $DOCKER_CMD exec -i "$DB_CONTAINER" psql -v ON_ERROR_STOP=1 -U "$DB_USER" -d "$DB_NAME"
done

$DOCKER_CMD exec "$DB_CONTAINER" psql -U "$DB_USER" -d "$DB_NAME" -c "NOTIFY pgrst, 'reload schema';" >/dev/null

echo "[INFO] Duplicidades por escopo:"
$DOCKER_CMD exec "$DB_CONTAINER" psql -U "$DB_USER" -d "$DB_NAME" -c "
SELECT type, name, count(*) AS total
FROM public.categories
GROUP BY type, name, user_id, company_id, person_id, tenant_id, is_system
HAVING count(*) > 1
ORDER BY type, name;
"

echo "[INFO] Duplicidades visiveis por nome/tipo:"
$DOCKER_CMD exec "$DB_CONTAINER" psql -U "$DB_USER" -d "$DB_NAME" -c "
SELECT type, name, count(*) AS total
FROM public.categories
GROUP BY type, name
HAVING count(*) > 1
ORDER BY type, name;
"
