#!/bin/bash

# Script para monitorar e corrigir permissões continuamente
echo "🔄 Iniciando monitoramento de permissões..."

while true; do
    # Verificar se o diretório views é gravável
    if [ ! -w "/var/www/html/storage/framework/views" ]; then
        echo "⚠️ Permissões perdidas - corrigindo..."
        chmod -R 777 /var/www/html/storage
        chmod -R 777 /var/www/html/bootstrap
        echo "✅ Permissões corrigidas automaticamente"
    fi
    
    # Verificar se consegue criar um arquivo de teste
    if ! echo "teste" > /var/www/html/storage/framework/views/test-$(date +%s).txt 2>/dev/null; then
        echo "❌ Falha na escrita - aplicando correção extrema"
        chmod -R 777 /var/www/html/storage
        chmod -R 777 /var/www/html/bootstrap
        mkdir -p /var/www/html/storage/framework/views
        mkdir -p /var/www/html/storage/framework/cache
        mkdir -p /var/www/html/storage/framework/sessions
    else
        # Limpar arquivos de teste
        rm -f /var/www/html/storage/framework/views/test-*.txt 2>/dev/null
    fi
    
    # Aguardar 30 segundos antes da próxima verificação
    sleep 30
done
