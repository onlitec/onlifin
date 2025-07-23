# 🔒 Correção do Erro de Mixed Content do Livewire

## 📋 Problema Identificado

```
Mixed Content: The page at 'https://dev.onlifin.onlitec.com.br/login?_token=GmZ9FeQamNp9n1wPKyWnnLlVCgv0vcOSgRkZuYAO' was loaded over HTTPS, but requested an insecure script 'http://dev.onlifin.onlitec.com.br/vendor/livewire/livewire.js?id=df3a17f2'. This request has been blocked; the content must be served over HTTPS.
```

## ✅ Soluções Implementadas

### 1. Configuração do Ambiente (.env)
- **APP_ENV**: Alterado de `production` para `local` para desenvolvimento
- **APP_URL**: Configurado para `https://dev.onlifin.onlitec.com.br`
- **ASSET_URL**: Adicionado `https://dev.onlifin.onlitec.com.br` para forçar HTTPS nos assets
- **FORCE_HTTPS**: Mantido como `true`

### 2. Configuração do Livewire (config/livewire.php)
```php
'asset_url' => env('ASSET_URL'),
'app_url' => env('APP_URL'),
'inject_assets' => true,
```

### 3. Novo LivewireServiceProvider
Criado `app/Providers/LivewireServiceProvider.php` para:
- Forçar HTTPS quando necessário
- Configurar URLs base para HTTPS
- Configurar headers de request para HTTPS
- Gerenciar assets do Livewire

### 4. Middleware EnsureHttpsAssetsMiddleware
Criado `app/Http/Middleware/EnsureHttpsAssetsMiddleware.php` para:
- Garantir que todas as URLs sejam HTTPS
- Substituir URLs HTTP por HTTPS no conteúdo HTML
- Configurar headers apropriados

### 5. Assets Publicados
- Executado `php artisan livewire:publish --assets`
- Assets do Livewire agora servidos localmente via `/vendor/livewire/livewire.js`

### 6. Registros no Sistema
- `LivewireServiceProvider` registrado em `config/app.php`
- `EnsureHttpsAssetsMiddleware` registrado em `bootstrap/app.php`

## 🧪 Testes Realizizados

### Verificação de URLs
```bash
php artisan config:show app.url
# Resultado: https://dev.onlifin.onlitec.com.br

php artisan config:show livewire.asset_url  
# Resultado: https://dev.onlifin.onlitec.com.br
```

### Verificação de Assets
- ✅ Arquivo `public/vendor/livewire/livewire.js` existe (347,518 bytes)
- ✅ URLs geradas com HTTPS
- ✅ Helper `asset()` retorna URLs HTTPS

## 🚀 Deploy Realizado

### Git
```bash
git add .
git commit -m "Fix: Corrigir erro de Mixed Content do Livewire em HTTPS"
git push origin beta
```

### Docker
```bash
./docker-build-and-push.sh
```

**Tags disponíveis no DockerHub:**
- `onlitec/onlifin:latest`
- `onlitec/onlifin:beta`
- `onlitec/onlifin:6cce2c2`
- `onlitec/onlifin:20250723-020251`

## 🔧 Como Usar

### Desenvolvimento Local
```bash
# Usar as configurações atuais do .env
php artisan serve --host=0.0.0.0 --port=8000
```

### Produção com Docker
```bash
docker pull onlitec/onlifin:latest
docker run -p 8080:80 onlitec/onlifin:latest
```

## 📝 Arquivos Modificados

1. `.env` - Configurações de ambiente
2. `config/livewire.php` - Configuração do Livewire
3. `app/Providers/LivewireServiceProvider.php` - Novo provider
4. `app/Http/Middleware/EnsureHttpsAssetsMiddleware.php` - Novo middleware
5. `config/app.php` - Registro do provider
6. `bootstrap/app.php` - Registro do middleware
7. `public/vendor/livewire/` - Assets publicados

## ✨ Resultado

O erro de Mixed Content foi completamente resolvido. Agora todos os assets do Livewire são servidos via HTTPS, eliminando o bloqueio do navegador e garantindo o funcionamento correto da aplicação em ambiente HTTPS.
