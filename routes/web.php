<?php

/*
 * ========================================================================
 * ARQUIVO PROTEGIDO - MODIFICAÇÕES REQUEREM AUTORIZAÇÃO EXPLÍCITA
 * ========================================================================
 * 
 * ATENÇÃO: Este arquivo contém as rotas críticas do sistema.
 * Qualquer modificação deve ser previamente autorizada e documentada.
 * 
 * Responsável: Equipe de Desenvolvimento
 * Última modificação autorizada: 2025-05-28
 * 
 * Para solicitar modificações, entre em contato com a equipe responsável.
 * ========================================================================
 */

use Illuminate\Support\Facades\Route;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Settings\SystemSettings;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Middleware\AdminMiddleware;
use App\Livewire\Dashboard;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\SettingsController;
use App\Livewire\Transactions\Income;
use App\Livewire\Transactions\Expenses;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReplicateSettingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\NotificationConfigController;
use App\Http\Controllers\SystemLogController;
use App\Http\Controllers\TransactionExportController;
use App\Http\Controllers\ModelApiKeyController;
use App\Http\Controllers\OpenRouterConfigController;
use App\Http\Controllers\AIProviderConfigController;
use App\Http\Controllers\TempStatementImportController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\GoogleChatbotController;
use App\Http\Controllers\FinancialReportController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route::get('/', function () {
    // return view('welcome');
// });

// Rotas públicas
// Route::middleware('guest')->group(function () {
    // Route::get('/', function () {
        // return redirect()->route('login');
    // });

    // Route::get('login', Login::class)->name('login');
    // Route::get('register', Register::class)->name('register');
    
    // Rotas de recuperação de senha
    // Route::get('forgot-password', \App\Livewire\Auth\ForgotPassword::class)->name('password.request');
    // Route::get('reset-password/{token}', \App\Livewire\Auth\ResetPassword::class)->name('password.reset');
// });

// Remover a rota pública de upload de extratos
// // Route::post('/statements/upload', [TempStatementImportController::class, 'upload'])->name('statements.upload');

// Rotas protegidas
// Route::middleware(['auth'])->group(function () {
    // Dashboard
    // Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    // Route::get('/dashboard', [DashboardController::class, 'index']);
    
    // Logs do sistema
    // Route::prefix('settings')->name('settings.')->middleware(['auth'])->group(function () {
        // Route::get('/logs', [SystemLogController::class, 'index'])->name('logs.index');
        // Route::get('/logs/files', [SystemLogController::class, 'files'])->name('logs.files');
        // Route::get('/logs/{log}', [SystemLogController::class, 'show'])->name('logs.show');
        // Route::get('/logs/view/{log}', [SystemLogController::class, 'view'])->name('logs.view');
    // });
    
    // Perfil do usuário
    // Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    // Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    
    // Transações
    // Route::prefix('transactions')->name('transactions.')->group(function () {
        // Route::get('/', [TransactionController::class, 'index'])

        // Route::get('/income', [TransactionController::class, 'showIncome'])

        // Route::get('/expenses', [TransactionController::class, 'showExpenses'])

        // Route::get('/create/{type?}', [TransactionController::class, 'create'])

        // Route::post('/', [TransactionController::class, 'store'])

        // Route::get('/{transaction}/edit', [TransactionController::class, 'edit'])

        // Route::put('/{transaction}', [TransactionController::class, 'update'])

        // Route::delete('/{transaction}', [TransactionController::class, 'destroy'])

        // Route::patch('/{transaction}/mark-as-paid', [TransactionController::class, 'markAsPaid'])

        // Route::post('/{transaction}/create-next', [TransactionController::class, 'createNext'])

    // });
    
    // Importação Temporária (com Ajax e IA)
    // Rota de importação de transações para compatibilidade com views que usam transactions.import
    // // Route::get('/transactions/import', [TempStatementImportController::class, 'index'])->name('transactions.import');
    // // Route::get('/statements/import', [TempStatementImportController::class, 'index'])->name('statements.import');
    // // Restaurar a rota de upload dentro do middleware auth
    // // Route::post('/statements/upload', [TempStatementImportController::class, 'upload'])->name('statements.upload');
    // // Rota de Mapeamento agora usa TempStatementImportController
    // // // Route::get('/mapping', [TempStatementImportController::class, 'showMapping'])->name('mapping'); 
    // // Rota para salvar as transações mapeadas (precisa ser POST)
    // // Route::post('/mapping/save', [TempStatementImportController::class, 'saveTransactions'])->name('statements.save'); // Nome da rota para salvar
    // 
    // // Rota para processar transações aprovadas após verificação de duplicatas
    // // Route::post('/transactions/process-approved', [StatementImportController::class, 'processApprovedTransactions'])->name('transactions.process-approved');

    // Rota antiga (comentada ou removida se não for mais usada)
    // // Route::get('/mapping', [App\Http\Controllers\FixedStatementImportController::class, 'showMapping'])->name('mapping'); 
    
    // Categorias
    // ROTAS DE CATEGORIAS COM SISTEMA DE AUTORIZAÇÃO HIERÁRQUICO
    // Route::prefix('categories')->name('categories.')->group(function () {
        // CORREÇÃO DE AUTORIZAÇÃO: Permite acesso com qualquer uma das duas permissões:
        // - view_own_categories: Ver apenas categorias próprias + do sistema
        // - view_all_categories: Ver todas as categorias (administradores)
        // Route::get('/', [CategoryController::class, 'index'])

        // Route::get('/create', [CategoryController::class, 'create'])

        // Route::post('/', [CategoryController::class, 'store'])

        // Route::get('/{category}/edit', [CategoryController::class, 'edit'])

        // Route::put('/{category}', [CategoryController::class, 'update'])

        // Route::delete('/{category}', [CategoryController::class, 'destroy'])

    // });
    
    // Contas
    // Route::prefix('accounts')->name('accounts.')->group(function () {
        // Route::get('/', [AccountController::class, 'index'])

        // Route::get('/create', [AccountController::class, 'create'])

        // Route::post('/', [AccountController::class, 'store'])

        // Route::get('/{account}/edit', [AccountController::class, 'edit'])

        // Route::put('/{account}', [AccountController::class, 'update'])

        // Route::delete('/{account}', [AccountController::class, 'destroy'])

    // });

    // Configurações - página principal acessível a todos os usuários
    // Route::get('/settings', [SettingsController::class, 'index'])->middleware(['auth'])->name('settings.index');
    
    // Configurações de notificações para todos os usuários
    // Route::get("/settings/notifications", [SettingsController::class, "notifications"])->middleware(["auth"])->name("settings.notifications");
    // Route::post("/settings/notifications", [SettingsController::class, "updateNotifications"])->middleware(["auth"])->name("settings.notifications.update");
    
    // Configurações (protegidas por middleware admin)
    // Route::prefix('settings')->name('settings.')->middleware(['auth'])->group(function () {
        // Route::get('/users', [SettingsController::class, 'users'])
            
        // Route::get('/users/new', [SettingsController::class, 'createUser'])
            
        // Route::post('/users/store', [SettingsController::class, 'storeUser'])
            
        // Route::get('/users/edit/{user}', [SettingsController::class, 'editUser'])
            
        // Route::put('/users/update/{user}', [SettingsController::class, 'updateUser'])
            
        // Route::get('/users/delete/{user}', [SettingsController::class, 'deleteUser'])
            
        // Route::get('/roles', [SettingsController::class, 'roles'])
            
        // Route::get('/roles/new', [SettingsController::class, 'createRole'])
            
        // Route::post('/roles/store', [SettingsController::class, 'storeRole'])
            
        // Route::get('/roles/edit/{role}', [SettingsController::class, 'editRole'])
            
        // Route::put('/roles/update/{role}', [SettingsController::class, 'updateRole'])
            
        // Route::get('/roles/delete/{role}', [SettingsController::class, 'deleteRole'])
            
        
        // Relatórios
        // Route::get('/reports', [SettingsController::class, 'reports'])->name('reports');
        // Route::post('/reports/transactions', [SettingsController::class, 'generateTransactionsReport'])->name('reports.transactions');
        
        // Backup
        // Route::get('/backup', [SettingsController::class, 'backup'])->name('backup');
        // Route::post('/backup', [SettingsController::class, 'createBackup'])->name('backup.create');
        // Route::get('/backup/{filename}', [SettingsController::class, 'downloadBackup'])->name('backup.download');
        // Route::delete('/backup/{filename}', [SettingsController::class, 'deleteBackup'])->name('backup.delete');
        // Route::post('/backup/restore', [SettingsController::class, 'restoreBackup'])->name('backup.restore');
        
        // Sistema de atualização da plataforma
        // Route::get('/system', [SettingsController::class, 'system'])->name('system');
        // Route::post('/system/backup', [SettingsController::class, 'backupPlatform'])->name('system.backup');
        // Route::post('/system/update', [SettingsController::class, 'updatePlatform'])->name('system.update');
        
        // Aparência: personalizar título e favicon do site
        // Route::get('/appearance', [SettingsController::class, 'appearance'])->name('appearance');
        // Route::post('/appearance', [SettingsController::class, 'updateAppearance'])->name('appearance.update');
        
        // Rotas do Replicate
        // Route::get('/replicate', [ReplicateSettingController::class, 'index'])->name('replicate.index');
        // Route::post('/replicate', [ReplicateSettingController::class, 'store'])->name('replicate.store');
        // Route::post('/replicate/test', [ReplicateSettingController::class, 'test'])->name('replicate.test');
        // Route::get('/replicate/get-settings/{provider}', [ReplicateSettingController::class, 'getSettings'])->name('replicate.get-settings');
        
        // Rotas para configuração de chaves API específicas por modelo
        // Route::get('/model-keys/edit/{modelKey}', [ModelApiKeyController::class, 'edit'])->name('model-keys.edit');
        // Route::put('/model-keys/{modelKey}', [ModelApiKeyController::class, 'update'])->name('model-keys.update');
        // Route::delete('/model-keys/{modelKey}', [ModelApiKeyController::class, 'destroy'])->name('model-keys.destroy');

        // Permissões
        // Route::get('/permissions', [SettingsController::class, 'permissions'])
            
        // Route::get('/permissions/new', [SettingsController::class, 'createPermission'])
            
        // Route::post('/permissions/store', [SettingsController::class, 'storePermission'])
            
        // Route::get('/permissions/edit/{permission}', [SettingsController::class, 'editPermission'])
            
        // Route::put('/permissions/update/{permission}', [SettingsController::class, 'updatePermission'])
            
        // Route::get('/permissions/delete/{permission}', [SettingsController::class, 'deletePermission'])
            

        // Rotas de logs do sistema
        // Route::get('/logs', [SystemLogController::class, 'index'])->name('logs.index');
        // Route::get('/logs/files', [SystemLogController::class, 'files'])->name('logs.files');
        // Route::get('/logs/{log}', [SystemLogController::class, 'show'])->name('logs.show');
        // Route::get('/logs/view/{log}', [SystemLogController::class, 'view'])->name('logs.view');
        
        // ****** Mover a rota para cá ******
        // Route::delete('/users/delete-data', [SettingsController::class, 'deleteUserData'])->name('deleteUserData'); 
        // *************************************
    // });

    // Rotas para o novo controlador de usuários (com middleware de permissão)
    // Route::middleware('permission:users.view_all')->get('/users', [App\Http\Controllers\UserController::class, 'index'])->name('users.index');
    // Route::middleware('permission:users.create')->get('/users/create', [App\Http\Controllers\UserController::class, 'create'])->name('users.create');
    // Route::middleware('permission:users.create')->post('/users', [App\Http\Controllers\UserController::class, 'store'])->name('users.store');

    // Rotas de notificações
    // Route::middleware(['auth'])->prefix('notifications')->name('notifications.')->group(function () {
        // Route::get('/', [App\Http\Controllers\NotificationController::class, 'index'])->name('index');
        // Route::get('/settings', [App\Http\Controllers\NotificationController::class, 'settings'])->name('settings');
        // Route::post('/settings', [App\Http\Controllers\NotificationController::class, 'updateSettings'])->name('update-settings');
        // Route::post('/test', [App\Http\Controllers\NotificationController::class, 'testNotification'])->name('test');
        // Route::post('/send-to-all', [App\Http\Controllers\NotificationController::class, 'sendToAll'])->name('send-to-all');
    // });

    // Rotas de notificações de vencimento
    // Route::middleware(['auth'])->prefix('notifications/due-date')->name('notifications.due-date.')->group(function () {
        // Route::get('/settings', [App\Http\Controllers\DueDateNotificationController::class, 'settings'])->name('settings');
        // Route::post('/settings', [App\Http\Controllers\DueDateNotificationController::class, 'updateSettings'])->name('update-settings');
        // Route::post('/test', [App\Http\Controllers\DueDateNotificationController::class, 'testNotification'])->name('test');
        // Route::post('/preview-template', [App\Http\Controllers\DueDateNotificationController::class, 'previewTemplate'])->name('preview-template');
        // Route::post('/run-check', [App\Http\Controllers\DueDateNotificationController::class, 'runCheck'])->middleware('admin')->name('run-check');
    // });
    
    // Rota direta para a página de configurações de notificações
    // Route::get('/settings/notifications', [NotificationConfigController::class, 'index'])->middleware(['auth'])->name('settings.notifications');
    
    // Rotas de configuração de notificações
    // Route::middleware(['auth'])->prefix('settings/notifications')->name('settings.notifications.')->group(function () {
        // Route::get('/', [NotificationConfigController::class, 'index'])->name('index');
        // Route::post('/', [NotificationConfigController::class, 'update'])->name('update');
        // Route::get('/whatsapp', [NotificationConfigController::class, 'whatsapp'])->name('whatsapp');
        // Route::get('/email', [NotificationConfigController::class, 'email'])->name('email');
        // Route::get('/push', [NotificationConfigController::class, 'push'])->name('push');
        // Route::get('/templates', [NotificationConfigController::class, 'templates'])->name('templates');
        // Route::post('/test', [NotificationConfigController::class, 'sendTest'])->name('test');
    // });

    // Adicionar nova rota para obter transações via AJAX
    // // Route::get('/transactions/ajax/get', 'App\Http\Controllers\TempStatementImportController@getTransactions')
    //     ->name('transactions.ajax.get')
    //     ->middleware('auth');

    // Adicionar nova rota para testar a API do Gemini
    // Route::get('/test-gemini', [TempStatementImportController::class, 'testGeminiAPI'])->name('test.gemini');

    // Adicionando rotas para gerenciamento de chaves API
    // Route::middleware(['auth'])->group(function () {
        // Route::get('/api-key/create', [App\Http\Controllers\ApiKeyController::class, 'create'])->name('api-key.create');
        // Route::post('/api-key/store', [App\Http\Controllers\ApiKeyController::class, 'store'])->name('api-key.store');
    // });

    // Adicionando rotas para chat com provedor de IA
    // Route::middleware(['auth'])->group(function () {
        // Route::get('/chat', [App\Http\Controllers\ChatController::class, 'index'])->name('chat.index');
        // Route::post('/chat/send', [App\Http\Controllers\ChatController::class, 'sendMessage'])->name('chat.send');
    // });

    // Adicionar nova rota para o método uploadAndAnalyze no controlador BankStatementController
    // Route::post('/bank-statement-analyze', [App\Http\Controllers\BankStatementController::class, 'uploadAndAnalyze'])->name('bank-statement.analyze');

    // Rotas para configuração dos Provedores de IA
    // Route::prefix('iaprovider-config')->name('iaprovider-config.')->group(function () {
        // Route::get('/', [AIProviderConfigController::class, 'index'])->name('index');
        // Route::get('/create', [AIProviderConfigController::class, 'create'])->name('create');
        // Route::post('/', [AIProviderConfigController::class, 'store'])->name('store');
        // Route::get('/{config}/edit', [AIProviderConfigController::class, 'edit'])->name('edit');
        // Route::put('/{config}', [AIProviderConfigController::class, 'update'])->name('update');
        // Route::delete('/{config}', [AIProviderConfigController::class, 'destroy'])->name('destroy');
        // Route::post('/test', [AIProviderConfigController::class, 'testConnection'])->name('test');
    // });

    // Grupos de usuários
    // Route::get('/groups', [\App\Http\Controllers\GroupController::class, 'index'])->name('groups.index');
    // Route::get('/groups/create', [\App\Http\Controllers\GroupController::class, 'create'])->name('groups.create');
    // Route::post('/groups', [\App\Http\Controllers\GroupController::class, 'store'])->name('groups.store');
    // Route::get('/groups/{group}/edit', [\App\Http\Controllers\GroupController::class, 'edit'])->name('groups.edit');
    // Route::put('/groups/{group}', [\App\Http\Controllers\GroupController::class, 'update'])->name('groups.update');
    // Route::delete('/groups/{group}', [\App\Http\Controllers\GroupController::class, 'destroy'])->name('groups.destroy');

    // Sugestão de categoria por IA
    // Route::post('/api/transactions/suggest-category', [\App\Http\Controllers\TransactionController::class, 'suggestCategory'])->middleware('auth');

    // Painel de resumo inteligente de despesas/receitas
    // Route::get('/transactions/summary', [\App\Http\Controllers\TransactionController::class, 'dashboardSummary'])->middleware('auth')->name('transactions.summary');

    // Relatório financeiro detalhado com insights
    // Route::get('/settings/reports/financial', [\App\Http\Controllers\SettingsController::class, 'financialReport'])->middleware('auth')->name('settings.reports.financial');

    // Rotas do Chatbot Financeiro via Google
    // Route::middleware(['auth'])->group(function () {
        // Tela de chat e interações com Dialogflow
        // Route::get('/chatbot', [GoogleChatbotController::class, 'index'])->name('chatbot.index');
        // Route::post('/chatbot/ask', [GoogleChatbotController::class, 'ask'])->name('chatbot.ask');
    // });

    // Rotas para gerenciamento de empresas
    // Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');
    // Route::get('/companies/create', [CompanyController::class, 'create'])->name('companies.create');
    // Route::post('/companies', [CompanyController::class, 'store'])->name('companies.store');
    // Route::post('/companies/{company}/switch', [CompanyController::class, 'switch'])->name('companies.switch');
    // Route::get('/companies/{company}/edit', [CompanyController::class, 'edit'])->name('companies.edit');
    // Route::put('/companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
    // Route::delete('/companies/{company}', [CompanyController::class, 'destroy'])->name('companies.destroy');

    // Rotas para Relatórios Financeiros com Gráficos
    // Route::prefix('reports/financial')->name('reports.financial.')->middleware(['auth'])->group(function () {
        // Route::get('/', [FinancialReportController::class, 'index'])->name('index');
        // Route::get('/expenses-by-category', [FinancialReportController::class, 'expensesByCategory'])->name('expensesByCategory');
        // Adicionar aqui outras rotas para diferentes tipos de gráficos/relatórios no futuro
        // Ex: // Route::get('/income-vs-expenses', [FinancialReportController::class, 'incomeVsExpenses'])->name('incomeVsExpenses');
    // });

    /*
     * Importação de extratos
     */
    // Route::middleware(['auth'])->prefix('statements')->group(function () {
        // // Route::get('/import', [TempStatementImportController::class, 'index'])->name('transactions.import');
        // Route::post('/upload', [TempStatementImportController::class, 'upload'])->name('transactions.upload');
        // // Route::get('/mapping', [TempStatementImportController::class, 'showMapping'])->name('mapping');
        // Route::get('/transactions', [TempStatementImportController::class, 'getTransactions'])->name('transactions.get');
        // Route::post('/save', [TempStatementImportController::class, 'saveTransactions'])->name('transactions.save');
        // Route::post('/analyze', [TempStatementImportController::class, 'analyze'])->name('transactions.analyze');
        // // Route::post('/test-gemini', [TempStatementImportController::class, 'testGeminiAPI'])->name('test.gemini');
        
        // Rota para verificar o progresso da análise do extrato
        // Route::get('/analysis-progress', [TempStatementImportController::class, 'checkAnalysisProgress'])->name('statements.analysis.progress');
    // });

// });

// Rota de logout
// Route::post('/logout', function () {
    // Auth::logout();
    // session()->invalidate();
    // session()->regenerateToken();
    // return redirect('/');
// })->middleware('auth')->name('logout');

// Rota temporária para teste de menu
// Route::get('/menu-test', function () {
    // return view('menu-test');
// })->middleware(['auth'])->name('menu.test');

// Incluir rotas para configurações múltiplas de IA
require __DIR__.'/multiple-ai-config.php';
