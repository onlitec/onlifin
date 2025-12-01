# Configuração do Usuário Administrador

## Como Funciona o Sistema de Administração

A plataforma de Gestão Financeira Pessoal utiliza um sistema automático de atribuição de privilégios de administrador:

### 🔑 Primeiro Usuário = Administrador Automático

**O primeiro usuário que se registrar na plataforma será automaticamente promovido a administrador.**

⚠️ **IMPORTANTE**: O auto-cadastro foi desabilitado. Apenas administradores podem criar novos usuários através do painel de administração.

## Passos para Criar o Primeiro Usuário Administrador

### Método 1: Via Supabase Dashboard (Recomendado)

1. **Acesse o Supabase Dashboard**
   - Faça login no painel do Supabase
   - Navegue até Authentication > Users

2. **Crie o Primeiro Usuário**
   - Clique em "Add User" ou "Invite User"
   - Email: `admin@miaoda.com` (ou qualquer email desejado)
   - Senha: Crie uma senha forte e segura
   - Confirme a criação

3. **Verifique o Perfil**
   - Vá para Table Editor > profiles
   - Verifique se o usuário foi criado com role = 'admin'
   - O primeiro usuário sempre recebe role 'admin' automaticamente

4. **Faça Login na Aplicação**
   - Acesse a página de login
   - Use o username (parte antes do @ do email)
   - Entre com a senha criada

### Método 2: Via SQL (Avançado)

Execute o seguinte SQL no Supabase SQL Editor:

```sql
-- Criar usuário no auth.users (substitua os valores)
INSERT INTO auth.users (
  instance_id,
  id,
  aud,
  role,
  email,
  encrypted_password,
  email_confirmed_at,
  recovery_sent_at,
  last_sign_in_at,
  raw_app_meta_data,
  raw_user_meta_data,
  created_at,
  updated_at,
  confirmation_token,
  email_change,
  email_change_token_new,
  recovery_token
) VALUES (
  '00000000-0000-0000-0000-000000000000',
  gen_random_uuid(),
  'authenticated',
  'authenticated',
  'admin@miaoda.com',
  crypt('sua_senha_aqui', gen_salt('bf')),
  NOW(),
  NOW(),
  NOW(),
  '{"provider":"email","providers":["email"]}',
  '{"username":"admin"}',
  NOW(),
  NOW(),
  '',
  '',
  '',
  ''
);

-- O trigger handle_new_user() criará automaticamente o perfil com role 'admin'
```

## Criando Usuários Adicionais

Após o primeiro usuário administrador estar configurado:

### 1. Acesse a Gestão de Usuários

- Faça login como administrador
- No menu, clique em **Admin** > **Gestão de Usuários**

### 2. Criar Novo Usuário

- Clique no botão **"Novo Usuário"**
- Preencha os dados:
  - **Nome de Usuário** (obrigatório): apenas letras, números e underscore
  - **Senha** (obrigatório): mínimo 6 caracteres
  - **Nome Completo** (opcional)
  - **Papel**: Escolha entre:
    - **Usuário**: Acesso básico às funcionalidades
    - **Financeiro**: Acesso a relatórios e análises
    - **Administrador**: Acesso total ao sistema

### 3. Gerenciar Usuários Existentes

Na página de Gestão de Usuários você pode:

- ✅ **Visualizar** todos os usuários cadastrados
- ✅ **Alterar o papel** de qualquer usuário
- ✅ **Excluir** usuários (com confirmação)
- ✅ **Ver informações** como data de criação

## Níveis de Acesso

O sistema possui três níveis de usuário:

| Nível | Descrição | Permissões |
|-------|-----------|------------|
| **admin** | Acesso total ao sistema | • Criar/editar/excluir usuários<br>• Configurar IA<br>• Acesso a todos os recursos<br>• Gerenciar permissões |
| **financeiro** | Acesso a relatórios e análises | • Visualizar relatórios<br>• Análises financeiras<br>• Exportar dados |
| **user** | Acesso básico às funcionalidades | • Gerenciar próprias contas<br>• Cadastrar transações<br>• Visualizar dashboard pessoal |

## Verificando Privilégios de Administrador

Após fazer login como administrador, você terá acesso a:

- ✅ **Menu Admin** - Visível no menu de navegação
- ✅ **Gestão de Usuários** - Criar e gerenciar usuários
- ✅ **IA Admin** - Configuração do assistente de IA
- ✅ **Todas as funcionalidades da plataforma**

## Segurança

⚠️ **Recomendações de Segurança:**

- Use um email seguro e confiável para a conta de administrador
- Crie uma senha forte (mínimo 8 caracteres, com letras, números e símbolos)
- Ative a autenticação multifator (MFA) quando disponível
- Não compartilhe as credenciais de administrador
- Mantenha um registro seguro das credenciais
- Revise periodicamente os usuários cadastrados
- Remova usuários inativos ou desnecessários

## Recuperação de Acesso

Se você perder o acesso à conta de administrador:

1. Use a função "Esqueci minha senha" na página de login (se implementada)
2. Acesse o Supabase Dashboard e redefina a senha manualmente
3. Crie um novo usuário admin via SQL se necessário
4. Entre em contato com o suporte técnico

## Políticas de Segurança do Banco de Dados

O sistema implementa Row Level Security (RLS) para proteger os dados:

- Usuários só podem ver e modificar seus próprios dados
- Administradores têm acesso total através de políticas especiais
- Todas as operações são auditadas
- Dados sensíveis são criptografados

## Suporte Técnico

Para problemas relacionados ao acesso de administrador:

- Verifique os logs do Supabase
- Consulte a documentação do sistema de autenticação
- Revise as políticas de segurança do banco de dados
- Verifique a tabela `profiles` para confirmar o role do usuário

## Troubleshooting

### Problema: Não consigo criar o primeiro usuário

**Solução**: Use o Supabase Dashboard para criar o usuário manualmente na seção Authentication.

### Problema: O usuário foi criado mas não tem permissões de admin

**Solução**: Verifique na tabela `profiles` se o campo `role` está definido como 'admin'. Se não, atualize manualmente:

```sql
UPDATE profiles SET role = 'admin' WHERE username = 'seu_usuario';
```

### Problema: Não vejo o menu Admin após fazer login

**Solução**: 
1. Verifique se você está logado com um usuário admin
2. Limpe o cache do navegador
3. Faça logout e login novamente
4. Verifique no console do navegador se há erros

---

**Data de Criação**: 01/12/2025  
**Última Atualização**: 01/12/2025
