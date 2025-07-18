# Solução para Erro 504 (Gateway Time-out) na Análise com IA

## Problema Identificado

O erro 504 estava ocorrendo na rota `/transactions/mapping?use_ai=1` devido a timeouts em múltiplas camadas:

1. **Timeout do Nginx**: `fastcgi_read_timeout = 300s` (5 minutos)
2. **Timeout do PHP**: `max_execution_time = 300s` (5 minutos)
3. **Chamadas HTTP sem timeout**: APIs de IA sem limite de tempo
4. **Prompts muito grandes**: Até 50 transações sendo analisadas
5. **Dupla análise**: Análise prévia + análise principal sequencial

## Soluções Implementadas

### 1. Timeouts nas Chamadas HTTP de IA

**Arquivo**: `app/Services/AIService.php`

- Adicionado `Http::timeout(120)` em todas as chamadas para APIs de IA
- Timeout de 2 minutos para evitar chamadas pendentes indefinidamente

### 2. Middleware para Aumentar Timeout

**Arquivo**: `app/Http/Middleware/IncreaseTimeoutForAIAnalysis.php`

- Middleware específico para rotas com IA
- Aumenta `max_execution_time` para 5 minutos quando `use_ai=1`
- Aplicado na rota `/transactions/mapping`

### 3. Otimização de Prompts

**Arquivo**: `app/Http/Controllers/TempStatementImportController.php`

- Reduzido limite de transações de 50 para 25
- Análise prévia limitada a 15 transações
- Transações existentes limitadas a 50 (últimos 30 dias)
- Categorias limitadas a 100

### 4. Análise Prévia Condicional

- Análise prévia só executa quando `use_ai=0`
- Evita dupla chamada de IA quando IA principal está ativa
- Timeout de 2 minutos para análise prévia

### 5. Timeout Explícito no Controller

- `set_time_limit(180)` no método `analyzeTransactionsWithAI`
- `set_time_limit(120)` no método `performPreAnalysisWithAI`

## Configurações do Servidor

### Nginx (já configurado)
```nginx
fastcgi_read_timeout 300;
```

### PHP (já configurado)
```ini
max_execution_time = 300
```

## Como Testar

1. **Teste com IA ativada**:
   ```
   http://dev.onlifin.onlitec.com.br/transactions/mapping?path=temp_uploads%2FEDeCsiUg29bYoFDhsf7qhyHOn12MPwlssqISmbK2.txt&account_id=2&extension=ofx&use_ai=1
   ```

2. **Teste sem IA**:
   ```
   http://dev.onlifin.onlitec.com.br/transactions/mapping?path=temp_uploads%2FEDeCsiUg29bYoFDhsf7qhyHOn12MPwlssqISmbK2.txt&account_id=2&extension=ofx&use_ai=0
   ```

## Monitoramento

### Logs para Verificar

1. **Laravel logs**:
   ```bash
   tail -f storage/logs/laravel-$(date +%Y-%m-%d).log
   ```

2. **Nginx error logs**:
   ```bash
   sudo tail -f /var/log/nginx/error.log
   ```

### Indicadores de Sucesso

- Logs mostrando "Timeout aumentado para análise com IA"
- Tempo de execução menor que 3 minutos
- Sem erros 504 nos logs do Nginx

## Fallbacks Implementados

1. **Resposta simulada**: Se IA falhar, usa dados simulados
2. **Fallback entre provedores**: Se um provedor falhar, tenta o próximo
3. **Limite de transações**: Se muitas transações, processa em lotes

## Próximos Passos (se necessário)

1. **Aumentar timeout do Nginx** para 600s se ainda houver problemas
2. **Implementar processamento assíncrono** com filas
3. **Cache de resultados** de IA para transações similares
4. **Otimização de prompts** com compressão de dados

## Comandos para Aplicar Mudanças

```bash
# Limpar cache do Laravel
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Reiniciar serviços (se necessário)
sudo systemctl reload nginx
sudo systemctl reload php8.2-fpm
``` 