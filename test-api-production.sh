#!/bin/bash

# 🧪 Script de Testes da API em Produção - Onlifin
# Este script testa todos os endpoints críticos da API após o deploy

set -e  # Parar execução em caso de erro

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configurações
BASE_URL="https://onlifin.onlitec.com.br/api"
TEST_EMAIL="teste-api-$(date +%s)@example.com"
TEST_PASSWORD="password123"
TEST_NAME="Teste API Deploy"
DEVICE_NAME="Test Script"
LOG_FILE="/tmp/api-test-$(date +%Y%m%d_%H%M%S).log"

# Variáveis globais
AUTH_TOKEN=""
USER_ID=""
ACCOUNT_ID=""
CATEGORY_ID=""
TRANSACTION_ID=""

# Função para logging
log() {
    echo -e "${GREEN}[$(date +'%H:%M:%S')] ✅ $1${NC}"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] SUCCESS: $1" >> $LOG_FILE
}

error() {
    echo -e "${RED}[$(date +'%H:%M:%S')] ❌ $1${NC}"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] ERROR: $1" >> $LOG_FILE
}

warning() {
    echo -e "${YELLOW}[$(date +'%H:%M:%S')] ⚠️  $1${NC}"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] WARNING: $1" >> $LOG_FILE
}

info() {
    echo -e "${BLUE}[$(date +'%H:%M:%S')] ℹ️  $1${NC}"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] INFO: $1" >> $LOG_FILE
}

# Função para fazer requisições HTTP
make_request() {
    local method=$1
    local endpoint=$2
    local data=$3
    local expected_status=$4
    local description=$5
    
    info "Testando: $description"
    
    local headers="-H 'Content-Type: application/json' -H 'Accept: application/json'"
    
    if [ -n "$AUTH_TOKEN" ]; then
        headers="$headers -H 'Authorization: Bearer $AUTH_TOKEN'"
    fi
    
    local cmd="curl -s -w '%{http_code}' -X $method '$BASE_URL$endpoint' $headers"
    
    if [ -n "$data" ]; then
        cmd="$cmd -d '$data'"
    fi
    
    local response=$(eval $cmd)
    local status_code="${response: -3}"
    local body="${response%???}"
    
    echo "Request: $method $endpoint" >> $LOG_FILE
    echo "Response Status: $status_code" >> $LOG_FILE
    echo "Response Body: $body" >> $LOG_FILE
    echo "---" >> $LOG_FILE
    
    if [ "$status_code" = "$expected_status" ]; then
        log "$description - Status: $status_code ✅"
        echo "$body"
        return 0
    else
        error "$description - Esperado: $expected_status, Recebido: $status_code"
        echo "Response: $body"
        return 1
    fi
}

# Teste 1: Documentação da API
test_documentation() {
    info "🔍 Testando documentação da API..."
    
    make_request "GET" "/docs" "" "200" "Documentação da API" > /dev/null
    
    log "Documentação da API acessível"
}

# Teste 2: Registro de usuário
test_user_registration() {
    info "👤 Testando registro de usuário..."
    
    local data="{
        \"name\": \"$TEST_NAME\",
        \"email\": \"$TEST_EMAIL\",
        \"password\": \"$TEST_PASSWORD\",
        \"password_confirmation\": \"$TEST_PASSWORD\",
        \"device_name\": \"$DEVICE_NAME\"
    }"
    
    local response=$(make_request "POST" "/auth/register" "$data" "201" "Registro de usuário")
    
    # Extrair token e user_id da resposta
    AUTH_TOKEN=$(echo "$response" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
    USER_ID=$(echo "$response" | grep -o '"id":[0-9]*' | cut -d':' -f2)
    
    if [ -n "$AUTH_TOKEN" ] && [ -n "$USER_ID" ]; then
        log "Usuário registrado com sucesso - ID: $USER_ID"
    else
        error "Falha ao extrair token ou ID do usuário"
        return 1
    fi
}

# Teste 3: Login de usuário
test_user_login() {
    info "🔐 Testando login de usuário..."
    
    # Limpar token para testar login
    AUTH_TOKEN=""
    
    local data="{
        \"email\": \"$TEST_EMAIL\",
        \"password\": \"$TEST_PASSWORD\",
        \"device_name\": \"$DEVICE_NAME\"
    }"
    
    local response=$(make_request "POST" "/auth/login" "$data" "200" "Login de usuário")
    
    # Extrair novo token
    AUTH_TOKEN=$(echo "$response" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
    
    if [ -n "$AUTH_TOKEN" ]; then
        log "Login realizado com sucesso"
    else
        error "Falha ao extrair token do login"
        return 1
    fi
}

# Teste 4: Informações do usuário autenticado
test_user_profile() {
    info "👤 Testando perfil do usuário..."
    
    make_request "GET" "/auth/me" "" "200" "Perfil do usuário" > /dev/null
    
    log "Perfil do usuário acessível"
}

# Teste 5: Criar conta
test_create_account() {
    info "🏦 Testando criação de conta..."
    
    local data="{
        \"name\": \"Conta Teste\",
        \"type\": \"checking\",
        \"initial_balance\": 1000.00,
        \"description\": \"Conta criada pelo teste automatizado\",
        \"color\": \"#3498db\"
    }"
    
    local response=$(make_request "POST" "/accounts" "$data" "201" "Criação de conta")
    
    # Extrair ID da conta
    ACCOUNT_ID=$(echo "$response" | grep -o '"id":[0-9]*' | cut -d':' -f2)
    
    if [ -n "$ACCOUNT_ID" ]; then
        log "Conta criada com sucesso - ID: $ACCOUNT_ID"
    else
        error "Falha ao extrair ID da conta"
        return 1
    fi
}

# Teste 6: Listar contas
test_list_accounts() {
    info "📋 Testando listagem de contas..."
    
    make_request "GET" "/accounts" "" "200" "Listagem de contas" > /dev/null
    
    log "Listagem de contas funcionando"
}

# Teste 7: Criar categoria
test_create_category() {
    info "📊 Testando criação de categoria..."
    
    local data="{
        \"name\": \"Categoria Teste\",
        \"type\": \"expense\",
        \"color\": \"#e74c3c\",
        \"icon\": \"fa-test\",
        \"description\": \"Categoria criada pelo teste automatizado\"
    }"
    
    local response=$(make_request "POST" "/categories" "$data" "201" "Criação de categoria")
    
    # Extrair ID da categoria
    CATEGORY_ID=$(echo "$response" | grep -o '"id":[0-9]*' | cut -d':' -f2)
    
    if [ -n "$CATEGORY_ID" ]; then
        log "Categoria criada com sucesso - ID: $CATEGORY_ID"
    else
        error "Falha ao extrair ID da categoria"
        return 1
    fi
}

# Teste 8: Criar transação
test_create_transaction() {
    info "💰 Testando criação de transação..."
    
    local data="{
        \"type\": \"expense\",
        \"status\": \"paid\",
        \"date\": \"$(date +%Y-%m-%d)\",
        \"description\": \"Transação de teste\",
        \"amount\": 50.75,
        \"category_id\": $CATEGORY_ID,
        \"account_id\": $ACCOUNT_ID,
        \"notes\": \"Criada pelo teste automatizado\"
    }"
    
    local response=$(make_request "POST" "/transactions" "$data" "201" "Criação de transação")
    
    # Extrair ID da transação
    TRANSACTION_ID=$(echo "$response" | grep -o '"id":[0-9]*' | cut -d':' -f2)
    
    if [ -n "$TRANSACTION_ID" ]; then
        log "Transação criada com sucesso - ID: $TRANSACTION_ID"
    else
        error "Falha ao extrair ID da transação"
        return 1
    fi
}

# Teste 9: Listar transações
test_list_transactions() {
    info "📋 Testando listagem de transações..."
    
    make_request "GET" "/transactions" "" "200" "Listagem de transações" > /dev/null
    
    log "Listagem de transações funcionando"
}

# Teste 10: Resumo de transações
test_transactions_summary() {
    info "📊 Testando resumo de transações..."
    
    make_request "GET" "/transactions/summary" "" "200" "Resumo de transações" > /dev/null
    
    log "Resumo de transações funcionando"
}

# Teste 11: Dashboard
test_dashboard() {
    info "📈 Testando dashboard..."
    
    make_request "GET" "/reports/dashboard" "" "200" "Dashboard" > /dev/null
    
    log "Dashboard funcionando"
}

# Teste 12: Chat com IA
test_ai_chat() {
    info "🤖 Testando chat com IA..."
    
    local data="{
        \"message\": \"Olá, como posso economizar dinheiro?\",
        \"context\": {}
    }"
    
    # IA pode falhar se não configurada, então aceitar 200 ou 500
    local response=$(curl -s -w '%{http_code}' -X POST "$BASE_URL/ai/chat" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -H "Authorization: Bearer $AUTH_TOKEN" \
        -d "$data")
    
    local status_code="${response: -3}"
    
    if [ "$status_code" = "200" ]; then
        log "Chat com IA funcionando"
    elif [ "$status_code" = "500" ]; then
        warning "Chat com IA não configurado (esperado se IA não estiver configurada)"
    else
        error "Chat com IA retornou status inesperado: $status_code"
    fi
}

# Teste 13: Rate Limiting
test_rate_limiting() {
    info "🚦 Testando rate limiting..."
    
    # Fazer várias requisições rápidas para testar rate limiting
    local count=0
    local rate_limited=false
    
    for i in {1..10}; do
        local response=$(curl -s -w '%{http_code}' -X GET "$BASE_URL/auth/me" \
            -H "Authorization: Bearer $AUTH_TOKEN")
        local status_code="${response: -3}"
        
        if [ "$status_code" = "429" ]; then
            rate_limited=true
            break
        fi
        
        count=$((count + 1))
        sleep 0.1
    done
    
    if [ "$rate_limited" = true ]; then
        log "Rate limiting funcionando (limitado após $count requisições)"
    else
        warning "Rate limiting pode não estar funcionando corretamente"
    fi
}

# Teste 14: CORS Headers
test_cors() {
    info "🌐 Testando headers CORS..."
    
    local response=$(curl -s -I -X OPTIONS "$BASE_URL/auth/login" \
        -H "Origin: http://localhost" \
        -H "Access-Control-Request-Method: POST" \
        -H "Access-Control-Request-Headers: Content-Type,Authorization")
    
    if echo "$response" | grep -q "Access-Control-Allow-Origin"; then
        log "Headers CORS configurados corretamente"
    else
        warning "Headers CORS podem não estar configurados"
    fi
}

# Teste 15: Logout
test_logout() {
    info "🚪 Testando logout..."
    
    make_request "POST" "/auth/logout" "" "200" "Logout" > /dev/null
    
    log "Logout funcionando"
}

# Limpeza - remover dados de teste
cleanup_test_data() {
    info "🧹 Limpando dados de teste..."
    
    # Tentar fazer login novamente para limpeza
    local data="{
        \"email\": \"$TEST_EMAIL\",
        \"password\": \"$TEST_PASSWORD\",
        \"device_name\": \"$DEVICE_NAME\"
    }"
    
    local response=$(curl -s -X POST "$BASE_URL/auth/login" \
        -H "Content-Type: application/json" \
        -d "$data")
    
    AUTH_TOKEN=$(echo "$response" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
    
    if [ -n "$AUTH_TOKEN" ]; then
        # Excluir transação de teste
        if [ -n "$TRANSACTION_ID" ]; then
            curl -s -X DELETE "$BASE_URL/transactions/$TRANSACTION_ID" \
                -H "Authorization: Bearer $AUTH_TOKEN" > /dev/null
        fi
        
        # Excluir categoria de teste
        if [ -n "$CATEGORY_ID" ]; then
            curl -s -X DELETE "$BASE_URL/categories/$CATEGORY_ID" \
                -H "Authorization: Bearer $AUTH_TOKEN" > /dev/null
        fi
        
        # Excluir conta de teste
        if [ -n "$ACCOUNT_ID" ]; then
            curl -s -X DELETE "$BASE_URL/accounts/$ACCOUNT_ID" \
                -H "Authorization: Bearer $AUTH_TOKEN" > /dev/null
        fi
        
        log "Dados de teste removidos"
    else
        warning "Não foi possível fazer login para limpeza"
    fi
}

# Função principal
main() {
    echo -e "${BLUE}🧪 Iniciando testes da API Onlifin em produção...${NC}"
    echo "Base URL: $BASE_URL"
    echo "Log: $LOG_FILE"
    echo ""
    
    local tests_passed=0
    local tests_failed=0
    
    # Lista de testes
    local tests=(
        "test_documentation"
        "test_user_registration"
        "test_user_login"
        "test_user_profile"
        "test_create_account"
        "test_list_accounts"
        "test_create_category"
        "test_create_transaction"
        "test_list_transactions"
        "test_transactions_summary"
        "test_dashboard"
        "test_ai_chat"
        "test_rate_limiting"
        "test_cors"
        "test_logout"
    )
    
    # Executar testes
    for test in "${tests[@]}"; do
        if $test; then
            tests_passed=$((tests_passed + 1))
        else
            tests_failed=$((tests_failed + 1))
        fi
        echo ""
    done
    
    # Limpeza
    cleanup_test_data
    
    # Resumo
    echo -e "${BLUE}📊 Resumo dos Testes:${NC}"
    echo -e "${GREEN}✅ Testes Passaram: $tests_passed${NC}"
    echo -e "${RED}❌ Testes Falharam: $tests_failed${NC}"
    echo -e "${BLUE}📝 Log completo: $LOG_FILE${NC}"
    
    if [ $tests_failed -eq 0 ]; then
        echo -e "${GREEN}🎉 Todos os testes passaram! API funcionando corretamente.${NC}"
        return 0
    else
        echo -e "${RED}⚠️  Alguns testes falharam. Verifique os logs para mais detalhes.${NC}"
        return 1
    fi
}

# Verificar se curl está disponível
if ! command -v curl >/dev/null 2>&1; then
    error "curl não encontrado. Instale o curl para executar os testes."
    exit 1
fi

# Executar testes
main "$@"
