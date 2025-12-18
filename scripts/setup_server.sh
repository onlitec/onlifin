#!/bin/bash

# Script de Setup do Servidor Ubuntu para Onlifin
# Este script instala todas as dependências necessárias para desenvolvimento local

set -e  # Parar em caso de erro

echo "=========================================="
echo "  Setup do Servidor Onlifin - Local Dev"
echo "=========================================="
echo ""

# Cores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Função para verificar se comando existe
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Função para imprimir status
print_status() {
    echo -e "${GREEN}[✓]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[!]${NC} $1"
}

print_error() {
    echo -e "${RED}[✗]${NC} $1"
}

# 1. Verificar Node.js
echo "1. Verificando Node.js..."
if command_exists node; then
    NODE_VERSION=$(node --version)
    print_status "Node.js já instalado: $NODE_VERSION"
else
    print_error "Node.js não encontrado!"
    echo "Por favor, instale Node.js v18+ manualmente:"
    echo "  curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -"
    echo "  sudo apt-get install -y nodejs"
    exit 1
fi

# 2. Instalar pnpm
echo ""
echo "2. Instalando pnpm..."
if command_exists pnpm; then
    PNPM_VERSION=$(pnpm --version)
    print_status "pnpm já instalado: $PNPM_VERSION"
else
    print_warning "Instalando pnpm..."
    npm install -g pnpm
    print_status "pnpm instalado com sucesso!"
fi

# 3. Verificar/Instalar Docker
echo ""
echo "3. Verificando Docker..."
if command_exists docker; then
    DOCKER_VERSION=$(docker --version)
    print_status "Docker já instalado: $DOCKER_VERSION"
else
    print_warning "Docker não encontrado. Instalando..."
    
    # Atualizar índice de pacotes
    sudo apt-get update
    
    # Instalar dependências
    sudo apt-get install -y \
        ca-certificates \
        curl \
        gnupg \
        lsb-release
    
    # Adicionar chave GPG oficial do Docker
    sudo mkdir -p /etc/apt/keyrings
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
    
    # Configurar repositório
    echo \
      "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
      $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
    
    # Instalar Docker Engine
    sudo apt-get update
    sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
    
    # Adicionar usuário ao grupo docker
    sudo usermod -aG docker $USER
    
    print_status "Docker instalado com sucesso!"
    print_warning "IMPORTANTE: Você precisa fazer logout e login novamente para usar Docker sem sudo"
fi

# 4. Verificar Docker Compose
echo ""
echo "4. Verificando Docker Compose..."
if docker compose version >/dev/null 2>&1; then
    COMPOSE_VERSION=$(docker compose version)
    print_status "Docker Compose já instalado: $COMPOSE_VERSION"
else
    print_error "Docker Compose não encontrado!"
    print_warning "Normalmente vem com Docker. Tente reinstalar Docker."
fi

# 5. Instalar Supabase CLI
echo ""
echo "5. Instalando Supabase CLI..."
if command_exists supabase; then
    SUPABASE_VERSION=$(supabase --version)
    print_status "Supabase CLI já instalado: $SUPABASE_VERSION"
else
    print_warning "Instalando Supabase CLI..."
    npm install -g supabase
    print_status "Supabase CLI instalado com sucesso!"
fi

# 6. Instalar dependências do projeto
echo ""
echo "6. Instalando dependências do projeto..."
cd "$(dirname "$0")/.."
pnpm install
print_status "Dependências do projeto instaladas!"

echo ""
echo "=========================================="
echo -e "${GREEN}Setup concluído com sucesso!${NC}"
echo "=========================================="
echo ""
echo "Próximos passos:"
echo "1. Se Docker foi instalado agora, faça logout e login novamente"
echo "2. Execute: ./scripts/start_local.sh"
echo ""
