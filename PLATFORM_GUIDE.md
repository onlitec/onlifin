# Plataforma de Gestão Financeira Pessoal - Guia do Usuário

## 🎯 Visão Geral

Bem-vindo à sua Plataforma de Gestão Financeira Pessoal! Esta é uma aplicação web completa que permite gerenciar suas finanças pessoais com recursos avançados de IA.

## ✨ Funcionalidades Principais

### 1. **Autenticação e Segurança**
- Sistema de login com nome de usuário e senha
- Controle de acesso baseado em funções (Usuário, Financeiro, Admin)
- O primeiro usuário registrado automaticamente se torna administrador
- Proteção de rotas e dados sensíveis

### 2. **Dashboard Financeiro**
- Visualização do saldo total de todas as contas
- Receitas e despesas do mês atual
- Gráfico de pizza mostrando despesas por categoria
- Gráfico de barras com histórico mensal dos últimos 6 meses
- Contadores de contas e cartões ativos

### 3. **Gestão de Contas Bancárias**
- Cadastro de múltiplas contas bancárias
- Informações detalhadas: nome, banco, agência, conta, moeda
- Acompanhamento de saldo em tempo real
- Edição e exclusão de contas

### 4. **Gestão de Transações**
- Registro de receitas e despesas
- Categorização automática com categorias pré-definidas
- Vinculação a contas bancárias
- Filtros por data, tipo e categoria
- Visualização detalhada de cada transação

### 5. **Assistente Financeiro com IA**
- Botão flutuante acessível em todas as páginas
- Chat em tempo real com inteligência artificial
- Ajuda com:
  - Categorização de transações
  - Dicas de economia
  - Análise de gastos
  - Planejamento financeiro
  - Explicações sobre conceitos financeiros
- Histórico de conversas registrado para auditoria

### 6. **Painel de Administração**
- Gerenciamento de usuários (apenas para admins)
- Alteração de funções de usuários
- Visualização de todos os perfis do sistema

## 🚀 Como Começar

### Primeiro Acesso

1. **Registre-se**
   - Acesse a página de login
   - Clique em "Não tem uma conta? Cadastre-se"
   - Escolha um nome de usuário (apenas letras, números e underscore)
   - Crie uma senha segura
   - O primeiro usuário registrado será automaticamente administrador

2. **Configure suas Contas**
   - Vá para "Contas" no menu
   - Clique em "Nova Conta"
   - Preencha os dados da sua conta bancária
   - Defina o saldo inicial

3. **Registre suas Transações**
   - Acesse "Transações" no menu
   - Clique em "Nova Transação"
   - Escolha o tipo (Receita ou Despesa)
   - Preencha valor, data, categoria e descrição
   - Vincule a uma conta bancária

4. **Explore o Dashboard**
   - Visualize seus dados financeiros em gráficos
   - Acompanhe seu saldo total
   - Analise suas despesas por categoria

5. **Use o Assistente de IA**
   - Clique no botão flutuante no canto inferior direito
   - Faça perguntas sobre suas finanças
   - Receba dicas personalizadas

## 👥 Funções de Usuário

### Usuário (user)
- Acesso completo aos próprios dados financeiros
- Criar, editar e excluir contas e transações
- Usar o assistente de IA
- Visualizar dashboard e relatórios

### Financeiro (financeiro)
- Todas as permissões de usuário
- Visualizar dados financeiros de todos os usuários
- Gerar relatórios consolidados

### Administrador (admin)
- Todas as permissões anteriores
- Gerenciar usuários e suas funções
- Acesso ao painel de administração
- Configurar permissões do sistema

## 🎨 Design e Interface

A plataforma utiliza um design profissional com:
- **Cores principais**: Azul profissional (#2C3E50) e verde financeiro (#27AE60)
- **Layout em cards**: Organização modular e limpa
- **Gráficos interativos**: Visualização clara de dados
- **Responsivo**: Funciona em desktop, tablet e mobile
- **Modo escuro**: Suporte automático para preferências do sistema

## 🔒 Segurança e Privacidade

- **Autenticação segura**: Senhas criptografadas
- **Controle de acesso**: RLS (Row Level Security) no banco de dados
- **Isolamento de dados**: Cada usuário acessa apenas seus próprios dados
- **Auditoria**: Registro de interações com IA
- **Permissões granulares**: Controle fino de acesso aos dados

## 🤖 Assistente de IA

O assistente financeiro utiliza o modelo Gemini 2.5 Flash e pode ajudar com:

- **Categorização**: "Como devo categorizar uma compra no supermercado?"
- **Economia**: "Dê-me dicas para economizar nas despesas mensais"
- **Análise**: "Como posso reduzir meus gastos com alimentação?"
- **Planejamento**: "Quanto devo guardar por mês para uma viagem?"
- **Educação**: "O que é fluxo de caixa?"

## 📊 Categorias Pré-definidas

### Receitas
- 💰 Salário
- 💼 Freelance
- 📈 Investimentos
- 💵 Outros Rendimentos

### Despesas
- 🍔 Alimentação
- 🚗 Transporte
- 🏠 Moradia
- 🏥 Saúde
- 📚 Educação
- 🎮 Lazer
- 🛒 Compras
- 📄 Contas
- 💸 Outros Gastos

## 💡 Dicas de Uso

1. **Registre transações regularmente** para manter seus dados atualizados
2. **Use categorias consistentes** para análises mais precisas
3. **Consulte o dashboard semanalmente** para acompanhar seu progresso
4. **Aproveite o assistente de IA** para tirar dúvidas e obter insights
5. **Mantenha seus saldos atualizados** para projeções mais precisas

## 🛠️ Tecnologias Utilizadas

- **Frontend**: React + TypeScript + Tailwind CSS + shadcn/ui
- **Backend**: Supabase (PostgreSQL + Auth + Edge Functions)
- **IA**: Google Gemini 2.5 Flash
- **Gráficos**: Recharts
- **Autenticação**: miaoda-auth-react

## 📝 Dados Iniciais

O sistema vem com 13 categorias pré-cadastradas (4 de receitas e 9 de despesas) para facilitar o início do uso. Você pode criar categorias personalizadas conforme necessário.

## 🎓 Próximos Passos

Após se familiarizar com a plataforma, você pode:
- Importar extratos bancários (funcionalidade futura)
- Configurar transações recorrentes
- Criar metas de economia
- Gerar relatórios personalizados
- Exportar dados para análise externa

---

**Nota**: Esta é uma plataforma MVP (Produto Mínimo Viável) com as funcionalidades essenciais implementadas. Novos recursos serão adicionados em versões futuras baseados no feedback dos usuários.
