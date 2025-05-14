# Documentação Onlifin v1.0.1

## Visão Geral
Sistema de gestão financeira desenvolvido com Laravel 11 e Livewire 3, oferecendo interface moderna e responsiva para controle de despesas e receitas.

## Estrutura do Sistema

### 1. Autenticação
- Login (`app/Livewire/Auth/Login.php`)
- Registro (`app/Livewire/Auth/Register.php`)
- Recuperação de senha (`app/Livewire/Auth/ForgotPassword.php`)
- Reset de senha (`app/Livewire/Auth/ResetPassword.php`)

### 2. Módulos Principais
- **Dashboard** (`app/Livewire/Dashboard.php`)
  - Resumo financeiro
  - Atividades recentes
  - Links rápidos

- **Despesas** (`app/Livewire/Expenses/ExpenseList.php`)
  - Listagem de despesas
  - Interface para nova despesa

- **Receitas** (`app/Livewire/Incomes/IncomeList.php`)
  - Listagem de receitas
  - Interface para nova receita

- **Configurações** (`app/Livewire/Settings/SystemSettings.php`)
  - Informações do sistema
  - Gestão de usuários
  - Configurações gerais

### 3. Controle de Acesso
- Middleware de Admin (`app/Http/Middleware/AdminMiddleware.php`)
- Rotas protegidas (`routes/web.php`)
- Verificação de permissões

### 4. Interface
- Layout principal (`resources/views/layouts/app.blade.php`)
  - Menu responsivo
  - Navegação consistente
  - Estilos padronizados

- Componentes visuais
  - Cards
  - Seções
  - Listas
  - Grids

### 5. Rotas Principais
\`\`\`php
// Públicas
/ -> Redirecionamento para login
/login -> Página de login
/register -> Página de registro

// Protegidas (requer autenticação)
/dashboard -> Dashboard principal
/expenses -> Gestão de despesas
/incomes -> Gestão de receitas
/settings -> Configurações (requer admin)
\`\`\`

## Requisitos Técnicos

### Software
- PHP 8.2+
- MySQL/MariaDB
- Composer
- Git

### Dependências Principais
\`\`\`json
{
    "php": "^8.2",
    "laravel/framework": "^11.0",
    "livewire/livewire": "^3.0"
}
\`\`\`

## Instalação

1. Clone o repositório
\`\`\`bash
git clone git@github.com:onlitec/onlifin.git
cd onlifin
\`\`\`

2. Instale dependências
\`\`\`bash
composer install
\`\`\`

3. Configure o ambiente
\`\`\`bash
cp .env.example .env
php artisan key:generate
\`\`\`

4. Configure o banco de dados no `.env`
\`\`\`env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=onlifin
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha
\`\`\`

5. Execute as migrações
\`\`\`bash
php artisan migrate
\`\`\`

## Estrutura de Arquivos
\`\`\`
onlifin/
├── app/
│   ├── Http/
│   │   └── Middleware/
│   └── Livewire/
│       ├── Auth/
│       ├── Expenses/
│       ├── Incomes/
│       └── Settings/
├── resources/
│   └── views/
│       ├── layouts/
│       └── livewire/
└── routes/
    └── web.php
\`\`\`

## Estilo e Layout

### Classes CSS Principais
\`\`\`css
.navbar -> Menu superior fixo
.card -> Containers de conteúdo
.card-section -> Seções dentro dos cards
.list-item -> Itens de lista
.grid -> Layout em grade
\`\`\`

### Componentes Comuns
- Cards para conteúdo
- Seções para organização
- Grids para layout
- Listas para dados

## Segurança
- Autenticação de usuários
- Middleware de admin
- CSRF Protection
- Validação de formulários
- Sessões seguras

## Manutenção
- Logs em `storage/logs`
- Cache em `storage/framework/cache`
- Sessões em `storage/framework/sessions`
- Views compiladas em `storage/framework/views`

## Versionamento
- Controle via Git
- Repositório no GitHub
- Versão atual: 1.0.1

---

Esta documentação será atualizada conforme novas funcionalidades forem desenvolvidas.
