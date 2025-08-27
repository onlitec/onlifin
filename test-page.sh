#!/bin/bash

echo "🔍 Teste da Página de Login"
echo "=========================="

URL="http://172.20.120.180/login"

echo "📡 Testando conectividade..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" $URL)
echo "   Status HTTP: $HTTP_CODE"

if [ "$HTTP_CODE" = "200" ]; then
    echo "✅ Página carrega corretamente"
    
    echo ""
    echo "🎨 Verificando assets..."
    
    # Verificar CSS
    CSS_CODE=$(curl -s -o /dev/null -w "%{http_code}" "http://172.20.120.180/build/assets/style-1frq-0Ho.css")
    echo "   CSS: $CSS_CODE"
    
    # Verificar JS
    JS_CODE=$(curl -s -o /dev/null -w "%{http_code}" "http://172.20.120.180/build/assets/app-CIJW-j-y.js")
    echo "   JS: $JS_CODE"
    
    # Verificar Vendor JS
    VENDOR_CODE=$(curl -s -o /dev/null -w "%{http_code}" "http://172.20.120.180/build/assets/vendor-BPJhcHQk.js")
    echo "   Vendor JS: $VENDOR_CODE"
    
    echo ""
    echo "🔍 Verificando scripts na página..."
    
    # Verificar se há scripts problemáticos
    PROBLEM_SCRIPTS=$(curl -s $URL | grep -c 'src="http://172.20.120.180"[^/]')
    echo "   Scripts problemáticos: $PROBLEM_SCRIPTS"
    
    if [ "$PROBLEM_SCRIPTS" -eq 0 ]; then
        echo "✅ Nenhum script problemático encontrado"
    else
        echo "❌ Scripts problemáticos encontrados"
        echo "   Listando scripts problemáticos:"
        curl -s $URL | grep 'src="http://172.20.120.180"[^/]' | head -3
    fi
    
    echo ""
    echo "📋 Scripts válidos encontrados:"
    curl -s $URL | grep -o 'src="[^"]*\.js"' | head -3
    
else
    echo "❌ Página não carrega corretamente"
fi

echo ""
echo "🏁 Teste concluído!"
