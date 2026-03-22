#!/bin/bash
set -euo pipefail

ROOT_ENV_FILE="${ROOT_ENV_FILE:-/opt/onlifin/.env}"
REPO_ENV_FILE="${REPO_ENV_FILE:-/opt/onlifin/source-real/.env}"
COMPOSE_FILE="${COMPOSE_FILE:-/opt/onlifin/docker-compose.yml}"
CHECK_SCRIPT="${CHECK_SCRIPT:-/opt/onlifin/source-real/scripts/check-notification-deploy-readiness.sh}"
DB_USER="${DB_USER:-onlifin}"
DB_NAME="${DB_NAME:-onlifin}"
APPLY_ENV_FALLBACK="${APPLY_ENV_FALLBACK:-false}"

SMTP_KEYS=(
  "SMTP_HOST"
  "SMTP_PORT"
  "SMTP_SECURE"
  "SMTP_USER"
  "SMTP_PASS"
  "SMTP_FROM_NAME"
  "SMTP_FROM_ADDRESS"
)
WHATSAPP_KEYS=(
  "WHATSAPP_API_BASE_URL"
  "WHATSAPP_API_TOKEN"
  "WHATSAPP_PROVIDER"
  "WHATSAPP_SENDER"
)

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

info() {
  echo -e "${GREEN}[INFO]${NC} $1"
}

warn() {
  echo -e "${YELLOW}[WARN]${NC} $1"
}

error() {
  echo -e "${RED}[ERROR]${NC} $1"
}

usage() {
  cat <<'EOF'
Uso:
  Exporte as variáveis desejadas e execute o script.

Exemplo SMTP:
  export SMTP_HOST="smtp.example.com"
  export SMTP_PORT="587"
  export SMTP_SECURE="false"
  export SMTP_USER="mailer@example.com"
  export SMTP_PASS="segredo"
  export SMTP_FROM_NAME="OnliFin"
  export SMTP_FROM_ADDRESS="financeiro@example.com"
  /opt/onlifin/source-real/scripts/configure-notification-channel-env.sh

Exemplo WhatsApp:
  export WHATSAPP_API_BASE_URL="https://provider.example.com"
  export WHATSAPP_API_TOKEN="segredo"
  export WHATSAPP_PROVIDER="generic"
  /opt/onlifin/source-real/scripts/configure-notification-channel-env.sh

Observações:
  - O script salva por padrão as credenciais globais no banco (`notification_channel_credentials`).
  - `SMTP_FROM_NAME` e `SMTP_FROM_ADDRESS` são gravados em `notification_settings`.
  - Se `APPLY_ENV_FALLBACK=true`, ele também sincroniza os .env e recria o worker.
  - O script só altera chaves explicitamente fornecidas no ambiente atual.
EOF
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

resolve_db_container() {
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

sql_escape() {
  printf "%s" "$1" | sed "s/'/''/g"
}

has_any_key() {
  local key
  for key in "$@"; do
    if [ -n "${!key:-}" ]; then
      return 0
    fi
  done
  return 1
}

ensure_file_exists() {
  local file="$1"
  local dir

  dir="$(dirname "$file")"
  mkdir -p "$dir"
  touch "$file"
}

upsert_env_key() {
  local file="$1"
  local key="$2"
  local value="$3"
  local tmp_file

  tmp_file="$(mktemp)"
  awk -v key="$key" -v value="$value" '
    BEGIN { replaced = 0 }
    index($0, key "=") == 1 {
      print key "=" value
      replaced = 1
      next
    }
    { print }
    END {
      if (!replaced) {
        print key "=" value
      }
    }
  ' "$file" > "$tmp_file"
  mv "$tmp_file" "$file"
}

validate_selection() {
  local smtp_selected="$1"
  local whatsapp_selected="$2"

  if [ "$smtp_selected" = "false" ] && [ "$whatsapp_selected" = "false" ]; then
    error "Nenhuma variável de notificação foi fornecida."
    echo
    usage
    exit 1
  fi

  if [ "$smtp_selected" = "true" ]; then
    local missing=()

    [ -n "${SMTP_HOST:-}" ] || missing+=("SMTP_HOST")
    [ -n "${SMTP_FROM_ADDRESS:-}" ] || missing+=("SMTP_FROM_ADDRESS")

    if [ -n "${SMTP_USER:-}" ] && [ -z "${SMTP_PASS:-}" ]; then
      missing+=("SMTP_PASS")
    fi

    if [ -z "${SMTP_USER:-}" ] && [ -n "${SMTP_PASS:-}" ]; then
      missing+=("SMTP_USER")
    fi

    if [ "${#missing[@]}" -gt 0 ]; then
      error "Configuração SMTP incompleta: ${missing[*]}"
      exit 1
    fi
  fi

  if [ "$whatsapp_selected" = "true" ] && [ -z "${WHATSAPP_API_BASE_URL:-}" ]; then
    error "Configuração WhatsApp incompleta: WHATSAPP_API_BASE_URL"
    exit 1
  fi
}

apply_keys_to_file() {
  local file="$1"
  shift
  local key

  ensure_file_exists "$file"
  info "Atualizando $file"

  for key in "$@"; do
    if [ -n "${!key:-}" ]; then
      upsert_env_key "$file" "$key" "${!key}"
      echo "  - $key: set"
    fi
  done
}

apply_database_settings() {
  local docker_cmd="$1"
  local db_container="$2"

  info "Salvando credenciais globais no banco"

  $docker_cmd exec -i "$db_container" psql -v ON_ERROR_STOP=1 -U "$DB_USER" -d "$DB_NAME" <<SQL >/dev/null
INSERT INTO public.notification_channel_credentials (credentials_key)
VALUES ('global')
ON CONFLICT (credentials_key) DO NOTHING;

UPDATE public.notification_channel_credentials
SET smtp_host = COALESCE($( [ -n "${SMTP_HOST:-}" ] && printf "'%s'" "$(sql_escape "$SMTP_HOST")" || printf "NULL" ), smtp_host),
    smtp_port = COALESCE($( [ -n "${SMTP_PORT:-}" ] && printf "%s" "$SMTP_PORT" || printf "NULL" ), smtp_port),
    smtp_secure = COALESCE($( [ -n "${SMTP_SECURE:-}" ] && { [ "$SMTP_SECURE" = "true" ] && printf "true" || printf "false"; } || printf "NULL" ), smtp_secure),
    smtp_user = COALESCE($( [ -n "${SMTP_USER:-}" ] && printf "'%s'" "$(sql_escape "$SMTP_USER")" || printf "NULL" ), smtp_user),
    smtp_pass = COALESCE($( [ -n "${SMTP_PASS:-}" ] && printf "'%s'" "$(sql_escape "$SMTP_PASS")" || printf "NULL" ), smtp_pass),
    whatsapp_provider = COALESCE($( [ -n "${WHATSAPP_PROVIDER:-}" ] && printf "'%s'" "$(sql_escape "$WHATSAPP_PROVIDER")" || printf "NULL" ), whatsapp_provider),
    whatsapp_api_base_url = COALESCE($( [ -n "${WHATSAPP_API_BASE_URL:-}" ] && printf "'%s'" "$(sql_escape "$WHATSAPP_API_BASE_URL")" || printf "NULL" ), whatsapp_api_base_url),
    whatsapp_api_token = COALESCE($( [ -n "${WHATSAPP_API_TOKEN:-}" ] && printf "'%s'" "$(sql_escape "$WHATSAPP_API_TOKEN")" || printf "NULL" ), whatsapp_api_token),
    whatsapp_sender = COALESCE($( [ -n "${WHATSAPP_SENDER:-}" ] && printf "'%s'" "$(sql_escape "$WHATSAPP_SENDER")" || printf "NULL" ), whatsapp_sender),
    updated_at = now()
WHERE credentials_key = 'global';

UPDATE public.notification_settings
SET email_from_name = COALESCE($( [ -n "${SMTP_FROM_NAME:-}" ] && printf "'%s'" "$(sql_escape "$SMTP_FROM_NAME")" || printf "NULL" ), email_from_name),
    email_from_address = COALESCE($( [ -n "${SMTP_FROM_ADDRESS:-}" ] && printf "'%s'" "$(sql_escape "$SMTP_FROM_ADDRESS")" || printf "NULL" ), email_from_address),
    updated_at = now()
WHERE settings_key = 'global';

NOTIFY pgrst, 'reload schema';
SQL

  echo "  - banco: credenciais globais atualizadas"
}

main() {
  if [ "${1:-}" = "--help" ] || [ "${1:-}" = "-h" ]; then
    usage
    exit 0
  fi

  local smtp_selected="false"
  local whatsapp_selected="false"
  local keys_to_apply=()
  local docker_cmd
  local db_container

  if has_any_key "${SMTP_KEYS[@]}"; then
    smtp_selected="true"
    keys_to_apply+=("${SMTP_KEYS[@]}")
  fi

  if has_any_key "${WHATSAPP_KEYS[@]}"; then
    whatsapp_selected="true"
    keys_to_apply+=("${WHATSAPP_KEYS[@]}")
  fi

  validate_selection "$smtp_selected" "$whatsapp_selected"

  if ! docker_cmd="$(resolve_docker_cmd)"; then
    error "Docker indisponível para reiniciar o worker."
    exit 1
  fi

  if ! db_container="$(resolve_db_container "$docker_cmd")"; then
    error "Container do banco nao encontrado."
    exit 1
  fi

  apply_database_settings "$docker_cmd" "$db_container"
  echo

  if [ "$APPLY_ENV_FALLBACK" = "true" ]; then
    apply_keys_to_file "$ROOT_ENV_FILE" "${keys_to_apply[@]}"
    echo
    apply_keys_to_file "$REPO_ENV_FILE" "${keys_to_apply[@]}"
    echo

    info "Recriando o container onlifin-notification-worker com as novas variáveis de fallback"
    (
      cd /opt/onlifin
      $docker_cmd compose --env-file "$ROOT_ENV_FILE" -f "$COMPOSE_FILE" up -d onlifin-notification-worker
    )
    echo
  else
    info "Modo banco aplicado; o worker passa a ler essas credenciais sem exigir restart."
    echo
  fi

  if [ -x "$CHECK_SCRIPT" ] || [ -f "$CHECK_SCRIPT" ]; then
    info "Executando checker final"
    bash "$CHECK_SCRIPT"
  else
    warn "Checker não encontrado em $CHECK_SCRIPT"
  fi
}

main "$@"
