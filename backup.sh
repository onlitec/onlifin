#!/bin/bash

# Diretório base do projeto
BASE_DIR="/var/www/html/dev.onlifin"

# Timestamp para o backup
timestamp=$(date +%Y%m%d_%H%M%S)

# Nome do arquivo de backup
backup_name="onlifin_backup_$timestamp"

# Diretório temporário para o backup
tmp_dir="/tmp/$backup_name"

# Cria diretório temporário
mkdir -p "$tmp_dir"

# Copia o código fonte
rsync -av --exclude='node_modules' --exclude='vendor' --exclude='storage' --exclude='.git' "$BASE_DIR/" "$tmp_dir/"

# Backup do banco de dados
mysqldump -u $(cat .env | grep DB_USERNAME | cut -d '=' -f2) -p$(cat .env | grep DB_PASSWORD | cut -d '=' -f2) $(cat .env | grep DB_DATABASE | cut -d '=' -f2) > "$tmp_dir/database.sql"

# Compacta tudo em um arquivo .tar.gz
tar -czf "$BASE_DIR/backups/$backup_name.tar.gz" -C "$tmp_dir" .

# Remove o diretório temporário
rm -rf "$tmp_dir"

echo "Backup completo criado em: $BASE_DIR/backups/$backup_name.tar.gz"
