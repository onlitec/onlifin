#!/bin/bash
# ===========================================
# Onlifin - Verificação de Segurança
# ===========================================
# Executa verificações básicas de segurança antes do deploy

set -e

echo "🔒 Onlifin - Verificação de Segurança"
echo "======================================"
echo ""

ERRORS=0
WARNINGS=0

# Cores
RED='\033[0;31m'
YELLOW='\033[1;33m'
GREEN='\033[0;32m'
NC='\033[0m' # No Color

# Função para erro
error() {
    echo -e "${RED}❌ ERRO: $1${NC}"
    ((ERRORS++))
}

# Função para aviso
warning() {
    echo -e "${YELLOW}⚠️  AVISO: $1${NC}"
    ((WARNINGS++))
}

# Função para sucesso
success() {
    echo -e "${GREEN}✅ $1${NC}"
}

# ===========================================
# Verificações de arquivos sensíveis
# ===========================================
echo "📁 Verificando arquivos sensíveis..."

# Verificar se .env está no .gitignore
if grep -q "^\.env$" .gitignore 2>/dev/null; then
    success ".env está no .gitignore"
else
    error ".env NÃO está no .gitignore - pode vazar credenciais!"
fi

# Verificar se .env.production existe (não deveria estar no repo)
if [ -f ".env.production" ]; then
    error ".env.production existe no diretório - não deveria estar no repo!"
else
    success ".env.production não existe no diretório"
fi

echo ""

# ===========================================
# Verificações de senhas hardcoded
# ===========================================
echo "🔑 Verificando senhas hardcoded..."

# Verificar docker-compose.yml
if grep -q "POSTGRES_PASSWORD:.*[^$]" docker-compose.yml 2>/dev/null; then
    if ! grep -q "POSTGRES_PASSWORD:.*\${" docker-compose.yml; then
        error "Senha hardcoded encontrada em docker-compose.yml"
    else
        success "docker-compose.yml usa variáveis de ambiente"
    fi
else
    success "docker-compose.yml usa variáveis de ambiente"
fi

# Verificar docker-compose.yaml
if [ -f "docker-compose.yaml" ]; then
    if grep -q "POSTGRES_PASSWORD:.*[^$]" docker-compose.yaml 2>/dev/null; then
        if ! grep -q "POSTGRES_PASSWORD:.*\${" docker-compose.yaml; then
            warning "Possível senha hardcoded em docker-compose.yaml"
        fi
    fi
fi

# Verificar Dockerfile.postgres
if grep -q "ENV POSTGRES_PASSWORD" docker/Dockerfile.postgres 2>/dev/null; then
    error "Senha hardcoded em docker/Dockerfile.postgres"
else
    success "Dockerfile.postgres não tem senha hardcoded"
fi

echo ""

# ===========================================
# Verificações de JWT Secret
# ===========================================
echo "🔐 Verificando JWT Secret..."

if [ -f ".env" ]; then
    JWT_SECRET=$(grep "^JWT_SECRET=" .env | cut -d'=' -f2 || echo "")
    if [ -n "$JWT_SECRET" ]; then
        JWT_LENGTH=${#JWT_SECRET}
        if [ $JWT_LENGTH -lt 32 ]; then
            error "JWT_SECRET muito curto (${JWT_LENGTH} chars) - mínimo recomendado: 64"
        elif [ $JWT_LENGTH -lt 64 ]; then
            warning "JWT_SECRET tem ${JWT_LENGTH} chars - recomendado: 64+"
        else
            success "JWT_SECRET tem tamanho adequado (${JWT_LENGTH} chars)"
        fi
        
        # Verificar se é o secret padrão
        if echo "$JWT_SECRET" | grep -q "super-secret\|change-me\|example\|default"; then
            error "JWT_SECRET parece ser um valor padrão - ALTERE IMEDIATAMENTE!"
        fi
    else
        warning "JWT_SECRET não encontrado em .env"
    fi
else
    warning "Arquivo .env não encontrado"
fi

echo ""

# ===========================================
# Verificações de configuração nginx
# ===========================================
echo "🌐 Verificando configuração nginx..."

if [ -f "nginx.conf" ]; then
    # Verificar rate limiting
    if grep -q "limit_req_zone" nginx.conf; then
        success "Rate limiting configurado no nginx"
    else
        warning "Rate limiting não encontrado no nginx.conf"
    fi
    
    # Verificar security headers
    if grep -q "X-Frame-Options" nginx.conf; then
        success "X-Frame-Options header presente"
    else
        warning "X-Frame-Options header não encontrado"
    fi
    
    if grep -q "Content-Security-Policy" nginx.conf; then
        success "CSP header presente"
    else
        warning "Content-Security-Policy header não encontrado"
    fi
    
    # Verificar server_tokens
    if grep -q "server_tokens off" nginx.conf; then
        success "server_tokens está desabilitado"
    else
        warning "server_tokens não está explicitamente desabilitado"
    fi
else
    warning "nginx.conf não encontrado"
fi

echo ""

# ===========================================
# Verificações de dependências
# ===========================================
echo "📦 Verificando dependências..."

if [ -f "package.json" ]; then
    # Verificar se npm audit está disponível
    if command -v npm &> /dev/null; then
        echo "   Executando npm audit (pode demorar)..."
        AUDIT_RESULT=$(npm audit --json 2>/dev/null || echo '{"vulnerabilities":{}}')
        
        # Contar vulnerabilidades críticas e altas
        CRITICAL=$(echo "$AUDIT_RESULT" | grep -o '"critical":[0-9]*' | head -1 | cut -d':' -f2 || echo "0")
        HIGH=$(echo "$AUDIT_RESULT" | grep -o '"high":[0-9]*' | head -1 | cut -d':' -f2 || echo "0")
        
        if [ "${CRITICAL:-0}" -gt 0 ]; then
            error "Encontradas $CRITICAL vulnerabilidades CRÍTICAS nas dependências"
        fi
        
        if [ "${HIGH:-0}" -gt 0 ]; then
            warning "Encontradas $HIGH vulnerabilidades ALTAS nas dependências"
        fi
        
        if [ "${CRITICAL:-0}" -eq 0 ] && [ "${HIGH:-0}" -eq 0 ]; then
            success "Nenhuma vulnerabilidade crítica ou alta encontrada"
        fi
    else
        warning "npm não encontrado - não foi possível verificar vulnerabilidades"
    fi
else
    warning "package.json não encontrado"
fi

echo ""

# ===========================================
# Resumo
# ===========================================
echo "======================================"
echo "📊 RESUMO DA VERIFICAÇÃO"
echo "======================================"

if [ $ERRORS -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    echo -e "${GREEN}✅ Todas as verificações passaram!${NC}"
    exit 0
elif [ $ERRORS -eq 0 ]; then
    echo -e "${YELLOW}⚠️  $WARNINGS aviso(s) encontrado(s)${NC}"
    echo "   Revise os avisos antes do deploy"
    exit 0
else
    echo -e "${RED}❌ $ERRORS erro(s) e $WARNINGS aviso(s) encontrado(s)${NC}"
    echo "   CORRIJA OS ERROS antes do deploy!"
    exit 1
fi
