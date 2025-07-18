#!/bin/bash

# Daemon para monitorar e corrigir permissões automaticamente
# Executa a cada 5 minutos para garantir que as permissões estejam sempre corretas

echo "🔧 Iniciando daemon de correção de permissões..."

while true; do
    # Verificar se os diretórios críticos têm permissões corretas
    if [ ! -w "/var/www/html/storage/framework/views" ] || [ ! -w "/var/www/html/storage/framework/cache" ]; then
        echo "⚠️  Permissões incorretas detectadas. Corrigindo..."
        
        # Corrigir permissões
        chown -R www:www /var/www/html/storage
        chmod -R 775 /var/www/html/storage
        chown -R www:www /var/www/html/bootstrap/cache
        chmod -R 775 /var/www/html/bootstrap/cache
        
        echo "✅ Permissões corrigidas automaticamente."
    fi
    
    # Aguardar 5 minutos antes da próxima verificação
    sleep 300
done
