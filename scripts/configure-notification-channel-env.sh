#!/bin/bash
set -euo pipefail

ROOT_ENV_FILE="${ROOT_ENV_FILE:-/opt/onlifin/.env}"
REPO_ENV_FILE="${REPO_ENV_FILE:-/opt/onlifin/source-real/.env}"
COMPOSE_FILE="${COMPOSE_FILE:-/opt/onlifin/docker-compose.yml}"
CHECK_SCRIPT="${CHECK_SCRIPT:-/opt/onlifin/source-real/scripts/check-notification-deploy-readiness.sh}"

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
  - O script só altera chaves explicitamente fornecidas no ambiente atual.
  - Ele sincroniza /opt/onlifin/.env e /opt/onlifin/source-real/.env.
  - No final, reinicia apenas o serviço onlifin-notification-worker e roda o checker.
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

main() {
  if [ "${1:-}" = "--help" ] || [ "${1:-}" = "-h" ]; then
    usage
    exit 0
  fi

  local smtp_selected="false"
  local whatsapp_selected="false"
  local keys_to_apply=()
  local docker_cmd

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

  apply_keys_to_file "$ROOT_ENV_FILE" "${keys_to_apply[@]}"
  echo
  apply_keys_to_file "$REPO_ENV_FILE" "${keys_to_apply[@]}"
  echo

  info "Recriando o container onlifin-notification-worker com as novas variáveis"
  (
    cd /opt/onlifin
    $docker_cmd compose --env-file "$ROOT_ENV_FILE" -f "$COMPOSE_FILE" up -d onlifin-notification-worker
  )
  echo

  if [ -x "$CHECK_SCRIPT" ] || [ -f "$CHECK_SCRIPT" ]; then
    info "Executando checker final"
    bash "$CHECK_SCRIPT"
  else
    warn "Checker não encontrado em $CHECK_SCRIPT"
  fi
}

main "$@"
