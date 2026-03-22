#!/bin/bash
set -euo pipefail

APP_BASE_URL="${APP_BASE_URL:-http://localhost:8081}"
DB_USER="${DB_USER:-onlifin}"
DB_NAME="${DB_NAME:-onlifin}"
PLAYWRIGHT_PACKAGE="${PLAYWRIGHT_PACKAGE:-playwright}"
PLAYWRIGHT_RUNTIME_DIR="${PLAYWRIGHT_RUNTIME_DIR:-${XDG_CACHE_HOME:-$HOME/.cache}/onlifin-admin-access-smoke}"
ACCOUNT_ADMIN_EMAIL="${ACCOUNT_ADMIN_EMAIL:-accountadmin.validate@miaoda.com}"
ACCOUNT_ADMIN_PASSWORD="${ACCOUNT_ADMIN_PASSWORD:-AccountValidate@2026!}"
PLATFORM_ADMIN_EMAIL="${PLATFORM_ADMIN_EMAIL:-platformadmin.validate@miaoda.com}"
PLATFORM_ADMIN_PASSWORD="${PLATFORM_ADMIN_PASSWORD:-PlatformValidate@2026!}"

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
  /opt/onlifin/source-real/scripts/run-admin-access-smoke-test.sh

Objetivo:
  - criar usuarios temporarios de validacao
  - validar no browser headless a segregacao entre:
    - Admin da Conta
    - Admin da Plataforma
  - remover os usuarios temporarios ao final

Variaveis opcionais:
  APP_BASE_URL
  DB_USER
  DB_NAME
  PLAYWRIGHT_PACKAGE
  PLAYWRIGHT_RUNTIME_DIR
  ACCOUNT_ADMIN_EMAIL
  ACCOUNT_ADMIN_PASSWORD
  PLATFORM_ADMIN_EMAIL
  PLATFORM_ADMIN_PASSWORD
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

psql_exec() {
  local sql="$1"
  $DOCKER_CMD exec -i onlifin-db psql -v ON_ERROR_STOP=1 -U "$DB_USER" -d "$DB_NAME" -c "$sql" >/dev/null
}

psql_scalar() {
  local sql="$1"
  $DOCKER_CMD exec -i onlifin-db psql -v ON_ERROR_STOP=1 -U "$DB_USER" -d "$DB_NAME" -t -A -c "$sql" | tr -d '\r' | sed '/^$/d' | head -n 1
}

resolve_chromium_path() {
  local candidates=(
    "${CHROMIUM_EXECUTABLE_PATH:-}"
    "$(command -v google-chrome 2>/dev/null || true)"
    "$(command -v google-chrome-stable 2>/dev/null || true)"
    "$(command -v chromium 2>/dev/null || true)"
    "$(command -v chromium-browser 2>/dev/null || true)"
  )
  local candidate

  for candidate in "${candidates[@]}"; do
    if [ -n "$candidate" ] && [ -x "$candidate" ]; then
      printf "%s\n" "$candidate"
      return 0
    fi
  done

  return 1
}

ensure_playwright_runtime() {
  if ! command -v npm >/dev/null 2>&1; then
    error "npm nao encontrado. Instale Node.js/npm antes de rodar o smoke test."
    exit 1
  fi

  mkdir -p "$PLAYWRIGHT_RUNTIME_DIR"

  if [ ! -f "$PLAYWRIGHT_RUNTIME_DIR/package.json" ]; then
    cat > "$PLAYWRIGHT_RUNTIME_DIR/package.json" <<EOF
{
  "name": "onlifin-admin-access-smoke",
  "private": true
}
EOF
  fi

  if [ ! -d "$PLAYWRIGHT_RUNTIME_DIR/node_modules/$PLAYWRIGHT_PACKAGE" ]; then
    info "Preparando runtime local de $PLAYWRIGHT_PACKAGE em cache"
    PLAYWRIGHT_SKIP_BROWSER_DOWNLOAD=1 npm install \
      --prefix "$PLAYWRIGHT_RUNTIME_DIR" \
      --no-save \
      --silent \
      "$PLAYWRIGHT_PACKAGE"
  fi
}

cleanup_temp_users() {
  if [ -z "${DOCKER_CMD:-}" ]; then
    return 0
  fi

  warn "Removendo usuarios temporarios de validacao"
  $DOCKER_CMD exec -i onlifin-db psql -v ON_ERROR_STOP=1 -U "$DB_USER" -d "$DB_NAME" <<SQL >/dev/null
DELETE FROM public.profiles
WHERE email IN (
  '$(sql_escape "$ACCOUNT_ADMIN_EMAIL")',
  '$(sql_escape "$PLATFORM_ADMIN_EMAIL")'
);

DELETE FROM auth.users
WHERE email IN (
  '$(sql_escape "$ACCOUNT_ADMIN_EMAIL")',
  '$(sql_escape "$PLATFORM_ADMIN_EMAIL")'
);
SQL
}

main() {
  trap cleanup_temp_users EXIT

  if [ "${1:-}" = "--help" ] || [ "${1:-}" = "-h" ]; then
    usage
    exit 0
  fi

  if ! command -v node >/dev/null 2>&1; then
    error "node nao encontrado. Instale Node.js/npm antes de rodar o smoke test."
    exit 1
  fi

  if ! DOCKER_CMD="$(resolve_docker_cmd)"; then
    error "Docker indisponivel."
    exit 1
  fi

  local chromium_path
  chromium_path="$(resolve_chromium_path || true)"
  if [ -z "$chromium_path" ]; then
    error "Nenhum executavel Chromium/Chrome encontrado no host."
    exit 1
  fi

  ensure_playwright_runtime

  local tenant_id
  tenant_id="$(psql_scalar "SELECT id FROM public.tenants ORDER BY created_at ASC, id ASC LIMIT 1;")"

  if [ -z "$tenant_id" ]; then
    error "Nenhum tenant encontrado para criar o usuario temporario de Admin da Conta."
    exit 1
  fi

  info "Tenant de validacao selecionado: $tenant_id"
  info "Criando usuarios temporarios"

  $DOCKER_CMD exec -i onlifin-db psql -v ON_ERROR_STOP=1 -U "$DB_USER" -d "$DB_NAME" <<SQL >/dev/null
DO \$\$
DECLARE
  v_account_user_id uuid;
  v_platform_user_id uuid;
BEGIN
  IF NOT EXISTS (SELECT 1 FROM auth.users WHERE email = '$(sql_escape "$ACCOUNT_ADMIN_EMAIL")') THEN
    INSERT INTO auth.users (email, password_hash)
    VALUES ('$(sql_escape "$ACCOUNT_ADMIN_EMAIL")', crypt('$(sql_escape "$ACCOUNT_ADMIN_PASSWORD")', gen_salt('bf')))
    RETURNING id INTO v_account_user_id;

    UPDATE public.profiles
    SET full_name = 'Account Admin Validate',
        email = '$(sql_escape "$ACCOUNT_ADMIN_EMAIL")',
        tenant_id = '$tenant_id'::uuid,
        role = 'user',
        status = 'active',
        settings = coalesce(settings, '{}'::jsonb) || jsonb_build_object('account_admin', true, 'plan_code', 'medium')
    WHERE id = v_account_user_id;
  END IF;

  IF NOT EXISTS (SELECT 1 FROM auth.users WHERE email = '$(sql_escape "$PLATFORM_ADMIN_EMAIL")') THEN
    INSERT INTO auth.users (email, password_hash)
    VALUES ('$(sql_escape "$PLATFORM_ADMIN_EMAIL")', crypt('$(sql_escape "$PLATFORM_ADMIN_PASSWORD")', gen_salt('bf')))
    RETURNING id INTO v_platform_user_id;

    UPDATE public.profiles
    SET full_name = 'Platform Admin Validate',
        email = '$(sql_escape "$PLATFORM_ADMIN_EMAIL")',
        role = 'admin',
        status = 'active',
        settings = coalesce(settings, '{}'::jsonb)
    WHERE id = v_platform_user_id;
  END IF;
END \$\$;
SQL

  local temp_js
  temp_js="$(mktemp /tmp/onlifin-admin-access-smoke-XXXXXX.js)"

  cat > "$temp_js" <<'EOF'
const { chromium } = require(process.env.PLAYWRIGHT_MODULE);

const baseUrl = process.env.APP_BASE_URL;
const accountAdminEmail = process.env.ACCOUNT_ADMIN_EMAIL;
const accountAdminPassword = process.env.ACCOUNT_ADMIN_PASSWORD;
const platformAdminEmail = process.env.PLATFORM_ADMIN_EMAIL;
const platformAdminPassword = process.env.PLATFORM_ADMIN_PASSWORD;
const chromiumExecutablePath = process.env.CHROMIUM_EXECUTABLE_PATH || undefined;

function log(message) {
  process.stdout.write(`${message}\n`);
}

async function assertNotOnPath(page, deniedPath, message) {
  try {
    await page.waitForURL((url) => !url.pathname.endsWith(deniedPath), { timeout: 10000 });
  } catch (error) {
    // The assertion below reports the effective route if the redirect never happens.
  }

  if (new URL(page.url()).pathname.endsWith(deniedPath)) {
    throw new Error(message);
  }
}

async function login(page, email, password) {
  await page.goto(`${baseUrl}/login`, { waitUntil: 'domcontentloaded' });
  await page.getByRole('textbox', { name: 'Usuário ou E-mail' }).waitFor({ state: 'visible', timeout: 20000 });
  await page.getByRole('textbox', { name: 'Usuário ou E-mail' }).fill(email);
  await page.getByRole('textbox', { name: 'Senha' }).waitFor({ state: 'visible', timeout: 20000 });
  await page.getByRole('textbox', { name: 'Senha' }).fill(password);
  await page.getByRole('button', { name: 'Entrar na Plataforma' }).click();
  await page.waitForURL(/\/pf$/, { timeout: 20000 });
}

async function expectVisible(page, text, message) {
  const locator = page.getByText(text, { exact: true });
  try {
    await locator.first().waitFor({ state: 'visible', timeout: 10000 });
  } catch (error) {
    throw new Error(message);
  }
}

async function expectHidden(page, text, message) {
  const locator = page.getByText(text, { exact: true });
  await page.waitForTimeout(1000);
  if (await locator.count() > 0 && await locator.first().isVisible()) {
    throw new Error(message);
  }
}

async function validateAccountAdmin(browser) {
  const context = await browser.newContext();
  const page = await context.newPage();

  await login(page, accountAdminEmail, accountAdminPassword);
  await page.goto(`${baseUrl}/preferences`, { waitUntil: 'domcontentloaded' });
  await expectVisible(page, 'Preferências', 'Admin da Conta nao abriu /preferences.');
  await expectVisible(page, 'Destinos pessoais de notificação', 'Admin da Conta nao visualizou os destinos pessoais de notificacao.');

  await page.goto(`${baseUrl}/pf`, { waitUntil: 'domcontentloaded' });
  await expectVisible(page, 'Administração', 'Admin da Conta nao exibiu o menu Administração.');
  await expectHidden(page, 'Configurações', 'Admin da Conta nao deveria ver o menu Configurações.');
  await expectVisible(page, 'Preferências', 'Admin da Conta nao exibiu o menu Preferências.');
  await expectVisible(page, 'Admin da Conta', 'Rotulo de Admin da Conta nao apareceu.');

  await page.goto(`${baseUrl}/settings`, { waitUntil: 'domcontentloaded' });
  await assertNotOnPath(page, '/settings', 'Admin da Conta nao deveria permanecer em /settings.');

  await page.goto(`${baseUrl}/admin-notifications`, { waitUntil: 'domcontentloaded' });
  await assertNotOnPath(page, '/admin-notifications', 'Admin da Conta nao deveria acessar /admin-notifications.');

  await page.goto(`${baseUrl}/user-management`, { waitUntil: 'domcontentloaded' });
  await assertNotOnPath(page, '/user-management', 'Admin da Conta nao deveria acessar /user-management.');

  await page.goto(`${baseUrl}/ai-admin`, { waitUntil: 'domcontentloaded' });
  await assertNotOnPath(page, '/ai-admin', 'Admin da Conta nao deveria acessar /ai-admin.');

  await page.goto(`${baseUrl}/admin-general`, { waitUntil: 'domcontentloaded' });
  await expectVisible(page, 'Administracao da Conta', 'Painel seguro de Administração da Conta nao abriu.');
  await context.close();
}

async function validatePlatformAdmin(browser) {
  const context = await browser.newContext();
  const page = await context.newPage();

  await login(page, platformAdminEmail, platformAdminPassword);
  await page.goto(`${baseUrl}/preferences`, { waitUntil: 'domcontentloaded' });
  await expectVisible(page, 'Preferências', 'Admin da Plataforma nao abriu /preferences.');
  await expectVisible(page, 'Destinos pessoais de notificação', 'Admin da Plataforma nao visualizou os destinos pessoais de notificacao.');

  await page.goto(`${baseUrl}/pf`, { waitUntil: 'domcontentloaded' });
  await expectVisible(page, 'Administração', 'Admin da Plataforma nao exibiu o menu Administração.');
  await expectVisible(page, 'Configurações', 'Admin da Plataforma nao exibiu o menu Configurações.');
  await expectVisible(page, 'Preferências', 'Admin da Plataforma nao exibiu o menu Preferências.');
  await expectVisible(page, 'Admin da Plataforma', 'Rotulo de Admin da Plataforma nao apareceu.');

  await page.goto(`${baseUrl}/settings`, { waitUntil: 'domcontentloaded' });
  if (!page.url().includes('/settings')) {
    throw new Error('Admin da Plataforma deveria permanecer em /settings.');
  }

  await expectVisible(page, 'Configurações da Plataforma', 'Tela Configurações nao abriu para Admin da Plataforma.');

  await page.goto(`${baseUrl}/admin-notifications`, { waitUntil: 'domcontentloaded' });
  await expectVisible(page, 'Processar fila agora', 'Admin da Plataforma nao abriu /admin-notifications.');

  await page.goto(`${baseUrl}/user-management`, { waitUntil: 'domcontentloaded' });
  await expectVisible(page, 'Gestão de Usuários', 'Admin da Plataforma nao abriu /user-management.');

  await page.goto(`${baseUrl}/ai-admin`, { waitUntil: 'domcontentloaded' });
  await expectVisible(page, 'Administração de IA', 'Admin da Plataforma nao abriu /ai-admin.');
  await context.close();
}

(async () => {
  const browser = await chromium.launch({
    executablePath: chromiumExecutablePath,
    headless: true,
  });

  try {
    await validateAccountAdmin(browser);
    log('[INFO] Validacao de Admin da Conta concluida.');

    await validatePlatformAdmin(browser);
    log('[INFO] Validacao de Admin da Plataforma concluida.');
  } finally {
    await browser.close();
  }
})().catch((error) => {
  console.error(`[ERROR] ${error.message}`);
  process.exit(1);
});
EOF

  info "Executando validacao no browser headless"
  local playwright_module
  playwright_module="$PLAYWRIGHT_RUNTIME_DIR/node_modules/$PLAYWRIGHT_PACKAGE"
  APP_BASE_URL="$APP_BASE_URL" \
  PLAYWRIGHT_MODULE="$playwright_module" \
  CHROMIUM_EXECUTABLE_PATH="$chromium_path" \
  ACCOUNT_ADMIN_EMAIL="$ACCOUNT_ADMIN_EMAIL" \
  ACCOUNT_ADMIN_PASSWORD="$ACCOUNT_ADMIN_PASSWORD" \
  PLATFORM_ADMIN_EMAIL="$PLATFORM_ADMIN_EMAIL" \
  PLATFORM_ADMIN_PASSWORD="$PLATFORM_ADMIN_PASSWORD" \
  node "$temp_js"

  rm -f "$temp_js"
  info "Smoke test de acesso administrativo concluido com sucesso"
}

main "$@"
