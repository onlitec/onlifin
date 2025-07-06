#!/bin/bash

# Script para commit e push das alterações para a branch beta - Release v2.0.0
# Execute este script manualmente no terminal

echo "=== Iniciando processo de commit para branch beta - Release v2.0.0 ==="

# Navegar para o diretório do projeto
cd /var/www/html/onlifin

# Verificar status atual
echo "Status atual do repositório:"
git status

# Adicionar todas as alterações
echo "Adicionando todas as alterações..."
git add -A

# Fazer commit com mensagem detalhada
echo "Fazendo commit..."
git commit -m "Release v2.0.0 - Atualizações e melhorias

- Atualizações nos controllers:
  * CategoryController.php
  * FixedStatementImportController.php  
  * StatementImportController.php
  * TempStatementImportController.php

- Melhorias no Job ProcessUploadedFinancialFile.php
- Atualizações no Livewire FormModal para transações
- Melhorias nos models Category e Transaction
- Remoção de migrations obsoletas:
  * 0001_01_01_000001_create_cache_table.php
  * 0001_01_01_000002_create_jobs_table.php
  * 2014_10_12_000000_create (parcial)

- Preparação para release v2.0.0 na branch beta"

# Mudar para a branch beta
echo "Mudando para a branch beta..."
git checkout beta

# Fazer merge das alterações da main para beta (se necessário)
echo "Fazendo merge das alterações..."
git merge main

# Fazer push para a branch beta
echo "Fazendo push para origin/beta..."
git push origin beta

echo "=== Processo concluído! ==="
echo "Alterações enviadas para a branch beta - Release v2.0.0"
