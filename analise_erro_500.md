# Análise do Erro 500 - Internal Server Error

## Contexto da Aplicação
A aplicação OnLifin é um sistema de gestão financeira pessoal construído em Laravel 11 com PHP 8.2, que inclui:
- Integração com APIs de IA (OpenAI/Anthropic) para análise de extratos bancários
- Sistema de autenticação social (Google, HybridAuth)
- Processamento de arquivos financeiros (OFX, PDF, Excel)
- Interface com Livewire para interações dinâmicas

## Principais Causas Identificadas do Erro 500

### 1. **Problemas de API Key da OpenAI**
**Status:** ⚠️ **CRÍTICO**

Nos logs encontrados em `public/anthropic_error.txt`, foram identificados dois erros graves:

#### Erro de Quota Excedida (HTTP 429):
```
"You exceeded your current quota, please check your plan and billing details."
```

#### Erro de API Key Inválida (HTTP 401):
```
"Incorrect API key provided: export R*******************************************************JouO"
```

**Impacto:** O arquivo `proxy-anthropic.php` não consegue processar extratos bancários, retornando erro 500.

### 2. **Configuração de Ambiente**
O sistema tenta carregar configurações do Laravel mas pode falhar se:
- Arquivo `.env` não estiver configurado corretamente
- Banco de dados não estiver acessível
- Dependências não estiverem instaladas

### 3. **Problemas no Proxy Anthropic**
O arquivo `public/proxy-anthropic.php` (314 linhas) tem várias validações que podem gerar erro 500:

#### Validações que podem falhar:
- **Método HTTP:** Deve ser POST
- **Arquivo de Upload:** Máximo 10MB
- **API Key:** Obrigatória no header `X-API-KEY` ou no banco
- **Timeout:** 120 segundos para processamento

#### Código problemático identificado:
```php
// Linha 25-30: Tentativa de carregar Laravel pode falhar
try {
    require_once __DIR__ . '/../bootstrap/app.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    
    $apiKey = \App\Models\Setting::where('key', 'ai_statement_analyzer_api_key')->value('value');
} catch (\Exception $e) {
    // Se não conseguir carregar o Laravel, continua sem a chave
}
```

### 4. **Dependências e Composer**
A aplicação tem muitas dependências complexas:
- Google Cloud Services (BigQuery, Vision, Storage, etc.)
- Laravel Octane com RoadRunner
- Spatie Laravel Permission
- Livewire 3.6

## Soluções Recomendadas

### 1. **Corrigir Configuração da API OpenAI**
```bash
# Verificar se a API key está configurada
php artisan config:show app.ai_statement_analyzer_api_key

# Atualizar a API key no banco de dados
php artisan tinker
# No tinker:
App\Models\Setting::updateOrCreate(
    ['key' => 'ai_statement_analyzer_api_key'],
    ['value' => 'sua-nova-api-key-aqui']
);
```

### 2. **Verificar Logs do Laravel**
```bash
# Verificar logs detalhados
tail -f storage/logs/laravel.log

# Limpar cache se necessário
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### 3. **Testar Configuração do Banco**
O sistema tem scripts de teste disponíveis:
```bash
php test_db_conn.php
php test_api_key.php
```

### 4. **Verificar Permissões de Arquivos**
```bash
# Executar script de correção de permissões
bash fix_cache_permissions.sh

# Garantir que storage seja gravável
chmod -R 775 storage
chown -R www-data:www-data storage
```

### 5. **Configurar Timeout e Limites PHP**
No arquivo `php.ini` ou `.htaccess`:
```ini
max_execution_time = 300
max_input_time = 300
memory_limit = 512M
upload_max_filesize = 10M
post_max_size = 10M
```

## Monitoramento e Debug

### Habilitar Logs Detalhados
1. No arquivo `.env`:
```env
APP_DEBUG=true
LOG_LEVEL=debug
```

2. Adicionar logging no proxy:
```php
// No proxy-anthropic.php, linha 108
file_put_contents(__DIR__ . '/debug.log', 
    date('Y-m-d H:i:s') . " - REQUEST: " . json_encode($_REQUEST) . "\n", 
    FILE_APPEND
);
```

### Verificar Status dos Serviços
```bash
# Verificar se o PHP-FPM está rodando
sudo systemctl status php8.2-fpm

# Verificar logs do Nginx/Apache
sudo tail -f /var/log/nginx/error.log
```

## Arquivos de Interesse para Investigação
- `public/proxy-anthropic.php` - Proxy principal da API
- `public/anthropic_error.txt` - Logs de erro específicos
- `storage/logs/laravel.log` - Logs gerais do Laravel
- `.env` - Configurações de ambiente
- `config/database.php` - Configuração do banco de dados

## Conclusão
O erro 500 está principalmente relacionado a problemas de configuração da API OpenAI (chave inválida/quota excedida) e possíveis falhas no carregamento do ambiente Laravel. A correção prioritária deve focar na configuração adequada das chaves de API e verificação das dependências do sistema.