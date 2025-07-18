# Configuração da Autenticação Google e 2FA

Este documento explica como configurar a autenticação via Google e a autenticação em duas etapas (2FA) no sistema Onlifin.

## 1. Configuração do Google OAuth

### 1.1 Criar projeto no Google Cloud Console

1. Acesse o [Google Cloud Console](https://console.cloud.google.com/)
2. Crie um novo projeto ou selecione um existente
3. Vá para "APIs e Serviços" > "Credenciais"
4. Clique em "Criar credenciais" > "ID do cliente OAuth 2.0"
5. Configure a tela de consentimento OAuth se necessário
6. Selecione "Aplicativo da Web" como tipo de aplicativo
7. Adicione as URLs de redirecionamento autorizadas:
   - Para desenvolvimento: `http://localhost:8000/auth/google/callback`
   - Para produção: `https://seudominio.com/auth/google/callback`

### 1.2 Configurar variáveis de ambiente

Adicione as seguintes variáveis ao seu arquivo `.env`:

```env
# Google OAuth Configuration
GOOGLE_CLIENT_ID=your_google_client_id_here
GOOGLE_CLIENT_SECRET=your_google_client_secret_here
GOOGLE_REDIRECT_URI=${APP_URL}/auth/google/callback
```

Substitua `your_google_client_id_here` e `your_google_client_secret_here` pelos valores obtidos no Google Cloud Console.

## 2. Funcionalidades Implementadas

### 2.1 Autenticação Google

- **Login via Google**: Usuários podem fazer login usando suas contas Google
- **Registro automático**: Novos usuários são criados automaticamente no primeiro login
- **Vinculação de contas**: Contas existentes podem ser vinculadas ao Google
- **Integração com 2FA**: Login via Google respeita configurações de 2FA

### 2.2 Autenticação em Duas Etapas (2FA)

- **Configuração via QR Code**: Suporte a aplicativos como Google Authenticator, Authy, etc.
- **Códigos de recuperação**: 8 códigos únicos para acesso de emergência
- **Gerenciamento completo**: Ativar, desativar e regenerar códigos
- **Integração total**: Funciona com login normal e Google OAuth

## 3. Rotas Disponíveis

### 3.1 Rotas de Autenticação Google

```php
// Redirecionar para Google OAuth
GET /auth/google

// Callback do Google OAuth
GET /auth/google/callback

// Desvincular conta Google (autenticado)
POST /auth/google/unlink
```

### 3.2 Rotas de 2FA

```php
// Verificação de código 2FA
GET /2fa/verify
POST /2fa/verify

// Verificação com código de recuperação
GET /2fa/recovery
POST /2fa/recovery

// Configuração de 2FA (autenticado)
GET /2fa/setup
POST /2fa/setup

// Desativar 2FA (autenticado)
POST /2fa/disable

// Gerar novos códigos de recuperação (autenticado)
POST /2fa/recovery-codes
```

## 4. Fluxo de Autenticação

### 4.1 Login Normal com 2FA

1. Usuário insere email e senha
2. Sistema valida credenciais
3. Se 2FA estiver ativado, redireciona para verificação
4. Usuário insere código do aplicativo ou código de recuperação
5. Login é completado

### 4.2 Login via Google com 2FA

1. Usuário clica em "Entrar com Google"
2. É redirecionado para o Google OAuth
3. Após autorização, retorna ao sistema
4. Se 2FA estiver ativado, redireciona para verificação
5. Login é completado

## 5. Campos Adicionados ao Banco de Dados

### 5.1 Tabela `users`

```sql
-- Campos para Google OAuth
google_id VARCHAR(255) NULL
google_avatar VARCHAR(255) NULL

-- Campos para 2FA
two_factor_enabled BOOLEAN DEFAULT FALSE
two_factor_secret VARCHAR(255) NULL
two_factor_confirmed_at TIMESTAMP NULL
two_factor_recovery_codes TEXT NULL

-- Índices
INDEX idx_google_id (google_id)
INDEX idx_two_factor_enabled (two_factor_enabled)
```

## 6. Segurança

### 6.1 Medidas Implementadas

- **Validação de códigos**: Códigos 2FA são validados com timestamp
- **Códigos únicos**: Códigos de recuperação são usados apenas uma vez
- **Logs de segurança**: Todas as ações são registradas
- **Validação de senha**: Desativar 2FA requer confirmação de senha
- **Proteção de rotas**: Rotas sensíveis protegidas por middleware

### 6.2 Recomendações

- Use HTTPS em produção
- Configure rate limiting nas rotas de autenticação
- Monitore logs de tentativas de login
- Eduque usuários sobre a importância do 2FA

## 7. Dependências Instaladas

```bash
composer require laravel/socialite
composer require pragmarx/google2fa-laravel
```

## 8. Testando a Implementação

### 8.1 Testar Google OAuth

1. Configure as credenciais no `.env`
2. Acesse a página de login
3. Clique em "Entrar com Google"
4. Autorize o aplicativo
5. Verifique se o login foi bem-sucedido

### 8.2 Testar 2FA

1. Faça login normalmente
2. Vá para "Perfil" > "Segurança da Conta"
3. Clique em "Ativar 2FA"
4. Escaneie o QR Code com um aplicativo autenticador
5. Digite o código de verificação
6. Guarde os códigos de recuperação
7. Faça logout e teste o login com 2FA

## 9. Solução de Problemas

### 9.1 Erros Comuns

**Erro: "The redirect URI in the request does not match"**
- Verifique se a URL de callback está correta no Google Cloud Console
- Certifique-se de que APP_URL no .env está correto

**Erro: "Invalid client"**
- Verifique se GOOGLE_CLIENT_ID e GOOGLE_CLIENT_SECRET estão corretos
- Confirme que as credenciais estão ativas no Google Cloud Console

**Erro: "QR Code não aparece"**
- Verifique se o pacote google2fa-qrcode está instalado
- Confirme que a biblioteca GD está habilitada no PHP

### 9.2 Logs para Debug

Os logs são salvos em `storage/logs/laravel.log` e incluem:
- Tentativas de login Google
- Ativação/desativação de 2FA
- Uso de códigos de recuperação
- Erros de autenticação

## 10. Próximos Passos

- Implementar rate limiting
- Adicionar notificações por email para ativações de 2FA
- Implementar backup de códigos QR
- Adicionar suporte a WebAuthn (FIDO2) 