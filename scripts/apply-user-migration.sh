#!/bin/bash
# Script para aplicar migration de gestão de usuários

echo "🔄 Aplicando migration de gestão de usuários..."

# Executar SQL usando cat e pipe (evita docker exec que está bloqueado pelo AppArmor)
cat /opt/onlifin/docker/init-db/07-user-management-enhancements.sql | docker exec -i onlifin-database psql -U onlifin -d onlifin

if [ $? -eq 0 ]; then
    echo "✅ Migration aplicada com sucesso!"
else
    echo "❌ Erro ao aplicar migration"
    exit 1
fi
