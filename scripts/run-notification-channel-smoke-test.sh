#!/bin/bash
set -euo pipefail

DB_USER="${DB_USER:-onlifin}"
DB_NAME="${DB_NAME:-onlifin}"
HEALTH_URL="${HEALTH_URL:-http://127.0.0.1:8081/api/worker/notification-health}"
TIMEOUT_SECONDS="${TIMEOUT_SECONDS:-90}"
POLL_INTERVAL_SECONDS="${POLL_INTERVAL_SECONDS:-5}"
TEST_EMAIL_DESTINATION="${TEST_EMAIL_DESTINATION:-}"
TEST_WHATSAPP_DESTINATION="${TEST_WHATSAPP_DESTINATION:-}"

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
  Exporte ao menos um destino de teste e execute o script.

Exemplo:
  export TEST_EMAIL_DESTINATION="financeiro@example.com"
  export TEST_WHATSAPP_DESTINATION="+5511999999999"
  /opt/onlifin/source-real/scripts/run-notification-channel-smoke-test.sh

Variáveis opcionais:
  DB_USER
  DB_NAME
  HEALTH_URL
  TIMEOUT_SECONDS
  POLL_INTERVAL_SECONDS

Resultado:
  - cria uma notificação técnica de smoke test
  - enfileira entrega por e-mail e/ou WhatsApp
  - solicita processamento imediato ao worker
  - aguarda o status final e imprime o resumo
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

sql_escape() {
  printf "%s" "$1" | sed "s/'/''/g"
}

fetch_scalar() {
  local query="$1"
  $DOCKER_CMD exec onlifin-db psql -U "$DB_USER" -d "$DB_NAME" -t -A -c "$query" | tr -d '\r' | sed '/^$/d' | head -n 1
}

fetch_rows() {
  local query="$1"
  $DOCKER_CMD exec onlifin-db psql -U "$DB_USER" -d "$DB_NAME" -t -A -F '|' -c "$query" | tr -d '\r' | sed '/^$/d'
}

check_channel_readiness() {
  local health="$1"
  local channel="$2"

  case "$channel" in
    email)
      if ! printf "%s" "$health" | grep -q '"smtpConfigured":true'; then
        warn "SMTP ainda não está pronto no health publicado."
      fi
      ;;
    whatsapp)
      if ! printf "%s" "$health" | grep -q '"whatsappConfigured":true'; then
        warn "WhatsApp ainda não está pronto no health publicado."
      fi
      ;;
  esac
}

insert_queue_item() {
  local notification_id="$1"
  local user_id="$2"
  local channel="$3"
  local destination="$4"
  local subject="$5"
  local content="$6"
  local correlation_id="$7"

  fetch_scalar "
    INSERT INTO public.notification_delivery_queue (
      notification_id,
      user_id,
      channel,
      destination,
      subject,
      content,
      payload,
      status,
      next_attempt_at
    ) VALUES (
      '$notification_id',
      '$user_id',
      '$channel',
      '$(sql_escape "$destination")',
      $( [ -n "$subject" ] && printf "'%s'" "$(sql_escape "$subject")" || printf "NULL" ),
      '$(sql_escape "$content")',
      '{\"smoke_test\":true,\"correlation_id\":\"$correlation_id\"}'::jsonb,
      'pending',
      now()
    )
    RETURNING id;
  "
}

wait_for_queue_final_state() {
  local queue_id="$1"
  local deadline=$(( $(date +%s) + TIMEOUT_SECONDS ))

  while [ "$(date +%s)" -le "$deadline" ]; do
    local row
    row="$(fetch_scalar "
      SELECT status || '|' || COALESCE(last_error, '')
      FROM public.notification_delivery_queue
      WHERE id = '$queue_id';
    ")"

    if [ -z "$row" ]; then
      echo "missing|Fila não encontrada"
      return 0
    fi

    case "${row%%|*}" in
      sent|failed)
        echo "$row"
        return 0
        ;;
    esac

    sleep "$POLL_INTERVAL_SECONDS"
  done

  echo "timeout|Tempo limite excedido aguardando processamento"
}

main() {
  if [ "${1:-}" = "--help" ] || [ "${1:-}" = "-h" ]; then
    usage
    exit 0
  fi

  if [ -z "$TEST_EMAIL_DESTINATION" ] && [ -z "$TEST_WHATSAPP_DESTINATION" ]; then
    error "Informe TEST_EMAIL_DESTINATION e/ou TEST_WHATSAPP_DESTINATION."
    echo
    usage
    exit 1
  fi

  if ! DOCKER_CMD="$(resolve_docker_cmd)"; then
    error "Docker indisponível."
    exit 1
  fi

  local health
  health="$(curl -fsS "$HEALTH_URL")"
  info "Health atual: $health"

  if [ -n "$TEST_EMAIL_DESTINATION" ]; then
    check_channel_readiness "$health" "email"
  fi

  if [ -n "$TEST_WHATSAPP_DESTINATION" ]; then
    check_channel_readiness "$health" "whatsapp"
  fi

  local admin_user_id
  admin_user_id="$(fetch_scalar "
    SELECT p.id
    FROM public.profiles p
    WHERE p.role = 'admin'
      AND COALESCE(p.status, 'active') = 'active'
    ORDER BY CASE WHEN p.username = 'notifvalidate' THEN 0 ELSE 1 END, p.created_at ASC
    LIMIT 1;
  ")"

  if [ -z "$admin_user_id" ]; then
    error "Nenhum administrador ativo encontrado para o smoke test."
    exit 1
  fi

  local correlation_id
  correlation_id="smoke-$(date -u +%Y%m%d%H%M%S)"

  local notification_id
  notification_id="$(fetch_scalar "
    INSERT INTO public.notifications (
      user_id,
      title,
      message,
      type,
      severity,
      event_key,
      metadata
    ) VALUES (
      '$admin_user_id',
      'Smoke test notificações',
      'Teste automático de entrega externa (' || '$correlation_id' || ')',
      'warning',
      'medium',
      'system_critical',
      '{\"smoke_test\":true,\"correlation_id\":\"$correlation_id\"}'::jsonb
    )
    RETURNING id;
  ")"

  if [ -z "$notification_id" ]; then
    error "Falha ao criar a notificação base do smoke test."
    exit 1
  fi

  info "Notificação de smoke test criada: $notification_id"

  local email_queue_id=""
  local whatsapp_queue_id=""
  local content="Smoke test de notificações OnliFin. Correlation ID: $correlation_id"

  if [ -n "$TEST_EMAIL_DESTINATION" ]; then
    email_queue_id="$(insert_queue_item \
      "$notification_id" \
      "$admin_user_id" \
      "email" \
      "$TEST_EMAIL_DESTINATION" \
      "OnliFin smoke test $correlation_id" \
      "$content" \
      "$correlation_id")"
    info "Fila de e-mail criada: $email_queue_id"
  fi

  if [ -n "$TEST_WHATSAPP_DESTINATION" ]; then
    whatsapp_queue_id="$(insert_queue_item \
      "$notification_id" \
      "$admin_user_id" \
      "whatsapp" \
      "$TEST_WHATSAPP_DESTINATION" \
      "" \
      "$content" \
      "$correlation_id")"
    info "Fila de WhatsApp criada: $whatsapp_queue_id"
  fi

  fetch_scalar "
    INSERT INTO public.notification_worker_commands (command, requested_by, payload)
    VALUES ('process_queue', '$admin_user_id', '{\"smoke_test\":true,\"correlation_id\":\"$correlation_id\"}'::jsonb)
    RETURNING id;
  " >/dev/null

  info "Comando process_queue solicitado ao worker"
  echo

  local has_failure="false"

  if [ -n "$email_queue_id" ]; then
    local email_result
    email_result="$(wait_for_queue_final_state "$email_queue_id")"
    info "Resultado e-mail: $email_result"
    if [ "${email_result%%|*}" != "sent" ]; then
      has_failure="true"
    fi
  fi

  if [ -n "$whatsapp_queue_id" ]; then
    local whatsapp_result
    whatsapp_result="$(wait_for_queue_final_state "$whatsapp_queue_id")"
    info "Resultado WhatsApp: $whatsapp_result"
    if [ "${whatsapp_result%%|*}" != "sent" ]; then
      has_failure="true"
    fi
  fi

  echo
  info "Entregas registradas"
  fetch_rows "
    SELECT channel, destination, status, COALESCE(error_message, ''), attempted_at
    FROM public.notification_deliveries
    WHERE notification_id = '$notification_id'
    ORDER BY attempted_at DESC;
  " | while IFS='|' read -r channel destination status error_message attempted_at; do
    echo "  - $channel | $destination | $status | ${error_message:-sem erro} | $attempted_at"
  done

  if [ "$has_failure" = "true" ]; then
    warn "Smoke test concluído com falha em pelo menos um canal."
    exit 2
  fi

  info "Smoke test concluído com sucesso."
}

main "$@"
