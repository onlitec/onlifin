#!/bin/bash

# Script para resolver o merge conflict
echo "Resolvendo conflito de merge..."

# Navegar para o diretório correto
cd /var/www/html/onlifin

# Verificar status
git status

# Adicionar o arquivo resolvido
git add commit_to_beta_v2.sh

# Verificar se há outros conflitos
git status

# Se não houver mais conflitos, fazer o commit
git commit -m "Resolve merge conflict in commit_to_beta_v2.sh"

echo "Merge conflict resolvido!" 