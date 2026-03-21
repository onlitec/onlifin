#!/bin/bash
set -euo pipefail

CHECK_SCRIPT="${CHECK_SCRIPT:-/opt/onlifin/source-real/scripts/check-notification-deploy-readiness.sh}"
CONFIGURE_SCRIPT="${CONFIGURE_SCRIPT:-/opt/onlifin/source-real/scripts/configure-notification-channel-env.sh}"
SMOKE_SCRIPT="${SMOKE_SCRIPT:-/opt/onlifin/source-real/scripts/run-notification-channel-smoke-test.sh}"

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
TEST_KEYS=(
  "TEST_EMAIL_DESTINATION"
  "TEST_WHATSAPP_DESTINATION"
)

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
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

section() {
  echo
  echo -e "${BLUE}== $1 ==${NC}"
}

usage() {
  cat <<'EOF'
Uso:
  /opt/onlifin/source-real/scripts/finalize-notification-system.sh

Fluxo:
  1. roda o checker de prontidão
  2. se encontrar SMTP_* e/ou WHATSAPP_* no ambiente atual, aplica no deploy
  3. roda o checker novamente
  4. se encontrar TEST_EMAIL_DESTINATION e/ou TEST_WHATSAPP_DESTINATION, executa o smoke test

Exemplo completo:
  export SMTP_HOST="smtp.example.com"
  export SMTP_PORT="587"
  export SMTP_SECURE="false"
  export SMTP_USER="mailer@example.com"
  export SMTP_PASS="segredo"
  export SMTP_FROM_NAME="OnliFin"
  export SMTP_FROM_ADDRESS="financeiro@example.com"
  export WHATSAPP_API_BASE_URL="https://provider.example.com"
  export WHATSAPP_API_TOKEN="segredo"
  export WHATSAPP_PROVIDER="generic"
  export TEST_EMAIL_DESTINATION="financeiro@example.com"
  export TEST_WHATSAPP_DESTINATION="+5511999999999"

  /opt/onlifin/source-real/scripts/finalize-notification-system.sh

Observações:
  - sem SMTP_*/WHATSAPP_* o script apenas diagnostica
  - sem TEST_* o script não roda o smoke test
  - os segredos nunca são impressos; só é indicado se a chave foi fornecida
EOF
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

ensure_executable() {
  local script="$1"
  if [ ! -f "$script" ]; then
    error "Script não encontrado: $script"
    exit 1
  fi
}

main() {
  if [ "${1:-}" = "--help" ] || [ "${1:-}" = "-h" ]; then
    usage
    exit 0
  fi

  ensure_executable "$CHECK_SCRIPT"
  ensure_executable "$CONFIGURE_SCRIPT"
  ensure_executable "$SMOKE_SCRIPT"

  section "Diagnóstico Inicial"
  bash "$CHECK_SCRIPT"

  if has_any_key "${SMTP_KEYS[@]}" "${WHATSAPP_KEYS[@]}"; then
    section "Aplicação de Credenciais"
    for key in "${SMTP_KEYS[@]}" "${WHATSAPP_KEYS[@]}"; do
      if [ -n "${!key:-}" ]; then
        echo "  - $key: set"
      fi
    done
    bash "$CONFIGURE_SCRIPT"
  else
    section "Aplicação de Credenciais"
    warn "Nenhuma variável SMTP_* ou WHATSAPP_* foi fornecida no ambiente atual. Pulando configuração."
  fi

  section "Diagnóstico Pós-Configuração"
  bash "$CHECK_SCRIPT"

  if has_any_key "${TEST_KEYS[@]}"; then
    section "Smoke Test"
    for key in "${TEST_KEYS[@]}"; do
      if [ -n "${!key:-}" ]; then
        echo "  - $key: set"
      fi
    done
    bash "$SMOKE_SCRIPT"
  else
    section "Smoke Test"
    warn "Nenhum destino TEST_* foi fornecido. Pulando smoke test."
  fi

  section "Resumo"
  info "Fluxo de finalização executado."
}

main "$@"
