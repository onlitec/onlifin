#!/bin/bash

# Script de Monitoramento de Performance - Onlifin
# Verifica gargalos de performance e sugere otimizações

echo "🔍 Análise de Performance - Onlifin"
echo "=================================="

# 1. Verificar uso de CPU/Memory dos containers
echo ""
echo "📊 Análise de Containers:"
docker stats --no-stream --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.MemPerc}}" | grep -E "(onlifin|prometheus|grafana|loki|promtail)"

# 2. Verificar tamanho do bundle
echo ""
echo "📦 Análise do Bundle:"
if [ -f "dist/assets/index-*.js" ]; then
    BUNDLE_SIZE=$(ls -lh dist/assets/index-*.js | awk '{print $5}')
    echo "Bundle principal: $BUNDLE_SIZE"
    
    # Listar todos os chunks
    echo "Chunks:"
    ls -lh dist/assets/*.js | awk '{print $9, $5}' | sort -k2 -hr
fi

# 3. Verificar se há containers pesados
echo ""
echo "⚠️  Containers Críticos (>5% CPU ou >500MB RAM):"
docker stats --no-stream --format "{{.Container}}: {{.CPUPerc}} CPU, {{.MemUsage}}" | \
  grep -E "(prometheus|grafana|loki|promtail)" | \
  while read line; do
    CPU=$(echo $line | grep -o '[0-9.]*%' | head -1 | sed 's/%//')
    MEM=$(echo $line | grep -o '[0-9.]*[KMGT]B' | head -1)
    
    if (( $(echo "$CPU > 5" | bc -l) )); then
        echo "🔴 ALTA CPU: $line"
    fi
    
    if [[ $MEM == *GB* ]] || [[ $MEM == *MB* && $(echo $MEM | sed 's/MB//' | bc) -gt 500 ]]; then
        echo "🔴 ALTA MEM: $line"
    fi
  done

# 4. Verificar performance do frontend
echo ""
echo "🌐 Performance do Frontend:"
if command -v curl &> /dev/null; then
    # Tempo de resposta
    RESPONSE_TIME=$(curl -o /dev/null -s -w '%{time_total}' http://localhost: 2>/dev/null || echo "N/A")
    echo "Tempo de resposta: ${RESPONSE_TIME}s"
    
    # Tamanho da página
    PAGE_SIZE=$(curl -s -o /dev/null -w '%{size_download}' http://localhost/ 2>/dev/null || echo "N/A")
    if [ "$PAGE_SIZE" != "N/A" ]; then
        echo "Tamanho da página: $(echo "scale=2; $PAGE_SIZE/1024" | bc)KB"
    fi
fi

# 5. Verificar logs de erros
echo ""
echo "📝 Logs de Erro Recentes:"
docker logs onlifin-frontend --tail 10 2>&1 | grep -i error || echo "Sem erros recentes"

# 6. Sugestões de otimização
echo ""
echo "💡 Sugestões de Otimização:"
echo "================================"

# Verificar se Prometheus está usando muita CPU
PROMETHEUS_CPU=$(docker stats --no-stream --format "{{.CPUPerc}}" prometheus 2>/dev/null | sed 's/%//' || echo "0")
if command -v bc &> /dev/null; then
    if (( $(echo "$PROMETHEUS_CPU > 10" | bc -l) )); then
        echo "🔧 Prometheus está usando muita CPU ($PROMETHEUS_CPU%). Considere:"
        echo "   - Reduzir intervalo de scraping"
        echo "   - Desabilitar métricas não essenciais"
        echo "   - Aumentar recursos do container"
    fi
fi

# Verificar se Grafana está usando muita memória
GRAFANA_MEM=$(docker stats --no-stream --format "{{.MemUsage}}" grafana 2>/dev/null | sed 's/MiB//' | sed 's/.* //' || echo "0")
if command -v bc &> /dev/null; then
    if (( $(echo "$GRAFANA_MEM > 1000" | bc -l) )); then
        echo "🔧 Grafana está usando muita memória (${GRAFANA_MEM}MiB). Considere:"
        echo "   - Limpar dashboards não utilizados"
        echo "   - Reduzir retenção de dados"
        echo "   - Otimizar queries dos painéis"
    fi
fi

# Verificar tamanho do bundle
if [ -f "dist/assets/index-*.js" ]; then
    BUNDLE_KB=$(ls -lh dist/assets/index-*.js | awk '{print $5}' | sed 's/[^0-9.]//g')
    if command -v bc &> /dev/null; then
        if (( $(echo "$BUNDLE_KB > 800" | bc -l) )); then
            echo "🔧 Bundle está grande (${BUNDLE_KB}KB). Considere:"
            echo "   - Code splitting adicional"
            echo "   - Remover dependências não utilizadas"
            echo "   - Lazy loading de componentes"
            echo "   - Comprimir imagens"
        fi
    fi
fi

echo ""
echo "✅ Análise concluída!"
echo "📊 Para monitoramento em tempo real: docker stats -i 5"
echo "📈 Para métricas detalhadas: acesse Grafana"
