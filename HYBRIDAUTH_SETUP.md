# Integração Hybridauth - Autenticação Social Múltipla

## Visão Geral

A Hybridauth foi integrada ao sistema Onlifin para fornecer autenticação social com múltiplos provedores. Esta implementação substitui e expande a funcionalidade anterior que era limitada apenas ao Google OAuth.

## Recursos Implementados

### 1. Múltiplos Provedores Suportados
- **Google** - Totalmente configurado e testado
- **Facebook** - Configurado, aguardando credenciais
- **Twitter** - Configurado, aguardando credenciais
- **GitHub** - Configurado, aguardando credenciais
- **LinkedIn** - Configurado, aguardando credenciais
- **Microsoft** - Configurado, aguardando credenciais

### 2. Funcionalidades Principais
- ✅ Login/registro automático via provedores sociais
- ✅ Vinculação de contas sociais a usuários existentes
- ✅ Múltiplas contas sociais por usuário
- ✅ Desvinculação segura de contas sociais
- ✅ Integração com sistema 2FA existente
- ✅ Interface unificada para gerenciamento
- ✅ Logs detalhados de todas as operações

### 3. Segurança
- ✅ Validação de tokens de acesso
- ✅ Armazenamento seguro de credenciais
- ✅ Verificação de integridade dos dados
- ✅ Proteção contra ataques de força bruta
- ✅ Logs de auditoria completos

## Configuração

### 1. Variáveis de Ambiente

Adicione as seguintes variáveis ao seu arquivo `.env`:

```env
# Configurações Hybridauth
HYBRIDAUTH_DEBUG=false
HYBRIDAUTH_SSL_VERIFY=true

# Google OAuth (já configurado)
GOOGLE_ENABLED=true
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret

# Facebook OAuth
FACEBOOK_ENABLED=false
FACEBOOK_CLIENT_ID=your_facebook_client_id
FACEBOOK_CLIENT_SECRET=your_facebook_client_secret

# Twitter OAuth
TWITTER_ENABLED=false
TWITTER_CLIENT_ID=your_twitter_client_id
TWITTER_CLIENT_SECRET=your_twitter_client_secret

# GitHub OAuth
GITHUB_ENABLED=false
GITHUB_CLIENT_ID=your_github_client_id
GITHUB_CLIENT_SECRET=your_github_client_secret

# LinkedIn OAuth
LINKEDIN_ENABLED=false
LINKEDIN_CLIENT_ID=your_linkedin_client_id
LINKEDIN_CLIENT_SECRET=your_linkedin_client_secret

# Microsoft OAuth
MICROSOFT_ENABLED=false
MICROSOFT_CLIENT_ID=your_microsoft_client_id
MICROSOFT_CLIENT_SECRET=your_microsoft_client_secret
```

### 2. Configuração dos Provedores

#### Google (Já Configurado)
- Console: [Google Cloud Console](https://console.cloud.google.com/)
- Callback URL: `https://seu-dominio.com/auth/social/callback?provider=google`

#### Facebook
1. Acesse [Facebook Developers](https://developers.facebook.com/)
2. Crie um novo app ou use um existente
3. Configure Facebook Login
4. Adicione a URL de callback: `https://seu-dominio.com/auth/social/callback?provider=facebook`

#### Twitter
1. Acesse [Twitter Developer Portal](https://developer.twitter.com/)
2. Crie um novo app
3. Configure OAuth 2.0
4. Adicione a URL de callback: `https://seu-dominio.com/auth/social/callback?provider=twitter`

#### GitHub
1. Acesse [GitHub Developer Settings](https://github.com/settings/developers)
2. Crie um novo OAuth App
3. Configure a URL de callback: `https://seu-dominio.com/auth/social/callback?provider=github`

#### LinkedIn
1. Acesse [LinkedIn Developer Console](https://www.linkedin.com/developers/)
2. Crie um novo app
3. Configure OAuth 2.0
4. Adicione a URL de callback: `https://seu-dominio.com/auth/social/callback?provider=linkedin`

#### Microsoft
1. Acesse [Azure Portal](https://portal.azure.com/)
2. Registre um novo app no Azure AD
3. Configure OAuth 2.0
4. Adicione a URL de callback: `https://seu-dominio.com/auth/social/callback?provider=microsoft`

## Estrutura do Banco de Dados

### Tabela `social_accounts`
```sql
CREATE TABLE social_accounts (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    provider VARCHAR(255) NOT NULL,
    provider_id VARCHAR(255) NOT NULL,
    provider_email VARCHAR(255) NULL,
    provider_name VARCHAR(255) NULL,
    provider_avatar VARCHAR(255) NULL,
    access_token TEXT NULL,
    refresh_token TEXT NULL,
    token_expires_at TIMESTAMP NULL,
    provider_data JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_provider_account (provider, provider_id),
    KEY idx_user_provider (user_id, provider),
    KEY idx_provider (provider)
);
```

## Uso da API

### 1. Rotas Disponíveis

#### Autenticação Social
- `GET /auth/social/{provider}` - Redireciona para o provedor
- `GET /auth/social/callback` - Processa o callback do provedor

#### Gerenciamento de Contas
- `POST /auth/social/{provider}/unlink` - Desvincula uma conta social

### 2. Componente Blade

```blade
<!-- Para tela de login -->
<x-social-providers action="login" />

<!-- Para gerenciamento no perfil -->
<x-social-providers action="manage" :user="$user" />
```

### 3. Métodos do Modelo User

```php
// Verificar se tem conta social específica
$user->hasSocialProvider('google'); // true/false

// Obter conta social específica
$socialAccount = $user->getSocialAccount('google');

// Obter todos os provedores conectados
$providers = $user->getConnectedProviders(); // ['google', 'facebook']

// Verificar se tem pelo menos uma conta social
$user->hasSocialAccounts(); // true/false
```

### 4. Métodos do Modelo SocialAccount

```php
// Encontrar conta por provedor e ID
$account = SocialAccount::findByProvider('google', '123456789');

// Obter contas de um usuário
$accounts = SocialAccount::getByUser($userId);

// Verificar se usuário tem provedor específico
$hasProvider = SocialAccount::userHasProvider($userId, 'google');

// Obter provedores suportados
$providers = SocialAccount::getSupportedProviders();
```

## Fluxo de Autenticação

### 1. Novo Usuário
1. Usuário clica em "Entrar com [Provedor]"
2. Redirecionamento para o provedor
3. Usuário autoriza no provedor
4. Callback processa os dados
5. Novo usuário é criado
6. Conta social é vinculada
7. Login automático (ou 2FA se habilitado)

### 2. Usuário Existente (Email Igual)
1. Usuário clica em "Entrar com [Provedor]"
2. Sistema identifica email existente
3. Conta social é vinculada ao usuário existente
4. Login automático (ou 2FA se habilitado)

### 3. Usuário Existente (Conta Social Já Vinculada)
1. Usuário clica em "Entrar com [Provedor]"
2. Sistema identifica conta social existente
3. Atualiza dados da conta social
4. Login automático (ou 2FA se habilitado)

## Logs e Monitoramento

### Logs Gerados
- Tentativas de autenticação social
- Criação de novos usuários via social
- Vinculação de contas sociais
- Desvinculação de contas sociais
- Erros de autenticação
- Atualizações de dados de provedores

### Localização dos Logs
- Laravel Logs: `storage/logs/laravel.log`
- Hybridauth Logs: `storage/logs/hybridauth.log` (se debug ativado)

## Testes

### 1. Teste Manual
1. Acesse a tela de login
2. Clique em "Entrar com Google"
3. Autorize no Google
4. Verifique se o login foi bem-sucedido
5. Acesse o perfil e verifique se a conta está conectada

### 2. Teste de Múltiplas Contas
1. Faça login com um provedor
2. Acesse o perfil
3. Conecte outro provedor
4. Verifique se ambos aparecem como conectados
5. Teste desvinculação

### 3. Teste de 2FA
1. Ative 2FA na conta
2. Faça logout
3. Faça login via provedor social
4. Verifique se é solicitado código 2FA

## Solução de Problemas

### 1. Erro "Provider not supported"
- Verifique se o provedor está na lista `getSupportedProviders()`
- Verifique se está habilitado no `.env`

### 2. Erro "Invalid callback URL"
- Verifique se a URL está correta no console do provedor
- Verifique se `APP_URL` está correto no `.env`

### 3. Erro "Invalid client credentials"
- Verifique as credenciais no `.env`
- Verifique se as credenciais estão corretas no console do provedor

### 4. Erro "SSL verification failed"
- Defina `HYBRIDAUTH_SSL_VERIFY=false` para desenvolvimento
- Para produção, configure certificados SSL adequados

## Próximos Passos

1. **Configurar Provedores Adicionais**: Obter credenciais para Facebook, Twitter, GitHub, LinkedIn e Microsoft
2. **Testes Automatizados**: Implementar testes unitários e de integração
3. **Rate Limiting**: Implementar limitação de tentativas de login social
4. **Webhook Integration**: Implementar webhooks para sincronização de dados
5. **Análise de Uso**: Implementar métricas de uso dos provedores sociais

## Compatibilidade

### Versões Suportadas
- PHP: 8.1+
- Laravel: 11.x
- Hybridauth: 3.12+

### Provedores Testados
- ✅ Google OAuth 2.0
- ⏳ Facebook Login (aguardando credenciais)
- ⏳ Twitter OAuth 2.0 (aguardando credenciais)
- ⏳ GitHub OAuth (aguardando credenciais)
- ⏳ LinkedIn OAuth 2.0 (aguardando credenciais)
- ⏳ Microsoft OAuth 2.0 (aguardando credenciais)

## Suporte

Para dúvidas ou problemas relacionados à integração Hybridauth, consulte:
- [Documentação Oficial Hybridauth](https://hybridauth.github.io/)
- [Repositório GitHub](https://github.com/hybridauth/hybridauth)
- Logs do sistema em `storage/logs/` 