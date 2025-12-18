#!/bin/bash
# ===========================================
# Onlifin - Script de Inicialização Ollama
# ===========================================
# Baixa e configura o modelo de IA para análise financeira

set -e

# Cores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

MODEL="${OLLAMA_MODEL:-llama3.2:3b}"

echo -e "${YELLOW}🤖 Configurando Ollama para Onlifin${NC}"
echo "Modelo: $MODEL"
echo ""

# Aguardar Ollama estar pronto
echo "Aguardando Ollama iniciar..."
until curl -s http://localhost:11434/api/tags > /dev/null 2>&1; do
    sleep 2
done
echo -e "${GREEN}✓ Ollama está rodando${NC}"

# Verificar se o modelo já existe
if curl -s http://localhost:11434/api/tags | grep -q "$MODEL"; then
    echo -e "${GREEN}✓ Modelo $MODEL já instalado${NC}"
else
    echo "Baixando modelo $MODEL (pode demorar alguns minutos)..."
    curl -X POST http://localhost:11434/api/pull -d "{\"name\": \"$MODEL\"}"
    echo -e "${GREEN}✓ Modelo $MODEL instalado${NC}"
fi

# Teste rápido
echo ""
echo "Testando modelo..."
RESPONSE=$(curl -s http://localhost:11434/api/generate -d '{
    "model": "'"$MODEL"'",
    "prompt": "Responda em uma frase: O que é gestão financeira pessoal?",
    "stream": false
}' | jq -r '.response')

echo -e "${GREEN}✓ Resposta do modelo:${NC}"
echo "$RESPONSE"
echo ""
echo -e "${GREEN}✅ Ollama configurado com sucesso!${NC}"
