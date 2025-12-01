# Configuração do Usuário Administrador

## Como Funciona o Sistema de Administração

A plataforma de Gestão Financeira Pessoal utiliza um sistema automático de atribuição de privilégios de administrador:

### 🔑 Primeiro Usuário = Administrador Automático

**O primeiro usuário que se registrar na plataforma será automaticamente promovido a administrador.**

## Passos para Criar o Usuário Administrador

### 1. Acesse a Página de Login
- Navegue até a página de login da aplicação
- URL: `http://localhost:5173/login` (ou o endereço do seu servidor)

### 2. Clique em "Criar Conta" ou "Registrar"
- Procure pelo link de registro na página de login

### 3. Preencha os Dados de Registro
- **Email**: Insira o email que será usado como administrador
- **Senha**: Crie uma senha forte e segura
- **Confirme a senha**

### 4. Confirme o Email
- Verifique sua caixa de entrada
- Clique no link de confirmação enviado pelo Supabase
- **IMPORTANTE**: O perfil de administrador só é criado após a confirmação do email

### 5. Faça Login
- Após confirmar o email, retorne à página de login
- Entre com suas credenciais
- Você agora tem acesso completo como administrador

## Verificando Privilégios de Administrador

Após fazer login como administrador, você terá acesso a:

- ✅ **Menu Admin** - Visível no menu de navegação
- ✅ **IA Admin** - Configuração do assistente de IA
- ✅ **Gestão de Usuários** - Controle de permissões (se implementado)
- ✅ **Todas as funcionalidades da plataforma**

## Níveis de Acesso

O sistema possui três níveis de usuário:

| Nível | Descrição | Atribuição |
|-------|-----------|------------|
| **admin** | Acesso total ao sistema | Primeiro usuário registrado |
| **financeiro** | Acesso a relatórios e análises | Manual (via admin) |
| **user** | Acesso básico às funcionalidades | Padrão para novos usuários |

## Criando Usuários Adicionais

Após o primeiro usuário administrador:

1. Novos registros receberão automaticamente o papel de **user**
2. O administrador pode promover usuários para **financeiro** ou **admin** através do painel de administração

## Segurança

⚠️ **Recomendações de Segurança:**

- Use um email seguro e confiável para a conta de administrador
- Crie uma senha forte (mínimo 8 caracteres, com letras, números e símbolos)
- Ative a autenticação multifator (MFA) quando disponível
- Não compartilhe as credenciais de administrador
- Mantenha um registro seguro das credenciais

## Recuperação de Acesso

Se você perder o acesso à conta de administrador:

1. Use a função "Esqueci minha senha" na página de login
2. Siga as instruções enviadas por email
3. Se necessário, entre em contato com o suporte técnico

## Suporte Técnico

Para problemas relacionados ao acesso de administrador:

- Verifique os logs do Supabase
- Consulte a documentação do sistema de autenticação
- Revise as políticas de segurança do banco de dados

---

**Data de Criação**: 01/12/2025  
**Última Atualização**: 01/12/2025
