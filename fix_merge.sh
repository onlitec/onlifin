#!/bin/bash

echo "Forçando resolução do merge conflict..."

# Navegar para o diretório
cd /var/www/html/onlifin

# Verificar status
echo "Status atual:"
git status

# Adicionar todos os arquivos
echo "Adicionando arquivos..."
git add .

# Verificar status novamente
echo "Status após git add:"
git status

# Fazer commit do merge
echo "Fazendo commit do merge..."
git commit -m "Merge branch 'main' into beta - Release v2.0.0

Resolução de conflitos:
- commit_to_beta_v2.sh: conflito resolvido

Alterações incluídas:
- Novos comandos console para categorias
- Melhorias nos controllers
- Atualizações nos models
- Limpeza de migrations obsoletas"

echo "Merge concluído com sucesso!" 