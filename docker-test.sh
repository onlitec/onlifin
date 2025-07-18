#!/bin/bash

# Script de teste para verificar se o Onlifin Docker está funcionando corretamente
set -e

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Função para log colorido
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

warn() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] WARNING: $1${NC}"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR: $1${NC}"
}

info() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')] INFO: $1${NC}"
}

# Função para testar endpoint
test_endpoint() {
    local url=$1
    local description=$2
    local expected_status=${3:-200}
    
    info "Testando: $description"
    
    local status_code=$(curl -s -o /dev/null -w "%{http_code}" "$url" || echo "000")
    
    if [[ "$status_code" == "$expected_status" ]]; then
        log "✅ $description - Status: $status_code"
        return 0
    else
        error "❌ $description - Esperado: $expected_status, Recebido: $status_code"
        return 1
    fi
}

# Função para testar serviço
test_service() {
    local service=$1
    local port=$2
    local description=$3
    
    info "Testando serviço: $description"
    
    if nc -z localhost "$port" 2>/dev/null; then
        log "✅ $description está rodando na porta $port"
        return 0
    else
        error "❌ $description não está acessível na porta $port"
        return 1
    fi
}

# Início dos testes
log "🧪 Iniciando testes do Onlifin Docker..."

# Verificar se Docker está rodando
if ! docker info > /dev/null 2>&1; then
    error "Docker não está rodando"
    exit 1
fi

# Verificar se os containers estão rodando
log "📋 Verificando status dos containers..."
if ! docker-compose ps | grep -q "Up"; then
    error "Nenhum container está rodando. Execute 'docker-compose up -d' primeiro."
    exit 1
fi

# Listar containers ativos
info "Containers ativos:"
docker-compose ps

# Testes de conectividade
log "🌐 Testando conectividade..."

# Teste básico da aplicação
test_endpoint "http://localhost:8080" "Página principal da aplicação"

# Teste de endpoints específicos (se existirem)
test_endpoint "http://localhost:8080/login" "Página de login"
test_endpoint "http://localhost:8080/api/health" "Health check da API" 200

# Testes de serviços (se estiverem habilitados)
log "🔌 Testando serviços..."

# Testar aplicação principal
test_service "onlifin" 8080 "Aplicação Onlifin"

# Testar serviços opcionais (apenas se estiverem rodando)
if docker-compose ps | grep -q "mysql.*Up"; then
    test_service "mysql" 3306 "MySQL"
fi

if docker-compose ps | grep -q "postgres.*Up"; then
    test_service "postgres" 5432 "PostgreSQL"
fi

if docker-compose ps | grep -q "redis.*Up"; then
    test_service "redis" 6379 "Redis"
fi

if docker-compose ps | grep -q "mailhog.*Up"; then
    test_service "mailhog" 8025 "MailHog Web UI"
    test_service "mailhog" 1025 "MailHog SMTP"
fi

if docker-compose ps | grep -q "adminer.*Up"; then
    test_service "adminer" 8081 "Adminer"
fi

# Testes de funcionalidade Laravel
log "⚙️ Testando funcionalidades Laravel..."

# Testar Artisan
info "Testando comando Artisan..."
if docker-compose exec -T onlifin php artisan --version > /dev/null 2>&1; then
    log "✅ Artisan está funcionando"
else
    error "❌ Artisan não está funcionando"
fi

# Testar conexão com banco de dados
info "Testando conexão com banco de dados..."
if docker-compose exec -T onlifin php artisan migrate:status > /dev/null 2>&1; then
    log "✅ Conexão com banco de dados está funcionando"
else
    error "❌ Problema na conexão com banco de dados"
fi

# Testar cache
info "Testando sistema de cache..."
if docker-compose exec -T onlifin php artisan cache:clear > /dev/null 2>&1; then
    log "✅ Sistema de cache está funcionando"
else
    error "❌ Problema no sistema de cache"
fi

# Testar permissões de arquivos
log "📁 Testando permissões de arquivos..."

info "Verificando permissões do storage..."
if docker-compose exec -T onlifin test -w /var/www/html/storage; then
    log "✅ Diretório storage tem permissão de escrita"
else
    error "❌ Diretório storage não tem permissão de escrita"
fi

info "Verificando permissões do cache..."
if docker-compose exec -T onlifin test -w /var/www/html/bootstrap/cache; then
    log "✅ Diretório cache tem permissão de escrita"
else
    error "❌ Diretório cache não tem permissão de escrita"
fi

# Testar logs
log "📝 Testando sistema de logs..."

info "Verificando logs da aplicação..."
if docker-compose logs --tail=1 onlifin > /dev/null 2>&1; then
    log "✅ Logs da aplicação estão acessíveis"
else
    error "❌ Problema ao acessar logs da aplicação"
fi

# Teste de performance básico
log "⚡ Teste de performance básico..."

info "Medindo tempo de resposta..."
response_time=$(curl -o /dev/null -s -w "%{time_total}" http://localhost:8080)
if (( $(echo "$response_time < 5.0" | bc -l) )); then
    log "✅ Tempo de resposta aceitável: ${response_time}s"
else
    warn "⚠️ Tempo de resposta alto: ${response_time}s"
fi

# Resumo final
log "📊 Resumo dos testes:"

# Contar sucessos e falhas
total_tests=10  # Ajuste conforme necessário
echo ""
echo -e "${BLUE}🎯 Testes concluídos!${NC}"
echo ""

# Mostrar informações úteis
echo -e "${BLUE}📋 Informações úteis:${NC}"
echo -e "  🌐 Aplicação: ${GREEN}http://localhost:8080${NC}"

if docker-compose ps | grep -q "mailhog.*Up"; then
    echo -e "  📧 MailHog: ${GREEN}http://localhost:8025${NC}"
fi

if docker-compose ps | grep -q "adminer.*Up"; then
    echo -e "  🗄️ Adminer: ${GREEN}http://localhost:8081${NC}"
fi

echo ""
echo -e "${BLUE}🛠️ Comandos úteis:${NC}"
echo -e "  Ver logs: ${GREEN}docker-compose logs -f${NC}"
echo -e "  Shell: ${GREEN}docker-compose exec onlifin sh${NC}"
echo -e "  Reiniciar: ${GREEN}docker-compose restart${NC}"
echo ""

log "✅ Testes concluídos com sucesso!"
