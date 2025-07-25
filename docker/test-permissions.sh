#!/bin/bash

# Script para testar permissões no container
echo "🔍 Testando permissões do container..."

# Verificar se os diretórios existem
echo "📁 Verificando diretórios..."
ls -la /var/www/html/storage/
ls -la /var/www/html/storage/framework/
ls -la /var/www/html/bootstrap/

# Testar escrita no diretório views
echo "✍️ Testando escrita em views..."
TEST_FILE="/var/www/html/storage/framework/views/test-permissions.txt"
if echo "teste" > "$TEST_FILE" 2>/dev/null; then
    echo "✅ Escrita em views: OK"
    rm -f "$TEST_FILE"
else
    echo "❌ Escrita em views: FALHOU"
    echo "Permissões atuais:"
    ls -la /var/www/html/storage/framework/views/
fi

# Testar escrita no diretório cache
echo "✍️ Testando escrita em cache..."
TEST_FILE="/var/www/html/storage/framework/cache/test-permissions.txt"
if echo "teste" > "$TEST_FILE" 2>/dev/null; then
    echo "✅ Escrita em cache: OK"
    rm -f "$TEST_FILE"
else
    echo "❌ Escrita em cache: FALHOU"
    echo "Permissões atuais:"
    ls -la /var/www/html/storage/framework/cache/
fi

# Testar escrita no bootstrap/cache
echo "✍️ Testando escrita em bootstrap/cache..."
TEST_FILE="/var/www/html/bootstrap/cache/test-permissions.txt"
if echo "teste" > "$TEST_FILE" 2>/dev/null; then
    echo "✅ Escrita em bootstrap/cache: OK"
    rm -f "$TEST_FILE"
else
    echo "❌ Escrita em bootstrap/cache: FALHOU"
    echo "Permissões atuais:"
    ls -la /var/www/html/bootstrap/cache/
fi

echo "🏁 Teste de permissões concluído!"
