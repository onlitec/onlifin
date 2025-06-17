# API de Configurações Múltiplas de IA

Esta documentação descreve como usar a API para gerenciar múltiplas configurações de IA por provedor.

## Endpoints Disponíveis

### 1. Listar Provedores Disponíveis

**GET** `/api/multiple-ai-config/providers`

Retorna todos os provedores disponíveis com suas estatísticas.

```json
{
  "success": true,
  "data": {
    "gemini": {
      "key": "gemini",
      "name": "Google Gemini",
      "models": ["gemini-pro", "gemini-pro-vision"],
      "endpoint": "https://generativelanguage.googleapis.com/v1beta/models",
      "stats": {
        "total_configurations": 3,
        "active_configurations": 2,
        "inactive_configurations": 1,
        "available_models": 2
      }
    }
  }
}
```

### 2. Listar Configurações de um Provedor

**GET** `/api/multiple-ai-config/provider/{provider}/configurations`

Retorna as configurações de um provedor específico.

```json
{
  "success": true,
  "data": {
    "provider": "gemini",
    "provider_name": "Google Gemini",
    "configurations": [
      {
        "id": 1,
        "provider": "gemini",
        "model": "gemini-pro",
        "api_token": "AIza***",
        "system_prompt": "Você é um assistente financeiro...",
        "chat_prompt": "Responda de forma clara...",
        "import_prompt": "Analise esta transação...",
        "is_active": true,
        "created_at": "2025-05-30T10:00:00.000000Z",
        "updated_at": "2025-05-30T10:00:00.000000Z"
      }
    ],
    "available_models": ["gemini-pro", "gemini-pro-vision"]
  }
}
```

### 3. Configurar Múltiplas IAs

**POST** `/api/multiple-ai-config/provider/{provider}/configure`

Configura múltiplas IAs para um provedor.

**Payload:**
```json
{
  "configurations": [
    {
      "model": "gemini-pro",
      "api_token": "AIzaSyC...",
      "system_prompt": "Você é um assistente financeiro especializado.",
      "chat_prompt": "Responda de forma clara e objetiva.",
      "import_prompt": "Analise esta transação financeira.",
      "is_active": true
    },
    {
      "model": "gemini-pro-vision",
      "api_token": "AIzaSyD...",
      "system_prompt": "Você é um assistente para análise de imagens.",
      "is_active": false
    }
  ]
}
```

**Resposta:**
```json
{
  "success": true,
  "message": "2 configuração(ões) processada(s) com sucesso",
  "data": {
    "total_configured": 2,
    "total_errors": 0,
    "configured_models": ["gemini-pro", "gemini-pro-vision"],
    "errors": []
  }
}
```

### 4. Ativar/Desativar Configuração

**PATCH** `/api/multiple-ai-config/provider/{provider}/model/{model}/toggle`

Ativa ou desativa uma configuração específica.

**Payload:**
```json
{
  "is_active": true
}
```

**Resposta:**
```json
{
  "success": true,
  "message": "Configuração ativada com sucesso"
}
```

### 5. Remover Configuração

**DELETE** `/api/multiple-ai-config/provider/{provider}/model/{model}`

Remove uma configuração específica.

**Resposta:**
```json
{
  "success": true,
  "message": "Configuração removida com sucesso"
}
```

### 6. Remover Todas as Configurações de um Provedor

**DELETE** `/api/multiple-ai-config/provider/{provider}/configurations`

Remove todas as configurações de um provedor.

**Resposta:**
```json
{
  "success": true,
  "message": "Removidas 3 configurações do provedor gemini",
  "data": {
    "removed_count": 3
  }
}
```

### 7. Validar Configuração

**POST** `/api/multiple-ai-config/provider/{provider}/validate`

Valida uma configuração antes de salvar.

**Payload:**
```json
{
  "model": "gemini-pro",
  "api_token": "AIzaSyC..."
}
```

**Resposta:**
```json
{
  "success": true,
  "message": "Configuração válida",
  "data": {
    "valid": true,
    "provider": "gemini",
    "model": "gemini-pro",
    "test_result": "API respondeu corretamente"
  }
}
```

### 8. Obter Estatísticas

**GET** `/api/multiple-ai-config/stats`

Retorna estatísticas de todas as configurações.

**Resposta:**
```json
{
  "success": true,
  "data": {
    "gemini": {
      "total_configurations": 3,
      "active_configurations": 2,
      "inactive_configurations": 1,
      "available_models": 2
    },
    "openai": {
      "total_configurations": 1,
      "active_configurations": 1,
      "inactive_configurations": 0,
      "available_models": 5
    }
  }
}
```

## Exemplos de Uso

### Configurar Múltiplas IAs do Gemini

```bash
curl -X POST \
  http://localhost/api/multiple-ai-config/provider/gemini/configure \
  -H 'Content-Type: application/json' \
  -H 'X-CSRF-TOKEN: your-csrf-token' \
  -d '{
    "configurations": [
      {
        "model": "gemini-pro",
        "api_token": "AIzaSyC...",
        "system_prompt": "Você é um assistente financeiro.",
        "is_active": true
      },
      {
        "model": "gemini-pro-vision",
        "api_token": "AIzaSyD...",
        "system_prompt": "Você analisa imagens financeiras.",
        "is_active": false
      }
    ]
  }'
```

### Listar Configurações do OpenAI

```bash
curl -X GET \
  http://localhost/api/multiple-ai-config/provider/openai/configurations
```

### Ativar uma Configuração

```bash
curl -X PATCH \
  http://localhost/api/multiple-ai-config/provider/gemini/model/gemini-pro-vision/toggle \
  -H 'Content-Type: application/json' \
  -H 'X-CSRF-TOKEN: your-csrf-token' \
  -d '{
    "is_active": true
  }'
```

## Interface Web

A interface web está disponível em:
- **Principal**: `http://dev.onlifin.onlitec.com.br/multiple-ai-config`
- **Por Provedor**: `http://dev.onlifin.onlitec.com.br/multiple-ai-config/provider/{provider}`

Também integrada na página de configurações de IA:
- **Configurações de IA**: `http://dev.onlifin.onlitec.com.br/iaprovider-config`

### Página Principal

Acesse `/multiple-ai-config` para ver a interface principal com:
- Lista de todos os provedores
- Estatísticas gerais
- Botões para configurar cada provedor

### Página do Provedor

Acesse `/multiple-ai-config/provider/{provider}` para:
- Ver todas as configurações do provedor
- Adicionar novas configurações
- Ativar/desativar configurações
- Remover configurações

## Códigos de Status HTTP

- `200` - Sucesso
- `207` - Multi-Status (sucesso parcial com alguns erros)
- `404` - Recurso não encontrado
- `422` - Dados inválidos
- `500` - Erro interno do servidor

## Tratamento de Erros

Todos os endpoints retornam erros no formato:

```json
{
  "success": false,
  "message": "Descrição do erro",
  "errors": {
    "campo": ["Mensagem de validação"]
  }
}
```

## Segurança

- Todos os endpoints POST/PATCH/DELETE requerem token CSRF
- Tokens de API são mascarados na resposta (apenas primeiros e últimos 4 caracteres visíveis)
- Validação de entrada em todos os endpoints
- Verificação de existência de provedor e modelo