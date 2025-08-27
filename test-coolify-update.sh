#!/bin/bash

echo "🔍 Testando se o Coolify foi atualizado..."

URL="http://172.20.120.180"

echo "📡 Testando conectividade básica..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" $URL)
echo "   Status HTTP: $HTTP_CODE"

if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "302" ]; then
    echo "✅ Servidor responde"
    
    echo "🎨 Testando assets..."
    # Tentar acessar alguns assets comuns
    CSS_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$URL/build/assets/app.css" 2>/dev/null || echo "404")
    JS_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$URL/build/assets/app.js" 2>/dev/null || echo "404")
    
    echo "   CSS: $CSS_CODE"
    echo "   JS: $JS_CODE"
    
    if [ "$CSS_CODE" != "000" ] && [ "$JS_CODE" != "000" ]; then
        echo "✅ Assets parecem estar carregando"
        
        echo "🔐 Testando página de login..."
        LOGIN_CONTENT=$(curl -s $URL/login 2>/dev/null | grep -i "login\|email\|password" | wc -l)
        
        if [ "$LOGIN_CONTENT" -gt 0 ]; then
            echo "✅ Página de login carrega corretamente"
            echo ""
            echo "🎉 ATUALIZAÇÃO PARECE TER FUNCIONADO!"
            echo ""
            echo "🌐 Acesse: $URL"
            echo "🔐 Login: admin@onlifin.com / admin123"
            echo ""
            echo "📋 Se ainda não conseguir fazer login:"
            echo "   1. Aguarde mais alguns minutos"
            echo "   2. Limpe cache do navegador"
            echo "   3. Tente em aba anônima"
        else
            echo "⚠️ Página de login não carrega corretamente"
        fi
    else
        echo "❌ Assets ainda não carregam (ERR_CONNECTION_REFUSED)"
        echo "   Aguarde mais alguns minutos ou verifique o deploy"
    fi
else
    echo "❌ Servidor não responde corretamente"
    echo "   Verifique se o deploy foi concluído"
fi

echo ""
echo "📊 Resumo do teste:"
echo "   URL: $URL"
echo "   HTTP: $HTTP_CODE"
echo "   Timestamp: $(date)"
