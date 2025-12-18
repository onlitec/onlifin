#!/bin/bash

# Script para fazer backup do banco de dados local

set -e

echo "=========================================="
echo "  Backup do Banco de Dados Local"
echo "=========================================="
echo ""

# Cores
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

print_status() {
    echo -e "${GREEN}[✓]${NC} $1"
}

print_info() {
    echo -e "${BLUE}[i]${NC} $1"
}

# Ir para o diretório do projeto
cd "$(dirname "$0")/.."

# Criar diretório de backups se não existir
mkdir -p backups

# Nome do arquivo com timestamp
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="backups/onlifin_backup_$TIMESTAMP.sql"

print_info "Criando backup..."

# Fazer dump do banco de dados
supabase db dump -f "$BACKUP_FILE"

print_status "Backup criado: $BACKUP_FILE"

# Mostrar tamanho do arquivo
FILE_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
print_info "Tamanho: $FILE_SIZE"

echo ""
echo "Para restaurar este backup:"
echo "  psql -h localhost -p 54322 -U postgres -d postgres < $BACKUP_FILE"
echo ""
