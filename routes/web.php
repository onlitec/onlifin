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
use App\Http\Controllers\InstallController;

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

Route::get('/', function () {
    return view('welcome');
});

// Rotas públicas
Route::middleware('guest')->group(function () {
    Route::get('/', function () {
        return redirect()->route('login');
    });

    Route::get('login', Login::class)->name('login');
    Route::get('register', Register::class)->name('register');
    
    // Rotas de recuperação de senha
    Route::get('forgot-password', \App\Livewire\Auth\ForgotPassword::class)->name('password.request');
    Route::get('reset-password/{token}', \App\Livewire\Auth\ResetPassword::class)->name('password.reset');
});

// Rotas protegidas
Route::middleware(['auth'])->group(function () {
    // Dashboard
    // Rota raiz exibindo o dashboard (sem nome, para evitar duplicação)
    Route::get('/', [DashboardController::class, 'index']);
    // Rota nomeada do dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Logs do sistema
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/logs', [SystemLogController::class, 'index'])->name('logs.index');
        Route::get('/logs/files', [SystemLogController::class, 'files'])->name('logs.files');
        Route::get('/logs/{log}', [SystemLogController::class, 'show'])->name('logs.show');
        // Visualizar logs de arquivo (API ou Laravel)
        Route::get('/logs/view/{type}/{filename}', [SystemLogController::class, 'view'])->name('logs.view');
        // Exportar logs do sistema
        Route::get('/logs/export', [SystemLogController::class, 'export'])->name('logs.export');
    });
    
    // Relatórios
    Route::get('/settings/reports', [SettingsController::class, 'reports'])->name('settings.reports');
    Route::post('/settings/reports/transactions', [SettingsController::class, 'generateTransactionsReport'])->name('settings.reports.transactions');
    // Rotas para CSV de Rendimentos e Despesas por Conta Bancária
    Route::post('/settings/reports/incomes-by-account', [SettingsController::class, 'exportIncomesByAccount'])->name('settings.reports.incomes_by_account');
    Route::post('/settings/reports/expenses-by-account', [SettingsController::class, 'exportExpensesByAccount'])->name('settings.reports.expenses_by_account');
    
    // Rotas SSL/HTTPS
    Route::get('/settings/ssl', [SettingsController::class, 'ssl'])->name('settings.ssl');
    Route::post('/settings/ssl/generate', [SettingsController::class, 'sslGenerate'])->name('settings.ssl.generate');
    Route::post('/settings/ssl/renew', [SettingsController::class, 'sslRenew'])->name('settings.ssl.renew');
    Route::post('/settings/ssl/validate', [SettingsController::class, 'sslValidate'])->name('settings.ssl.validate');
    // Rota de diagnóstico
    Route::get('/settings/diagnostics', [SettingsController::class, 'diagnostics'])->name('settings.diagnostics');
    
    // Perfil do usuário
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    
    // Rotas para configuração dos Provedores de IA
    Route::prefix('iaprovider-config')->name('iaprovider-config.')->group(function () {
        Route::get('/', [AIProviderConfigController::class, 'index'])->name('index');
        Route::get('/create', [AIProviderConfigController::class, 'create'])->name('create');
        Route::post('/', [AIProviderConfigController::class, 'store'])->name('store');
        Route::get('/{config}/edit', [AIProviderConfigController::class, 'edit'])->name('edit');
        Route::put('/{config}', [AIProviderConfigController::class, 'update'])->name('update');
        Route::delete('/{config}', [AIProviderConfigController::class, 'destroy'])->name('destroy');
        Route::post('/test', [AIProviderConfigController::class, 'testConnection'])->name('test');
    });
    
    // Rotas do Chatbot
    Route::get('/chatbot', [ChatbotController::class, 'index'])->name('chatbot.index');
    Route::post('/chatbot/ask', [ChatbotController::class, 'ask'])->name('chatbot.ask');
    Route::post('/chatbot/process-message', [ChatbotController::class, 'processMessage'])
        ->middleware('auth')
        ->name('chatbot.processMessage');
    Route::post('/chatbot/upload-statement', [ChatbotController::class, 'uploadStatement'])->name('chatbot.uploadStatement');
    Route::post('/chatbot/process-statement', [ChatbotController::class, 'processStatement'])->name('chatbot.processStatement');
    
    // Importação de extratos
    Route::get('/transactions/import', [TempStatementImportController::class, 'index'])->name('transactions.import');
    Route::post('/transactions/upload', [TempStatementImportController::class, 'upload'])->name('transactions.upload');
    Route::get('/transactions/mapping', [TempStatementImportController::class, 'showMapping'])->name('mapping');
    Route::post('/transactions/save', [TempStatementImportController::class, 'saveTransactions'])->name('transactions.save');
    Route::post('/transactions/analyze', [TempStatementImportController::class, 'analyze'])->name('transactions.analyze');
    Route::get('/transactions/analysis-progress', [TempStatementImportController::class, 'checkAnalysisProgress'])->name('transactions.analysis.progress');
    // Rota AJAX para retornar as transações armazenadas na sessão para a página de mapeamento
    Route::get('/transactions/ajax/get', [TempStatementImportController::class, 'getTransactions'])->name('transactions.ajax.get');
    
    // Rotas para análise de extratos com IA
    Route::post('/transactions/analyze-with-ai', [TempStatementImportController::class, 'analyzeWithAI'])->name('statements.analyze-with-ai');
    Route::get('/transactions/review-categorized', [TempStatementImportController::class, 'reviewCategorizedTransactions'])->name('statements.review-categorized');
    
    // Transações
    Route::prefix('transactions')->name('transactions.')->group(function () {
        // Rota de listagem geral de transações (habilitada novamente)
        Route::get('/', [TransactionController::class, 'index'])
            ->middleware(\App\Http\Middleware\CheckPermission::class . ':view_own_transactions|view_all_transactions')
            ->name('index');
        Route::get('/income', [TransactionController::class, 'showIncome'])
            ->middleware(\App\Http\Middleware\CheckPermission::class . ':view_own_transactions|view_all_transactions')
            ->name('income');
        Route::get('/expenses', [TransactionController::class, 'showExpenses'])
            ->middleware(\App\Http\Middleware\CheckPermission::class . ':view_own_transactions|view_all_transactions')
            ->name('expenses');
        Route::get('/create/{type?}', [TransactionController::class, 'create'])
            ->middleware('permission:create_transactions')
            ->name('create');
        Route::post('/', [TransactionController::class, 'store'])
            ->middleware('permission:create_transactions')
            ->name('store');
        Route::get('/{transaction}/edit', [TransactionController::class, 'edit'])
            ->middleware('permission:edit_own_transactions|edit_all_transactions')
            ->name('edit');
        Route::put('/{transaction}', [TransactionController::class, 'update'])
            ->middleware('permission:edit_own_transactions|edit_all_transactions')
            ->name('update');
        Route::delete('/{transaction}', [TransactionController::class, 'destroy'])
            ->middleware('permission:delete_own_transactions|delete_all_transactions')
            ->name('destroy');
        Route::patch('/{transaction}/mark-as-paid', [TransactionController::class, 'markAsPaid'])
            ->middleware('permission:mark_as_paid_own_transactions|mark_as_paid_all_transactions')
            ->name('mark-as-paid');
        Route::post('/{transaction}/create-next', [TransactionController::class, 'createNext'])
            ->middleware('permission:create_transactions')
            ->name('create-next');
    });
    
    // Categorias
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])
            ->middleware('permission:view_own_categories|view_all_categories')
            ->name('index');
        Route::get('/create', [CategoryController::class, 'create'])
            ->middleware('permission:create_categories')
            ->name('create');
        Route::post('/', [CategoryController::class, 'store'])
            ->middleware('permission:create_categories')
            ->name('store');
        Route::get('/{category}/edit', [CategoryController::class, 'edit'])
            ->middleware('permission:edit_own_categories|edit_all_categories')
            ->name('edit');
        Route::put('/{category}', [CategoryController::class, 'update'])
            ->middleware('permission:edit_own_categories|edit_all_categories')
            ->name('update');
        Route::delete('/{category}', [CategoryController::class, 'destroy'])
            ->middleware('permission:delete_own_categories|delete_all_categories')
            ->name('destroy');
    });
    
    // Contas
    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/', [AccountController::class, 'index'])
            ->middleware('permission:view_own_accounts|view_all_accounts')
            ->name('index');
        Route::get('/create', [AccountController::class, 'create'])
            ->middleware('permission:create_accounts')
            ->name('create');
        Route::post('/', [AccountController::class, 'store'])
            ->middleware('permission:create_accounts')
            ->name('store');
        Route::get('/{account}/edit', [AccountController::class, 'edit'])
            ->middleware('permission:edit_own_accounts|edit_all_accounts')
            ->name('edit');
        Route::put('/{account}', [AccountController::class, 'update'])
            ->middleware('permission:edit_own_accounts|edit_all_accounts')
            ->name('update');
        Route::delete('/{account}', [AccountController::class, 'destroy'])
            ->middleware('permission:delete_own_accounts|delete_all_accounts')
            ->name('destroy');
    });

    // Adiciono rotas de empresas
    Route::prefix('companies')->name('companies.')->group(function () {
        Route::get('/', [CompanyController::class, 'index'])->name('index');
        Route::get('/create', [CompanyController::class, 'create'])->name('create');
        Route::post('/', [CompanyController::class, 'store'])->name('store');
        Route::post('/switch/{company}', [CompanyController::class, 'switch'])->name('switch');
        Route::get('/{company}/edit', [CompanyController::class, 'edit'])->name('edit');
        Route::put('/{company}', [CompanyController::class, 'update'])->name('update');
        Route::delete('/{company}', [CompanyController::class, 'destroy'])->name('destroy');
    });

    // Configurações - página principal acessível a todos os usuários
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    // Rotas de gerenciamento de usuários (Configurações)
    Route::get('/settings/users', [SettingsController::class, 'users'])->name('settings.users');
    Route::get('/settings/users/new', [SettingsController::class, 'createUser'])->name('settings.users.new');
    Route::post('/settings/users', [SettingsController::class, 'storeUser'])->name('settings.users.store');
    Route::get('/settings/users/{user}/edit', [SettingsController::class, 'editUser'])->name('settings.users.edit');
    Route::put('/settings/users/{user}', [SettingsController::class, 'updateUser'])->name('settings.users.update');
    Route::delete('/settings/users/{user}', [SettingsController::class, 'deleteUser'])->name('settings.users.delete');

    // Rotas de perfis (Configurações)
    Route::get('/settings/roles', [SettingsController::class, 'roles'])->name('settings.roles');
    Route::get('/settings/roles/new', [SettingsController::class, 'createRole'])->name('settings.roles.new');
    Route::post('/settings/roles', [SettingsController::class, 'storeRole'])->name('settings.roles.store');
    Route::get('/settings/roles/{role}/edit', [SettingsController::class, 'editRole'])->name('settings.roles.edit');
    Route::put('/settings/roles/{role}', [SettingsController::class, 'updateRole'])->name('settings.roles.update');
    Route::delete('/settings/roles/{role}', [SettingsController::class, 'deleteRole'])->name('settings.roles.delete');

    // Rotas de backup (Configurações)
    Route::get('/settings/backup', [SettingsController::class, 'backup'])->name('settings.backup');
    Route::post('/settings/backup/create', [SettingsController::class, 'createBackup'])->name('settings.backup.create');
    Route::post('/settings/backup/restore', [SettingsController::class, 'restoreBackup'])->name('settings.backup.restore');
    Route::get('/settings/backup/download/{filename}', [SettingsController::class, 'downloadBackup'])->name('settings.backup.download');
    Route::delete('/settings/backup/{filename}', [SettingsController::class, 'deleteBackup'])->name('settings.backup.delete');

    // Rotas de sistema (Configurações)
    Route::get('/settings/system', [SettingsController::class, 'system'])->name('settings.system');
    Route::post('/settings/system/update', [SettingsController::class, 'updatePlatform'])->name('settings.system.update');

    // Rotas de aparência (Configurações)
    Route::get('/settings/appearance', [SettingsController::class, 'appearance'])->name('settings.appearance');
    Route::post('/settings/appearance', [SettingsController::class, 'updateAppearance'])->name('settings.appearance.update');

    // Rota para exclusão de dados financeiros de usuário (Configurações)
    Route::delete('/settings/delete-user-data', [SettingsController::class, 'deleteUserData'])->name('settings.deleteUserData');

    // Adiciono rotas de notificações
    Route::prefix('settings/notifications')->name('settings.notifications.')->group(function () {
        Route::get('/', [NotificationConfigController::class, 'index'])->name('index');
        Route::get('/email', [NotificationConfigController::class, 'email'])->name('email');
        Route::get('/whatsapp', [NotificationConfigController::class, 'whatsapp'])->name('whatsapp');
        Route::get('/push', [NotificationConfigController::class, 'push'])->name('push');
        Route::get('/templates', [NotificationConfigController::class, 'templates'])->name('templates');
        Route::post('/', [NotificationConfigController::class, 'update'])->name('update');
        Route::post('/test', [NotificationConfigController::class, 'sendTest'])->name('sendTest');
    });
});

// Rota de logout
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

Route::get('/install', [InstallController::class, 'install'])->name('install');
