# Credenciais do Administrador

## ✅ Usuário Administrador Criado com Sucesso

### Dados de Acesso

- **Nome de Usuário**: `admin`
- **Senha**: `*M3a74g20M`
- **Email**: `admin@miaoda.com`
- **Papel**: Administrador (admin)

### Como Fazer Login

1. Acesse a página de login da aplicação
2. No campo "Nome de Usuário", digite: `admin`
3. No campo "Senha", digite: `*M3a74g20M`
4. Clique em "Entrar"

### Permissões do Administrador

Como administrador, você tem acesso a:

- ✅ **Dashboard** - Visão geral das finanças
- ✅ **Contas** - Gerenciar contas bancárias
- ✅ **Cartões** - Gerenciar cartões de crédito
- ✅ **Transações** - Cadastrar e gerenciar transações
- ✅ **Categorias** - Gerenciar categorias de despesas/receitas
- ✅ **Relatórios** - Visualizar relatórios financeiros
- ✅ **Admin** - Painel de administração
  - **Gestão de Usuários** - Criar e gerenciar usuários do sistema
  - **IA Admin** - Configurar o assistente de IA

### Primeiros Passos

Após fazer login como administrador, você pode:

1. **Criar Novos Usuários**
   - Acesse: Admin > Gestão de Usuários
   - Clique em "Novo Usuário"
   - Preencha os dados e escolha o papel (user, financeiro ou admin)

2. **Configurar o Assistente de IA**
   - Acesse: Admin > IA Admin
   - Configure o modelo de IA desejado
   - Defina as permissões de acesso aos dados

3. **Começar a Usar o Sistema**
   - Cadastre suas contas bancárias
   - Adicione seus cartões de crédito
   - Importe extratos ou cadastre transações manualmente
   - Visualize relatórios e análises

### Segurança

⚠️ **IMPORTANTE**:
- Mantenha esta senha em local seguro
- Não compartilhe as credenciais de administrador
- Considere alterar a senha após o primeiro login
- Crie usuários separados para outras pessoas que precisem acessar o sistema

### Alterando a Senha

Para alterar a senha do administrador:

1. Faça login com as credenciais acima
2. Acesse o menu do usuário (ícone de perfil no canto superior direito)
3. Clique em "Administração"
4. Ou use o SQL do Supabase para redefinir a senha:

```sql
UPDATE auth.users 
SET encrypted_password = crypt('nova_senha_aqui', gen_salt('bf'))
WHERE email = 'admin@miaoda.com';
```

---

**Data de Criação**: 01/12/2025  
**Status**: ✅ Ativo
