#!/bin/bash

# Script para iniciar o ambiente de desenvolvimento local do Onlifin
# Inicia Supabase local e o servidor de desenvolvimento

set -e

echo "=========================================="
echo "  Iniciando Onlifin - Desenvolvimento Local"
echo "=========================================="
echo ""

# Cores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_status() {
    echo -e "${GREEN}[✓]${NC} $1"
}

print_info() {
    echo -e "${BLUE}[i]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[!]${NC} $1"
}

# Ir para o diretório do projeto
cd "$(dirname "$0")/.."

# 1. Verificar se Supabase está inicializado
echo "1. Verificando Supabase..."
if [ ! -f "supabase/config.toml" ]; then
    print_warning "Supabase não inicializado. Inicializando..."
    supabase init
    print_status "Supabase inicializado!"
else
    print_status "Supabase já inicializado"
fi

# 2. Iniciar Supabase local
echo ""
echo "2. Iniciando Supabase local..."
print_info "Isso pode levar alguns minutos na primeira vez (download de imagens Docker)..."

# Verificar se já está rodando
if supabase status >/dev/null 2>&1; then
    print_warning "Supabase já está rodando. Parando para reiniciar..."
    supabase stop
fi

# Iniciar Supabase
supabase start

print_status "Supabase iniciado com sucesso!"

# 3. Obter credenciais locais
echo ""
echo "3. Obtendo credenciais locais..."
SUPABASE_STATUS=$(supabase status)

# Extrair URLs e chaves
API_URL=$(echo "$SUPABASE_STATUS" | grep "API URL" | awk '{print $3}')
ANON_KEY=$(echo "$SUPABASE_STATUS" | grep "anon key" | awk '{print $3}')
SERVICE_ROLE_KEY=$(echo "$SUPABASE_STATUS" | grep "service_role key" | awk '{print $3}')
STUDIO_URL=$(echo "$SUPABASE_STATUS" | grep "Studio URL" | awk '{print $3}')

# 4. Atualizar .env.local
echo ""
echo "4. Criando arquivo .env.local..."
cat > .env.local << EOF
# Configuração Local do Supabase
# Gerado automaticamente por start_local.sh

VITE_APP_ID=app-7xkeeoe4bsap

VITE_SUPABASE_URL=$API_URL
VITE_SUPABASE_ANON_KEY=$ANON_KEY

# Chave de serviço (não expor no frontend!)
SUPABASE_SERVICE_ROLE_KEY=$SERVICE_ROLE_KEY
EOF

print_status "Arquivo .env.local criado!"

# 5. Aplicar migrações
echo ""
echo "5. Aplicando migrações do banco de dados..."
supabase db reset
print_status "Migrações aplicadas!"

# 6. Mostrar informações
echo ""
echo "=========================================="
echo -e "${GREEN}Ambiente local iniciado com sucesso!${NC}"
echo "=========================================="
echo ""
echo "📊 Informações do ambiente:"
echo "  - API URL: $API_URL"
echo "  - Studio URL: $STUDIO_URL"
echo "  - DB URL: postgresql://postgres:postgres@localhost:54322/postgres"
echo ""
echo "🔑 Credenciais padrão:"
echo "  - Email: admin@financeiro.com"
echo "  - Senha: admin123"
echo ""
echo "📝 Próximos passos:"
echo "  1. Acesse o Supabase Studio: $STUDIO_URL"
echo "  2. Em outro terminal, execute: pnpm dev"
echo "  3. Acesse a aplicação em: http://localhost:5173"
echo ""
echo "⚠️  Para parar o Supabase: supabase stop"
echo ""
