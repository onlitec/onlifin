#!/bin/bash
set -e

# Se JWT_SECRET estiver definido, configura no PostgreSQL
if [ -n "$JWT_SECRET" ]; then
    echo "🔐 Configurando JWT_SECRET no banco de dados..."
    
    # Adicionar configuração ao postgresql.conf ou via comando SQL de inicialização
    # Nota: Como o PostgreSQL já está rodando ou inicializando, vamos usar um arquivo SQL temporário que será executado pelo entrypoint
    
    echo "ALTER DATABASE onlifin SET \"app.settings.jwt_secret\" TO '$JWT_SECRET';" > /docker-entrypoint-initdb.d/00-jwt-secret.sql
else
    echo "⚠️ JWT_SECRET não definido! Geração de token falhará."
fi
