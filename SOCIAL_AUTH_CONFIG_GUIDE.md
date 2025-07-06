# Guia de Configuração de Autenticação Social

## Visão Geral

O sistema Onlifin agora possui uma interface amigável para configuração de provedores de autenticação social. Esta funcionalidade permite que os usuários façam login utilizando suas contas do Google, Facebook, Twitter, GitHub, LinkedIn e Microsoft.

## Acesso à Configuração

Para acessar a configuração de autenticação social:

1. Faça login como administrador
2. Acesse **Configurações** no menu principal
3. Clique no card **"Autenticação Social"**
4. Ou acesse diretamente: `https://onlifin.onlitec.com.br/settings/social-auth`

## Interface de Configuração

### Visão Geral dos Provedores

A interface exibe cards para cada provedor suportado:

- **Google** (já configurado)
- **Facebook**
- **Twitter**
- **GitHub**
- **LinkedIn**
- **Microsoft**

### Status dos Provedores

Cada card mostra:
- **Status**: Habilitado/Desabilitado
- **Configuração**: Configurado/Não configurado
- **Botões**: Documentação e Configurar

## Configuração de Provedores

### Passo a Passo

1. **Clique em "Configurar"** no card do provedor desejado
2. **Ative o provedor** usando o toggle
3. **Preencha as credenciais**:
   - Client ID
   - Client Secret
4. **Copie a URL de callback** (gerada automaticamente)
5. **Teste a configuração** (recomendado)
6. **Salve** as configurações

### URLs de Callback

As URLs de callback são geradas automaticamente:
- **Google**: `https://onlifin.onlitec.com.br/auth/social/callback?provider=google`
- **Facebook**: `https://onlifin.onlitec.com.br/auth/social/callback?provider=facebook`
- **Twitter**: `https://onlifin.onlitec.com.br/auth/social/callback?provider=twitter`
- **GitHub**: `https://onlifin.onlitec.com.br/auth/social/callback?provider=github`
- **LinkedIn**: `https://onlifin.onlitec.com.br/auth/social/callback?provider=linkedin`
- **Microsoft**: `https://onlifin.onlitec.com.br/auth/social/callback?provider=microsoft`

## Configuração por Provedor

### Google OAuth 2.0

1. **Console**: [Google Cloud Console](https://console.cloud.google.com/)
2. **Passos**:
   - Acesse o Google Cloud Console
   - Crie um novo projeto ou selecione um existente
   - Ative a API Google+ ou People API
   - Configure a tela de consentimento OAuth
   - Crie credenciais OAuth 2.0
   - Adicione a URL de callback autorizada
   - Copie o Client ID e Client Secret

### Facebook Login

1. **Console**: [Facebook Developers](https://developers.facebook.com/)
2. **Passos**:
   - Acesse Facebook Developers
   - Crie um novo app ou use um existente
   - Adicione o produto Facebook Login
   - Configure URLs de redirecionamento OAuth válidas
   - Obtenha o App ID e App Secret
   - Configure as permissões necessárias

### Twitter OAuth 2.0

1. **Console**: [Twitter Developer Portal](https://developer.twitter.com/)
2. **Passos**:
   - Acesse Twitter Developer Portal
   - Crie um novo app
   - Configure OAuth 2.0 settings
   - Adicione a URL de callback
   - Obtenha Client ID e Client Secret
   - Configure as permissões de leitura

### GitHub OAuth

1. **Console**: [GitHub Developer Settings](https://github.com/settings/developers)
2. **Passos**:
   - Acesse GitHub Developer Settings
   - Crie um novo OAuth App
   - Preencha as informações do aplicativo
   - Configure a Authorization callback URL
   - Obtenha Client ID e Client Secret

### LinkedIn OAuth 2.0

1. **Console**: [LinkedIn Developer Console](https://www.linkedin.com/developers/)
2. **Passos**:
   - Acesse LinkedIn Developer Console
   - Crie um novo app
   - Configure OAuth 2.0 settings
   - Adicione redirect URLs autorizadas
   - Obtenha Client ID e Client Secret
   - Configure produtos necessários

### Microsoft OAuth 2.0

1. **Console**: [Azure Portal](https://portal.azure.com/)
2. **Passos**:
   - Acesse Azure Portal
   - Registre um novo app no Azure AD
   - Configure OAuth 2.0 permissions
   - Adicione redirect URIs
   - Obtenha Application ID e Secret
   - Configure API permissions

## Funcionalidades da Interface

### Teste de Configuração

- **Função**: Valida as credenciais antes de salvar
- **Como usar**: Preencha Client ID e Secret, clique em "Testar"
- **Resultado**: Mostra se as credenciais estão corretas

### Documentação Integrada

- **Função**: Mostra instruções específicas para cada provedor
- **Como usar**: Clique em "Documentação" no card do provedor
- **Conteúdo**: Links para consoles, URLs de callback, passos detalhados

### Cópia de URL

- **Função**: Facilita a cópia da URL de callback
- **Como usar**: Clique no ícone de cópia ao lado da URL
- **Resultado**: URL copiada para área de transferência

## Armazenamento de Configurações

As configurações são armazenadas no arquivo `.env`:

```env
# Google (já configurado)
GOOGLE_ENABLED=true
GOOGLE_CLIENT_ID=seu_client_id
GOOGLE_CLIENT_SECRET=seu_client_secret

# Facebook
FACEBOOK_ENABLED=false
FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=

# Twitter
TWITTER_ENABLED=false
TWITTER_CLIENT_ID=
TWITTER_CLIENT_SECRET=

# GitHub
GITHUB_ENABLED=false
GITHUB_CLIENT_ID=
GITHUB_CLIENT_SECRET=

# LinkedIn
LINKEDIN_ENABLED=false
LINKEDIN_CLIENT_ID=
LINKEDIN_CLIENT_SECRET=

# Microsoft
MICROSOFT_ENABLED=false
MICROSOFT_CLIENT_ID=
MICROSOFT_CLIENT_SECRET=
```

## Fluxo de Autenticação

1. **Usuário clica** no botão do provedor na tela de login
2. **Sistema redireciona** para o provedor OAuth
3. **Usuário autoriza** o acesso no provedor
4. **Provedor redireciona** de volta com código de autorização
5. **Sistema processa** o código e obtém dados do usuário
6. **Sistema autentica** o usuário (cria conta se necessário)
7. **Usuário é logado** no sistema

## Segurança

- **Credenciais**: Armazenadas no arquivo .env (não versionado)
- **Logs**: Todas as operações são registradas nos logs
- **Validação**: Credenciais são validadas antes de serem salvas
- **Criptografia**: Comunicação via HTTPS obrigatória

## Troubleshooting

### Problema: "Provedor não suportado"
- **Causa**: Biblioteca Hybridauth não encontrou o provedor
- **Solução**: Verifique se o provedor está instalado

### Problema: "Credenciais inválidas"
- **Causa**: Client ID ou Secret incorretos
- **Solução**: Verifique as credenciais no console do provedor

### Problema: "URL de callback inválida"
- **Causa**: URL não configurada no console do provedor
- **Solução**: Adicione a URL de callback no console

### Problema: "Permissões insuficientes"
- **Causa**: Aplicativo não tem permissões necessárias
- **Solução**: Configure as permissões no console do provedor

## Logs e Monitoramento

Os logs são armazenados em:
- `storage/logs/laravel.log` - Logs gerais
- `storage/logs/hybridauth.log` - Logs específicos do Hybridauth

### Tipos de Log

- **INFO**: Configurações atualizadas com sucesso
- **WARNING**: Testes de configuração falharam
- **ERROR**: Erros durante salvamento de configurações

## Próximos Passos

1. **Configure os provedores** que deseja utilizar
2. **Teste cada configuração** antes de disponibilizar
3. **Monitore os logs** para identificar problemas
4. **Documente** as configurações específicas da sua organização

## Suporte

Em caso de dúvidas ou problemas:
1. Consulte os logs do sistema
2. Verifique a documentação do provedor
3. Teste as credenciais manualmente
4. Entre em contato com o suporte técnico

---

**Nota**: Esta funcionalidade expande significativamente as opções de autenticação do sistema Onlifin, proporcionando uma experiência mais flexível e moderna para os usuários. 