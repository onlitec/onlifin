#!/bin/bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILES=(
  "/opt/onlifin/.env"
  "${ROOT_DIR}/.env"
)
REQUIRED_KEYS=(
  "SMTP_HOST"
  "SMTP_USER"
  "SMTP_PASS"
  "SMTP_FROM_ADDRESS"
  "WHATSAPP_API_BASE_URL"
  "WHATSAPP_API_TOKEN"
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

read_env_status() {
  local env_file="$1"

  if [ ! -f "$env_file" ]; then
    warn "Arquivo ausente: $env_file"
    return 0
  fi

  info "Verificando $env_file"
  for key in "${REQUIRED_KEYS[@]}"; do
    local value
    value="$(grep -E "^${key}=" "$env_file" 2>/dev/null | tail -n 1 | cut -d '=' -f2- || true)"

    if [ -n "$value" ]; then
      echo "  - $key: set"
    else
      echo "  - $key: empty"
    fi
  done
}

if ! DOCKER_CMD="$(resolve_docker_cmd)"; then
  error "Docker indisponível para validar o deploy."
  exit 1
fi

info "Checagem de prontidão do sistema de notificações"
echo

for env_file in "${ENV_FILES[@]}"; do
  read_env_status "$env_file"
  echo
done

info "Ambiente atual do worker publicado"
$DOCKER_CMD inspect onlifin-notification-worker --format '{{range .Config.Env}}{{println .}}{{end}}' | \
  grep -E '^(SMTP_HOST|SMTP_USER|SMTP_PASS|SMTP_FROM_ADDRESS|WHATSAPP_API_BASE_URL|WHATSAPP_API_TOKEN)=' | \
  while IFS='=' read -r key value; do
    if [ -n "$value" ]; then
      echo "  - $key: set"
    else
      echo "  - $key: empty"
    fi
  done
echo

info "Health publicado"
health_payload="$(curl -fsS http://127.0.0.1:8081/api/worker/notification-health || true)"

if [ -n "$health_payload" ]; then
  echo "$health_payload"
  echo
  echo "$health_payload" | node -e '
    let input = "";
    process.stdin.on("data", (chunk) => input += chunk);
    process.stdin.on("end", () => {
      try {
        const health = JSON.parse(input);
        console.log(`  - smtpConfigured: ${health.smtpConfigured} (${health.smtpCredentialSource || "unknown"})`);
        console.log(`  - whatsappConfigured: ${health.whatsappConfigured} (${health.whatsappCredentialSource || "unknown"})`);
        console.log(`  - whatsappSenderConfigured: ${Boolean(health.whatsappSenderConfigured)}`);
      } catch (error) {
        console.log("  - nao foi possivel interpretar o payload de health");
      }
    });
  '
else
  warn "Health do worker indisponivel nesta verificacao."
fi
echo
echo

warn "Campos em .env agora sao fallback do deploy. Se o health mostrar source=database, a configuracao da UI administrativa ja prevalece sobre o env."
