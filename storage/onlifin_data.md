# Análise Detalhada da Plataforma Onlifin

Este documento descreve as funcionalidades e tecnologias identificadas na plataforma Onlifin, com mais detalhes sobre cada módulo.

## Funcionalidades Detalhadas

### 1. Autenticação e Gerenciamento de Usuários

*   **Autenticação:**
    *   Login (`App\Livewire\Auth\Login`).
    *   Registro (`App\Livewire\Auth\Register`).
    *   Recuperação de Senha (envio de link por e-mail, `App\Livewire\Auth\ForgotPassword`).
    *   Reset de Senha (via link com token, `App\Livewire\Auth\ResetPassword`).
    *   Gerenciamento de Sessão (padrão Laravel).
    *   Logout.
*   **Gerenciamento de Perfil:**
    *   Usuário pode editar seu próprio nome e e-mail (`App\Http\Controllers\ProfileController`).
    *   Usuário pode alterar sua senha.
*   **Gerenciamento de Usuários (Admin - `App\Http\Controllers\SettingsController`):**
    *   CRUD completo para usuários (criar, listar, editar, deletar).
    *   Atribuição de status: `is_active` (boolean) para ativar/desativar login do usuário.
    *   Atribuição de papéis (Roles) aos usuários.
    *   Atribuição do status `is_admin` (boolean) - mecanismo simples de admin, pode coexistir ou ser substituído pelo sistema de Roles/Permissions.
*   **Modelo `User` (`App\Models\User`):**
    *   Campos: `name`, `email`, `password`, `is_admin`, `phone`, `is_active`, `email_verified_at`, `email_notifications`, `whatsapp_notifications`, `push_notifications`, `due_date_notifications`.
    *   Relacionamento `belongsToMany` com `Role`.
    *   Método `hasPermission()` para verificar permissões via Roles.
    *   Métodos `routeNotificationForWhatsapp()` e `routeNotificationForMail()` para notificações.
    *   Métodos `shouldReceiveNotification()` e `shouldReceiveWhatsApp()` para controlar envio de notificações.

### 2. Dashboard (`App\Http\Controllers\DashboardController`)

*   **Visão Geral:** Apresenta um resumo financeiro personalizável por período.
*   **Períodos Suportados:** Mês atual (padrão), Mês passado, Ano atual, Ano passado, Todo o período.
*   **Cards de Resumo:**
    *   Saldo Atual Total (calculado pela soma de transações pagas, `income - expense`).
    *   Receitas Totais (no período selecionado, status `paid`).
    *   Despesas Totais (no período selecionado, status `paid`).
    *   Balanço do Período (`income - expense` no período).
    *   Variação Percentual: Compara os totais de Receita, Despesa e Balanço com o período anterior correspondente (exceto para "Todo o período").
*   **Gráficos (usando Chart.js):**
    *   **Despesas por Categoria:** Gráfico de pizza/rosca mostrando as 10 maiores categorias de despesa no período.
    *   **Receitas por Categoria:** Gráfico similar para as 10 maiores categorias de receita.
    *   **Saldo ao Longo do Tempo:** Gráfico de linha mostrando a evolução do saldo dia a dia (atualmente implementado para o mês atual).
*   **Listas Rápidas:**
    *   Transações (Receitas e Despesas) do dia atual.
    *   Transações Pendentes (Receitas e Despesas) para os próximos 7 dias.

### 3. Gerenciamento de Transações

*   **Controlador Principal:** `App\Http\Controllers\TransactionController`.
*   **Modelo:** `App\Models\Transaction`.
*   **Livewire Components:** `App\Livewire\Transactions\Income`, `App\Livewire\Transactions\Expenses` (provavelmente para listagem e filtros interativos).
*   **Funcionalidades:**
    *   Listagem paginada de todas as transações (`/transactions`).
    *   Listagem separada de Receitas (`/transactions/income`) e Despesas (`/transactions/expenses`).
    *   Criação de novas transações (formulário em `/transactions/create/{type?}`).
        *   Seleção de Tipo (Receita/Despesa), Status (Pendente/Pago), Data, Descrição, Valor, Categoria, Conta.
        *   Campo opcional para Notas.
        *   Suporte a Recorrência.
    *   Edição de transações existentes.
    *   Exclusão de transações.
    *   Marcar transação como Paga/Recebida (altera o status para `paid`).
    *   **Recorrência:**
        *   Tipos: `none`, `fixed` (recorrência fixa mensal), `installment` (parcelamento).
        *   Campos: `recurrence_type`, `installment_number`, `total_installments`, `next_date`.
        *   Funcionalidade `createNext`: Cria a próxima ocorrência de uma transação recorrente (fixa ou parcelada), atualizando a data e o número da parcela (se aplicável), e ajustando a `next_date` da transação original.
*   **Modelo `Transaction`:**
    *   Campos: `type`, `status`, `recurrence_type`, `installment_number`, `total_installments`, `next_date`, `date`, `description`, `amount` (armazenado como inteiro/centavos), `category_id`, `account_id`, `user_id`, `notes`.
    *   Relacionamentos `belongsTo` com `Category`, `Account`, `User`.
    *   Acessor `FormattedAmount` para exibir valor em Reais.
    *   Mutator `Amount` para converter o valor recebido (string formatada ou número) para centavos antes de salvar.
    *   Métodos auxiliares: `isPaid()`, `isPending()`, `hasRecurrence()`, `isFixedRecurrence()`, `isInstallmentRecurrence()`, `FormattedInstallment`.

### 4. Gerenciamento de Contas (`App\Http\Controllers\AccountController`)

*   **Modelo:** `App\Models\Account`.
*   **Funcionalidades:**
    *   CRUD completo para contas financeiras (bancos, carteiras, etc.).
    *   Associação obrigatória a um `user_id`.
*   **Modelo `Account`:**
    *   Campos: `name`, `type` (tipo da conta, ex: Corrente, Poupança, Carteira), `balance` (campo existe, mas pode não ser atualizado em tempo real - Dashboard calcula a partir das transações), `active` (boolean), `user_id`, `description`, `color`.
    *   Relacionamento `belongsTo` com `User`.
    *   Relacionamento `hasMany` com `Transaction`.

### 5. Gerenciamento de Categorias (`App\Http\Controllers\CategoryController`)

*   **Modelo:** `App\Models\Category`.
*   **Funcionalidades:**
    *   CRUD completo para categorias.
    *   Associação obrigatória a um `user_id`.
    *   Categorias são do tipo `income` ou `expense`.
*   **Modelo `Category`:**
    *   Campos: `name`, `type` ('income' ou 'expense'), `color`, `description`, `icon`, `user_id`.
    *   Relacionamento `hasMany` com `Expense` (possivelmente um erro, deveria ser `Transaction`?).

### 6. Importação de Extratos

*   **Controladores:** `App\Http\Controllers\TempStatementImportController` (fluxo principal com AJAX e IA), `App\Http\Controllers\FixedStatementImportController` (parece ser uma versão anterior).
*   **Fluxo (`TempStatementImportController`):**
    1.  **Upload (`index`, `upload`):**
        *   Usuário seleciona a conta e o arquivo de extrato (PDF, CSV, OFX, QIF, QFX, XLS, XLSX, TXT).
        *   Opção para usar IA na categorização.
        *   Upload via AJAX salva o arquivo em `storage/temp_uploads`.
        *   Retorna o caminho do arquivo, ID da conta e extensão para o frontend.
    2.  **Análise e Mapeamento (`showMapping`, chamado via JS após upload):**
        *   Lê o arquivo temporário (`extractTransactionsFromOFX`, `extractTransactionsFromCSV`, etc.).
        *   Se a opção `use_ai` estiver ativa:
            *   Chama a API configurada (Gemini/Replicate via `AIConfigService`) para sugerir categorias e tipos para as transações extraídas (`analyzeTransactionsWithAI`).
            *   Aplica as sugestões da IA às transações (`applyCategorizationToTransactions`).
        *   Busca categorias e contas do usuário.
        *   Exibe a lista de transações extraídas (com sugestões da IA, se houver) em uma tabela para o usuário revisar e ajustar (selecionar categoria, confirmar tipo, etc.).
    3.  **Salvar Transações (`saveTransactions`):**
        *   Recebe os dados da tabela de mapeamento (via POST).
        *   Valida os dados.
        *   Cria as `Transaction` no banco de dados.
        *   Exclui o arquivo temporário do extrato.
*   **Dependências:** `endeken/ofx-php-parser` para ler arquivos OFX.
*   **IA:** Integração com APIs externas (Gemini/Replicate) para categorização automática, configurável via `ReplicateSetting` e `ModelApiKey`.

### 7. Configurações (Admin - `App\Http\Controllers\SettingsController`)

*   **Gerenciamento de Papéis (Roles):**
    *   CRUD para Papéis.
    *   Atribuição de Permissões aos Papéis.
*   **Gerenciamento de Permissões:**
    *   CRUD para Permissões.
    *   Permissões são categorizadas (users, roles, transactions, etc.) para organização na UI.
*   **Backup:**
    *   Criar Backup: Gera um arquivo ZIP contendo arquivos do projeto (config, public, resources, routes, etc.) e um dump SQL do banco de dados (`dump.sql`). Arquivos/pastas como `.git`, `node_modules`, `vendor`, `storage/logs` são ignorados.
    *   Listar Backups: Mostra arquivos `.zip` na pasta `storage/app/backups`.
    *   Download de Backup.
    *   Excluir Backup.
    *   Restaurar Backup: Requer upload de um arquivo ZIP de backup. Extrai os arquivos para uma pasta temporária, substitui os arquivos atuais pelos do backup e executa o `dump.sql` no banco de dados.
*   **Configuração de IA (`ReplicateSettingController`, `ModelApiKeyController`):**
    *   Permite configurar chaves de API e endpoints para diferentes provedores de IA (Replicate, Gemini, etc.).
    *   Gerenciamento de chaves de API específicas por modelo (ex: chave diferente para `gemini-pro` e `mixtral`).
    *   Testar conexão com a API configurada.
*   **Visualização de Logs (`SystemLogController`):**
    *   Lista arquivos de log do Laravel (`storage/logs`).
    *   Permite visualizar o conteúdo dos arquivos de log.
    *   Permite exportar logs (provavelmente como arquivo de texto).
*   **Relatórios:**
    *   Interface para gerar relatórios (atualmente apenas relatório de transações implementado).
    *   Geração de relatório de transações em formato CSV.
*   **Exclusão de Dados do Usuário:**
    *   Funcionalidade para administradores excluírem todos os dados associados a um usuário específico (Transações, Contas, Categorias, etc.).

### 8. Notificações

*   **Controladores:** `NotificationController`, `DueDateNotificationController`, `NotificationConfigController`.
*   **Modelos:** `Notification`, `NotificationTemplate`, `NotificationSetting`, `DueDateNotificationSetting`.
*   **Canais Suportados:** E-mail, Banco de Dados (Laravel), WhatsApp (requer configuração externa e número de telefone no `User`), Push (requer configuração externa).
*   **Funcionalidades:**
    *   **Configuração Geral (`NotificationConfigController`):**
        *   Usuário pode habilitar/desabilitar canais de notificação (Email, WhatsApp, Push, DB) em seu perfil.
        *   Admin pode configurar templates de notificação (Email, WhatsApp) usando variáveis (ex: `{user_name}`, `{transaction_description}`).
        *   Testar envio de notificações para os canais configurados.
    *   **Notificações de Vencimento (`DueDateNotificationController`):**
        *   Configuração específica para notificações de contas a pagar/receber próximas do vencimento.
        *   Permite definir quantos dias antes/depois do vencimento enviar (ex: 3 dias antes, no dia, 1 dia depois).
        *   Habilitar/desabilitar canais específicos para estas notificações.
        *   Visualizar preview do template de notificação de vencimento.
        *   Testar envio da notificação de vencimento.
        *   Funcionalidade (Admin) para rodar a verificação (`runCheck`) manualmente (provavelmente também existe um comando agendado).
    *   **Visualização (`NotificationController`):**
        *   Listagem de notificações recebidas pelo usuário (provavelmente do canal `database`).
        *   Marcar notificações como lidas.
    *   **Envio:**
        *   Sistema pode enviar notificações (ex: `SystemNotification`).
        *   Notificações de vencimento são enviadas automaticamente (via `CheckDueDates` command/job).
        *   Admin pode enviar notificações de teste ou para todos os usuários.

### 9. API (`routes/api.php`)

*   Atualmente, parece conter rotas relacionadas à integração com Gemini (`GeminiController`), possivelmente para análise de texto ou outras funcionalidades internas, e não uma API pública para consumo externo.
*   Utiliza Laravel Sanctum para autenticação, indicando que pode ser usada por um SPA ou aplicativo móvel no futuro.

## Tecnologias Utilizadas (Revisão)

*   **Backend:**
    *   **Framework:** Laravel 11
    *   **Linguagem:** PHP 8.2+
    *   **Servidor:** PHP-FPM / Laravel Octane + RoadRunner
    *   **Banco de Dados:** Relacional (Migrations para `users`, `accounts`, `categories`, `transactions`, `roles`, `permissions`, `settings`, `notifications`, etc.)
    *   **ORM:** Eloquent
    *   **Autenticação:** Laravel Session, Laravel Sanctum (para API)
    *   **Jobs/Queues:** Laravel Queues
    *   **Cache:** Laravel Cache
    *   **Parsing OFX:** `endeken/ofx-php-parser`
    *   **Cliente HTTP:** Guzzle
    *   **Integração IA:** `google/cloud` (Gemini), APIs Replicate (configurável)
*   **Frontend:**
    *   **Engine:** Blade
    *   **JS Framework:** Alpine.js
    *   **Componentes Interativos:** Livewire 3
    *   **Assets:** Vite
    *   **CSS:** Tailwind CSS
    *   **Ícones:** Remix Icon
    *   **Libs JS:** `imask`, `sweetalert2`, `axios`, `wire-elements/modal`, `jantinnerezo/livewire-alert`
*   **Desenvolvimento & Ferramentas:**
    *   **Pacotes PHP:** Composer
    *   **Pacotes JS:** npm
    *   **Code Style PHP:** Laravel Pint
    *   **Code Style JS:** ESLint, Prettier
    *   **Testes:** PHPUnit
    *   **Dev Local:** Laravel Sail (opcional)
*   **Outros:**
    *   **Logging:** Laravel Logging, `SystemLogController` para visualização.

Este documento fornece uma visão mais aprofundada das funcionalidades da plataforma Onlifin, com base na análise estática do código fonte.