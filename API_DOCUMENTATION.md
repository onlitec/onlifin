# Onlifin API - Documentação para App Android

## Visão Geral

A API Onlifin fornece acesso completo aos dados financeiros da plataforma para desenvolvimento do aplicativo Android. A API é baseada em REST, utiliza autenticação via tokens Bearer (Laravel Sanctum) e retorna dados em formato JSON.

## Base URL

```
Produção: http://172.20.120.180:8080/api
Desenvolvimento: http://localhost:8080/api
```

## Autenticação

A API utiliza Laravel Sanctum para autenticação via tokens Bearer. Todos os endpoints protegidos requerem o header:

```
Authorization: Bearer {token}
```

### Fluxo de Autenticação

1. **Registro/Login** → Recebe token de acesso
2. **Usar token** em todas as requisições protegidas
3. **Refresh token** quando necessário
4. **Logout** para revogar token

## Endpoints Principais

### 🔐 Autenticação

#### POST /auth/register
Registra novo usuário
```json
{
  "name": "João Silva",
  "email": "joao@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "device_name": "Android App"
}
```

#### POST /auth/login
Autentica usuário
```json
{
  "email": "joao@example.com",
  "password": "password123",
  "device_name": "Android App"
}
```

#### GET /auth/me
Informações do usuário autenticado

#### POST /auth/logout
Logout (revoga token atual)

#### POST /auth/refresh
Renova token atual

### 💰 Transações

#### GET /transactions
Lista transações do usuário
- Parâmetros: `type`, `status`, `account_id`, `category_id`, `date_from`, `date_to`, `search`, `per_page`, `page`

#### POST /transactions
Cria nova transação
```json
{
  "type": "expense",
  "status": "paid",
  "date": "2024-01-15",
  "description": "Supermercado",
  "amount": 150.75,
  "category_id": 1,
  "account_id": 1,
  "notes": "Compras da semana"
}
```

#### GET /transactions/{id}
Detalhes de transação específica

#### PUT /transactions/{id}
Atualiza transação

#### DELETE /transactions/{id}
Exclui transação

#### GET /transactions/summary
Resumo financeiro das transações

### 🏦 Contas

#### GET /accounts
Lista contas do usuário

#### POST /accounts
Cria nova conta
```json
{
  "name": "Conta Corrente",
  "type": "checking",
  "initial_balance": 1000.00,
  "description": "Conta principal",
  "color": "#3498db"
}
```

#### GET /accounts/{id}
Detalhes de conta específica

#### PUT /accounts/{id}
Atualiza conta

#### DELETE /accounts/{id}
Exclui conta

#### GET /accounts/summary
Resumo de todas as contas

### 📊 Categorias

#### GET /categories
Lista categorias do usuário
- Parâmetros: `type` (income/expense), `with_stats`

#### POST /categories
Cria nova categoria
```json
{
  "name": "Alimentação",
  "type": "expense",
  "color": "#e74c3c",
  "icon": "fa-utensils",
  "description": "Gastos com alimentação"
}
```

#### GET /categories/{id}
Detalhes de categoria específica

#### PUT /categories/{id}
Atualiza categoria

#### DELETE /categories/{id}
Exclui categoria

#### GET /categories/stats
Estatísticas das categorias

### 📈 Relatórios

#### GET /reports/dashboard
Dashboard com resumo geral

#### GET /reports/cash-flow
Relatório de fluxo de caixa
- Parâmetros: `date_from`, `date_to`, `group_by` (day/week/month), `account_id`

#### GET /reports/by-category
Relatório por categorias
- Parâmetros: `date_from`, `date_to`, `type`, `limit`

#### GET /reports/by-account
Relatório por contas
- Parâmetros: `date_from`, `date_to`, `include_inactive`

### ⚙️ Configurações

#### GET /settings
Configurações do usuário

#### PUT /settings/profile
Atualiza perfil
```json
{
  "name": "João Silva Santos",
  "phone": "(11) 99999-9999"
}
```

#### POST /settings/profile/photo
Upload de foto de perfil (multipart/form-data)

#### PUT /settings/password
Altera senha
```json
{
  "current_password": "senhaatual",
  "password": "novasenha123",
  "password_confirmation": "novasenha123"
}
```

#### PUT /settings/notifications
Configurações de notificação
```json
{
  "email_notifications": true,
  "push_notifications": true,
  "whatsapp_notifications": false,
  "due_date_notifications": true
}
```

### 🤖 Inteligência Artificial

#### POST /ai/chat
Chat com IA financeira
```json
{
  "message": "Como posso economizar mais dinheiro?",
  "context": {}
}
```

#### POST /ai/analysis
Análise financeira inteligente
```json
{
  "period": "month",
  "analysis_type": "spending"
}
```

#### POST /ai/categorization
Sugestões de categorização
```json
{
  "description": "Pagamento cartão de crédito",
  "amount": 500.00,
  "type": "expense"
}
```

#### GET /ai/insights
Insights financeiros personalizados

## Formato de Resposta

### Sucesso
```json
{
  "success": true,
  "data": {
    // dados da resposta
  },
  "timestamp": "2024-01-15T10:30:00.000000Z"
}
```

### Erro
```json
{
  "success": false,
  "message": "Mensagem de erro",
  "errors": {
    // detalhes dos erros de validação
  },
  "timestamp": "2024-01-15T10:30:00.000000Z"
}
```

## Códigos de Status HTTP

- `200` - Sucesso
- `201` - Criado com sucesso
- `400` - Requisição inválida
- `401` - Não autorizado (token inválido/expirado)
- `403` - Proibido (sem permissão)
- `404` - Não encontrado
- `422` - Erro de validação
- `429` - Muitas requisições (rate limit)
- `500` - Erro interno do servidor

## Rate Limiting

- **Usuários autenticados**: 60 requisições por minuto
- **Usuários não autenticados**: 10 requisições por minuto

Headers de resposta:
- `X-RateLimit-Limit`: Limite total
- `X-RateLimit-Remaining`: Requisições restantes
- `Retry-After`: Segundos para tentar novamente (quando limitado)

## CORS

A API está configurada para aceitar requisições de aplicações Android. Headers CORS incluem:
- `Access-Control-Allow-Origin`
- `Access-Control-Allow-Methods`
- `Access-Control-Allow-Headers`

## Paginação

Endpoints que retornam listas incluem paginação:

```json
{
  "success": true,
  "data": {
    "transactions": [...],
    "pagination": {
      "current_page": 1,
      "last_page": 5,
      "per_page": 15,
      "total": 73,
      "from": 1,
      "to": 15
    }
  }
}
```

## Tratamento de Erros

### Erros de Validação (422)
```json
{
  "success": false,
  "message": "Dados inválidos",
  "errors": {
    "email": ["O campo email é obrigatório."],
    "password": ["O campo password deve ter pelo menos 8 caracteres."]
  }
}
```

### Token Expirado (401)
```json
{
  "success": false,
  "message": "Token inválido ou expirado"
}
```

## Valores Monetários

- **Entrada**: Valores em reais (ex: 150.75)
- **Saída**: Valores em reais com formatação (ex: "R$ 150,75")
- **Armazenamento**: Valores em centavos internamente

## Datas

- **Formato**: ISO 8601 (YYYY-MM-DD ou YYYY-MM-DDTHH:MM:SS.000000Z)
- **Timezone**: UTC para timestamps, local para datas

## Testes

Para testar a API, você pode usar:

```bash
# Registro
curl -X POST "http://172.20.120.180:8080/api/auth/register" \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"test@example.com","password":"password123","password_confirmation":"password123","device_name":"Test"}'

# Login
curl -X POST "http://172.20.120.180:8080/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password123","device_name":"Test"}'

# Usar token retornado
curl -X GET "http://172.20.120.180:8080/api/auth/me" \
  -H "Authorization: Bearer {seu_token_aqui}"
```

## Documentação Interativa

Acesse a documentação interativa em:
- http://172.20.120.180:8080/api/docs

## Suporte

Para dúvidas sobre a API, entre em contato com a equipe de desenvolvimento.
