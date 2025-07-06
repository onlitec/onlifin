#!/bin/bash

# Script para atualizar para a Release v3.0.0
echo "=== Atualizando para Release v3.0.0 ==="

# Navegar para o diretório do projeto
cd /var/www/html/onlifin

# Verificar status atual
echo "Status atual do repositório:"
git status

# Limpar arquivos temporários
echo "Limpando arquivos temporários..."
git add -A
git commit -m "Cleanup: Remove temporary files before v3.0.0 update" || echo "Nada para commitar"

# Fazer backup da branch beta atual
echo "Fazendo backup da branch beta atual..."
git branch beta-backup-$(date +%Y%m%d-%H%M%S)

# Verificar se estamos na branch beta
echo "Branch atual:"
git branch --show-current

# Fazer merge da tag v3.0.0 para a branch beta
echo "Fazendo merge da tag v3.0.0 para a branch beta..."
git merge v3.0.0 -m "Merge release v3.0.0 into beta

- Sistema completo de autenticação social com Google
- Sistema de email SMTP com templates personalizados  
- Autenticação de dois fatores (2FA)
- Melhorias gerais do sistema
- Atualização para versão 3.0.0"

# Verificar status após o merge
echo "Status após merge:"
git status

# Se houver conflitos, mostrar quais arquivos
if [ $? -ne 0 ]; then
    echo "=== CONFLITOS DETECTADOS ==="
    echo "Arquivos em conflito:"
    git diff --name-only --diff-filter=U
    echo "Execute 'git status' para ver detalhes dos conflitos"
    echo "Após resolver os conflitos, execute 'git add <arquivo>' e 'git commit'"
else
    echo "=== MERGE CONCLUÍDO COM SUCESSO ==="
    echo "Versão local atualizada para v3.0.0"
fi

echo "=== Processo de atualização finalizado ===" 