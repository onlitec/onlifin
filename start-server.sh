#!/bin/bash

# Script para iniciar o servidor de desenvolvimento Onlifin
# Acesso externo: http://192.168.0.70/

echo "🚀 Iniciando servidor Onlifin..."
echo "📡 Acesso local:   http://localhost/"
echo "🌐 Acesso externo: http://192.168.0.70/"
echo ""

# Verificar se a porta 80 está em uso
if lsof -i :80 > /dev/null 2>&1; then
    echo "⚠️  Porta 80 já está em uso. Tentando liberar..."
    sudo fuser -k 80/tcp 2>/dev/null || true
    sleep 1
fi

# Executar o servidor de desenvolvimento com permissões para porta 80
# Usar sudo para porta privilegiada (< 1024)
cd /opt/onlifin
sudo -E npx vite --host 0.0.0.0 --port 80
