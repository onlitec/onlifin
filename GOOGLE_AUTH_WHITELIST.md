# Autenticação Google Restrita - Apenas Usuários Cadastrados

## Visão Geral

O sistema de autenticação via Google foi modificado para aceitar apenas usuários que já estão previamente cadastrados no sistema. Isso significa que novos usuários não podem se registrar automaticamente usando suas contas Google - eles devem ser cadastrados primeiro por um administrador.

## Como Funciona

### 1. Verificação de Email

Quando um usuário tenta fazer login via Google:

1. O sistema obtém o email da conta Google
2. Verifica se existe um usuário cadastrado com esse email no banco de dados
3. Se o email **não** estiver cadastrado, o acesso é negado
4. Se o email **estiver** cadastrado, o login prossegue normalmente

### 2. Fluxo de Autenticação

```
┌─────────────────────┐
│   Usuário clica     │
│  "Entrar com Google"│
└──────────┬──────────┘
           │
           v
┌─────────────────────┐
│   Redirecionamento  │
│   para Google OAuth │
└──────────┬──────────┘
           │
           v
┌─────────────────────┐
│  Usuário autoriza   │
│    no Google        │
└──────────┬──────────┘
           │
           v
┌─────────────────────┐
│   Sistema verifica  │
│  se email está      │
│   cadastrado        │
└──────────┬──────────┘
           │
           v
┌─────────────────────┐    NÃO    ┌─────────────────────┐
│   Email cadastrado? │─────────▶│   Acesso negado     │
│                     │          │   Mensagem de erro  │
└──────────┬──────────┘          └─────────────────────┘
           │ SIM
           v
┌─────────────────────┐
│   Vincula conta     │
│   Google ao usuário │
│   e faz login       │
└─────────────────────┘
```

### 3. Mensagens de Erro

Quando um usuário não cadastrado tenta fazer login, ele recebe a seguinte mensagem:

**"Acesso negado. Apenas usuários previamente cadastrados podem fazer login via Google. Entre em contato com o administrador para solicitar acesso."**

## Implementação Técnica

### Métodos Adicionados ao Modelo User

```php
/**
 * Verifica se um email está cadastrado no sistema
 */
public static function isEmailRegistered(string $email): bool
{
    return self::where('email', $email)->exists();
}

/**
 * Busca usuário por email
 */
public static function findByEmail(string $email): ?User
{
    return self::where('email', $email)->first();
}
```

### Controladores Modificados

1. **SocialAuthController** - Para autenticação via HybridAuth (múltiplos provedores)
2. **GoogleAuthController** - Para autenticação via Laravel Socialite (Google específico)

Ambos implementam a mesma lógica de verificação de email cadastrado.

## Vantagens de Segurança

### 1. Controle de Acesso
- Apenas usuários autorizados podem acessar o sistema
- Administradores têm controle total sobre quem pode fazer login
- Previne registros automáticos indesejados

### 2. Auditoria
- Todas as tentativas de login com emails não cadastrados são registradas nos logs
- Facilita o monitoramento de tentativas de acesso não autorizadas

### 3. Consistência
- Mantém a integridade do sistema de usuários
- Garante que todos os usuários passem pelo processo de aprovação

## Como Cadastrar Novos Usuários

### Via Interface Web

1. Acesse **Configurações** → **Usuários**
2. Clique em **"Novo Usuário"**
3. Preencha os dados do usuário (nome, email, etc.)
4. Defina uma senha temporária
5. Salve o usuário

### Via Linha de Comando

```bash
php artisan app:create-user --name="Nome do Usuário" --email="email@exemplo.com" --password="senha123"
```

## Processo para Usuários Existentes

### 1. Usuário Já Cadastrado (Primeira Vez com Google)

1. Usuário clica em "Entrar com Google"
2. Sistema verifica que o email está cadastrado
3. Sistema vincula a conta Google ao usuário existente
4. Login é realizado com sucesso
5. Nas próximas vezes, o login será direto

### 2. Usuário Já Vinculado

1. Usuário clica em "Entrar com Google"
2. Sistema reconhece a conta Google já vinculada
3. Login é realizado imediatamente

## Logs e Monitoramento

### Logs Gerados

- **INFO**: Login Google realizado com sucesso
- **INFO**: Conta Google vinculada ao usuário existente
- **WARNING**: Tentativa de login com email não cadastrado
- **ERROR**: Erros durante o processo de autenticação

### Exemplo de Log

```
[2024-01-15 10:30:45] local.WARNING: Tentativa de login com email não cadastrado
{
    "provider": "google",
    "email": "usuario.nao.cadastrado@gmail.com",
    "google_id": "123456789"
}
```

## Configuração

### Variáveis de Ambiente

As mesmas configurações do Google OAuth continuam válidas:

```env
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
```

### Rotas Disponíveis

- `GET /auth/google` - Redireciona para Google OAuth
- `GET /auth/google/callback` - Processa callback do Google
- `GET /auth/social/google` - Redireciona via HybridAuth
- `GET /auth/social/callback?provider=google` - Callback via HybridAuth

## Compatibilidade

### Versões Suportadas

- Laravel 11.x
- HybridAuth 3.x
- Laravel Socialite 5.x

### Provedores Afetados

Esta implementação afeta todos os provedores sociais configurados:
- Google
- Facebook
- Twitter
- GitHub
- LinkedIn
- Microsoft

## Solução de Problemas

### Problema: "Acesso negado" para usuário que deveria ter acesso

**Causa**: Email não está cadastrado no sistema ou há diferença entre emails

**Solução**:
1. Verifique se o email está exatamente igual no banco de dados
2. Cadastre o usuário se necessário
3. Verifique logs para confirmar o email recebido do Google

### Problema: Usuário não consegue fazer login após cadastro

**Causa**: Conta Google não foi vinculada corretamente

**Solução**:
1. Verifique se o usuário está ativo (`is_active = true`)
2. Tente fazer login novamente via Google
3. Verifique logs para erros durante vinculação

## Migração de Sistemas Existentes

### Se você já tinha usuários criados via Google

Os usuários existentes continuarão funcionando normalmente, pois:
1. Eles já estão cadastrados no sistema
2. Suas contas Google já estão vinculadas
3. O login continuará funcionando sem problemas

### Para desabilitar a restrição (se necessário)

Se por algum motivo você precisar voltar ao comportamento anterior (permitir novos registros), você pode:

1. Comentar a verificação de email cadastrado
2. Reativar a criação automática de usuários
3. Modificar a mensagem de erro

**Nota**: Não recomendamos desabilitar a restrição em ambientes de produção por questões de segurança.

---

**Implementado em**: Janeiro 2024  
**Versão**: 1.0  
**Compatibilidade**: Laravel 11.x, HybridAuth 3.x 