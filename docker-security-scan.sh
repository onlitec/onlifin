#!/bin/bash

# Script para verificação de segurança da imagem Docker
# Realiza scans de vulnerabilidades e verifica configurações de segurança

echo "🔒 Onlifin Docker Security Scanner"
echo "================================="
echo ""

# Verificar se a imagem existe
if ! docker images | grep -q "onlitec/onlifin"; then
    echo "❌ Imagem onlitec/onlifin não encontrada"
    echo "   Execute: docker-compose build"
    exit 1
fi

IMAGE_NAME="onlitec/onlifin:latest"
echo "🔍 Analisando imagem: $IMAGE_NAME"
echo ""

# 1. Verificar informações básicas da imagem
echo "📊 Informações da Imagem:"
echo "========================"
docker images --format "table {{.Repository}}\t{{.Tag}}\t{{.Size}}\t{{.CreatedAt}}" | grep onlitec/onlifin
echo ""

# 2. Verificar labels OCI
echo "🏷️  Labels OCI:"
echo "==============="
docker inspect $IMAGE_NAME --format='{{range $k, $v := .Config.Labels}}{{$k}}: {{$v}}{{"\n"}}{{end}}' | head -10
echo ""

# 3. Verificar usuário não-root
echo "👤 Verificação de Usuário:"
echo "========================="
USER_CHECK=$(docker inspect $IMAGE_NAME --format='{{.Config.User}}')
if [ -n "$USER_CHECK" ] && [ "$USER_CHECK" != "root" ]; then
    echo "✅ Imagem configurada para usuário não-root: $USER_CHECK"
else
    echo "⚠️  Imagem pode estar executando como root"
fi
echo ""

# 4. Verificar portas expostas
echo "🔌 Portas Expostas:"
echo "=================="
EXPOSED_PORTS=$(docker inspect $IMAGE_NAME --format='{{range $port, $config := .Config.ExposedPorts}}{{$port}} {{end}}')
if [ -n "$EXPOSED_PORTS" ]; then
    echo "✅ Portas expostas: $EXPOSED_PORTS"
else
    echo "⚠️  Nenhuma porta explicitamente exposta"
fi
echo ""

# 5. Verificar variáveis de ambiente sensíveis
echo "🔐 Verificação de Variáveis Sensíveis:"
echo "====================================="
SENSITIVE_VARS=$(docker inspect $IMAGE_NAME --format='{{range .Config.Env}}{{.}}{{"\n"}}{{end}}' | grep -iE "(password|secret|key|token)" || true)
if [ -z "$SENSITIVE_VARS" ]; then
    echo "✅ Nenhuma variável sensível encontrada na imagem"
else
    echo "⚠️  Variáveis sensíveis encontradas:"
    echo "$SENSITIVE_VARS"
fi
echo ""

# 6. Verificar tamanho da imagem
echo "📏 Análise de Tamanho:"
echo "====================="
IMAGE_SIZE=$(docker images --format "{{.Size}}" $IMAGE_NAME)
echo "Tamanho da imagem: $IMAGE_SIZE"

# Converter para MB para comparação
SIZE_MB=$(docker images --format "{{.Size}}" $IMAGE_NAME | sed 's/GB/000MB/' | sed 's/MB//' | cut -d'.' -f1)
if [ "$SIZE_MB" -gt 2000 ]; then
    echo "⚠️  Imagem grande (>2GB) - considere otimização"
elif [ "$SIZE_MB" -gt 1000 ]; then
    echo "⚠️  Imagem média (>1GB) - pode ser otimizada"
else
    echo "✅ Tamanho da imagem aceitável"
fi
echo ""

# 7. Verificar base image
echo "🏗️  Imagem Base:"
echo "==============="
BASE_IMAGE=$(docker history $IMAGE_NAME --format "{{.CreatedBy}}" | tail -1 | grep -oE 'FROM [^[:space:]]+' | cut -d' ' -f2 || echo "Não identificada")
echo "Imagem base: $BASE_IMAGE"

if echo "$BASE_IMAGE" | grep -qE "(alpine|ubuntu|debian)"; then
    echo "✅ Usando imagem base oficial"
else
    echo "⚠️  Verifique se a imagem base é confiável"
fi
echo ""

# 8. Scan de vulnerabilidades (se disponível)
echo "🛡️  Scan de Vulnerabilidades:"
echo "============================="

# Tentar usar docker scan (se disponível)
if command -v docker &> /dev/null && docker scan --help &> /dev/null; then
    echo "Executando docker scan..."
    docker scan $IMAGE_NAME --severity high || echo "⚠️  Docker scan não disponível ou falhou"
else
    echo "⚠️  Docker scan não disponível"
    echo "   Instale: https://docs.docker.com/engine/scan/"
fi

# Tentar usar trivy (se disponível)
if command -v trivy &> /dev/null; then
    echo ""
    echo "Executando Trivy scan..."
    trivy image --severity HIGH,CRITICAL $IMAGE_NAME
else
    echo "⚠️  Trivy não disponível"
    echo "   Instale: https://aquasecurity.github.io/trivy/"
fi

echo ""

# 9. Verificar configurações de runtime
echo "⚙️  Configurações de Runtime:"
echo "============================"
echo "Verificando container em execução..."

CONTAINER_ID=$(docker ps --filter "ancestor=$IMAGE_NAME" --format "{{.ID}}" | head -1)
if [ -n "$CONTAINER_ID" ]; then
    echo "✅ Container encontrado: $CONTAINER_ID"
    
    # Verificar processos
    echo ""
    echo "Processos em execução:"
    docker exec $CONTAINER_ID ps aux | head -5
    
    # Verificar usuário dos processos
    echo ""
    echo "Usuários dos processos:"
    docker exec $CONTAINER_ID ps -eo user | sort | uniq -c
    
else
    echo "⚠️  Nenhum container em execução encontrado"
    echo "   Execute: docker-compose up -d"
fi

echo ""
echo "🎯 Recomendações de Segurança:"
echo "=============================="
echo "✅ Use sempre tags específicas em produção"
echo "✅ Execute containers como usuário não-root"
echo "✅ Mantenha imagens base atualizadas"
echo "✅ Remova pacotes desnecessários"
echo "✅ Use multi-stage builds para reduzir tamanho"
echo "✅ Escaneie regularmente por vulnerabilidades"
echo "✅ Use secrets management para dados sensíveis"
echo "✅ Configure resource limits"
echo ""

echo "📋 Relatório completo salvo em: docker-security-report.txt"

# Salvar relatório
{
    echo "Onlifin Docker Security Report"
    echo "Generated: $(date)"
    echo "Image: $IMAGE_NAME"
    echo ""
    echo "=== IMAGE INFO ==="
    docker images --format "table {{.Repository}}\t{{.Tag}}\t{{.Size}}\t{{.CreatedAt}}" | grep onlitec/onlifin
    echo ""
    echo "=== LABELS ==="
    docker inspect $IMAGE_NAME --format='{{range $k, $v := .Config.Labels}}{{$k}}: {{$v}}{{"\n"}}{{end}}'
    echo ""
    echo "=== USER CHECK ==="
    echo "User: $(docker inspect $IMAGE_NAME --format='{{.Config.User}}')"
    echo ""
    echo "=== EXPOSED PORTS ==="
    docker inspect $IMAGE_NAME --format='{{range $port, $config := .Config.ExposedPorts}}{{$port}} {{end}}'
} > docker-security-report.txt

echo "✅ Scan de segurança concluído!"
