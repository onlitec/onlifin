<?php

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
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index']);
    
    // Logs do sistema
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::middleware(['auth'])->group(function () {
            Route::get('/logs', [SystemLogController::class, 'index'])->name('logs.index');
            Route::get('/logs/export', [SystemLogController::class, 'export'])->name('logs.export');
            Route::get('/logs/view/{type}/{filename}', [SystemLogController::class, 'view'])->name('logs.view');
            Route::get('/logs/{log}', [SystemLogController::class, 'show'])->name('logs.show');
        });
    });
    
    // Perfil do usuário
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    
    // Transações
    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/', [TransactionController::class, 'index'])->name('index');
        Route::get('/income', [TransactionController::class, 'showIncome'])->name('income');
        Route::get('/expenses', [TransactionController::class, 'showExpenses'])->name('expenses');
        Route::get('/create/{type?}', [TransactionController::class, 'create'])->name('create');
        Route::post('/', [TransactionController::class, 'store'])->name('store');
        Route::get('/{transaction}/edit', [TransactionController::class, 'edit'])->name('edit');
        Route::put('/{transaction}', [TransactionController::class, 'update'])->name('update');
        Route::delete('/{transaction}', [TransactionController::class, 'destroy'])->name('destroy');
        Route::patch('/{transaction}/mark-as-paid', [TransactionController::class, 'markAsPaid'])->name('mark-as-paid');
        Route::post('/{transaction}/create-next', [TransactionController::class, 'createNext'])->name('create-next');
    });
    
    // Importação Temporária (com Ajax e IA)
    Route::get('/statements/import', [App\Http\Controllers\TempStatementImportController::class, 'index'])->name('statements.import');
    Route::post('/statements/upload', [App\Http\Controllers\TempStatementImportController::class, 'upload'])->name('statements.upload'); // Rota do Ajax e fallback
    // Rota de Mapeamento agora usa TempStatementImportController
    Route::get('/mapping', [App\Http\Controllers\TempStatementImportController::class, 'showMapping'])->name('mapping'); 
    // Rota para salvar as transações mapeadas (precisa ser POST)
    Route::post('/mapping/save', [App\Http\Controllers\TempStatementImportController::class, 'saveTransactions'])->name('statements.save'); // Nome da rota para salvar

    // Rota antiga (comentada ou removida se não for mais usada)
    // Route::get('/mapping', [App\Http\Controllers\FixedStatementImportController::class, 'showMapping'])->name('mapping'); 
    
    // Categorias
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::get('/create', [CategoryController::class, 'create'])->name('create');
        Route::post('/', [CategoryController::class, 'store'])->name('store');
        Route::get('/{category}/edit', [CategoryController::class, 'edit'])->name('edit');
        Route::put('/{category}', [CategoryController::class, 'update'])->name('update');
        Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('destroy');
    });
    
    // Contas
    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/', [AccountController::class, 'index'])->name('index');
        Route::get('/create', [AccountController::class, 'create'])->name('create');
        Route::post('/', [AccountController::class, 'store'])->name('store');
        Route::get('/{account}/edit', [AccountController::class, 'edit'])->name('edit');
        Route::put('/{account}', [AccountController::class, 'update'])->name('update');
        Route::delete('/{account}', [AccountController::class, 'destroy'])->name('destroy');
    });

    // Configurações - página principal acessível a todos os usuários
    Route::get('/settings', [SettingsController::class, 'index'])->middleware(['auth'])->name('settings.index');
    
    // Configurações de notificações para todos os usuários
    Route::get("/settings/notifications", [SettingsController::class, "notifications"])->middleware(["auth"])->name("settings.notifications");
    Route::post("/settings/notifications", [SettingsController::class, "updateNotifications"])->middleware(["auth"])->name("settings.notifications.update");
    
    // Configurações (protegidas por middleware admin)
    Route::prefix('settings')->name('settings.')->middleware(['auth', AdminMiddleware::class])->group(function () {
        Route::get('/users', [SettingsController::class, 'users'])->name('users');
        Route::get('/users/new', [SettingsController::class, 'createUser'])->name('users.new');
        Route::post('/users/store', [SettingsController::class, 'storeUser'])->name('users.store');
        Route::get('/users/edit/{user}', [SettingsController::class, 'editUser'])->name('users.edit');
        Route::put('/users/update/{user}', [SettingsController::class, 'updateUser'])->name('users.update');
        Route::get('/users/delete/{user}', [SettingsController::class, 'deleteUser'])->name('users.delete');
        Route::get('/roles', [SettingsController::class, 'roles'])->name('roles');
        Route::get('/roles/new', [SettingsController::class, 'createRole'])->name('roles.new');
        Route::post('/roles/store', [SettingsController::class, 'storeRole'])->name('roles.store');
        Route::get('/roles/edit/{role}', [SettingsController::class, 'editRole'])->name('roles.edit');
        Route::put('/roles/update/{role}', [SettingsController::class, 'updateRole'])->name('roles.update');
        Route::get('/roles/delete/{role}', [SettingsController::class, 'deleteRole'])->name('roles.delete');
        
        // Relatórios
        Route::get('/reports', [SettingsController::class, 'reports'])->name('reports');
        Route::post('/reports/transactions', [SettingsController::class, 'generateTransactionsReport'])->name('reports.transactions');
        
        // Backup
        Route::get('/backup', [SettingsController::class, 'backup'])->name('backup');
        Route::post('/backup', [SettingsController::class, 'createBackup'])->name('backup.create');
        Route::get('/backup/{filename}', [SettingsController::class, 'downloadBackup'])->name('backup.download');
        Route::delete('/backup/{filename}', [SettingsController::class, 'deleteBackup'])->name('backup.delete');
        Route::post('/backup/restore', [SettingsController::class, 'restoreBackup'])->name('backup.restore');
        
        // Rotas do Replicate
        Route::get('/replicate', [ReplicateSettingController::class, 'index'])->name('replicate.index');
        Route::post('/replicate', [ReplicateSettingController::class, 'store'])->name('replicate.store');
        Route::post('/replicate/test', [ReplicateSettingController::class, 'test'])->name('replicate.test');
        Route::get('/replicate/get-settings/{provider}', [ReplicateSettingController::class, 'getSettings'])->name('replicate.get-settings');
        
        // Rotas para configuração de chaves API específicas por modelo
        Route::get('/model-keys', [ModelApiKeyController::class, 'index'])->name('model-keys.index');
        Route::post('/model-keys', [ModelApiKeyController::class, 'store'])->name('model-keys.store');
        Route::post('/model-keys/test', [ModelApiKeyController::class, 'testConnection'])->name('model-keys.test');
        Route::get('/model-keys/edit/{modelKey}', [ModelApiKeyController::class, 'edit'])->name('model-keys.edit');
        Route::put('/model-keys/{modelKey}', [ModelApiKeyController::class, 'update'])->name('model-keys.update');
        Route::delete('/model-keys/{modelKey}', [ModelApiKeyController::class, 'destroy'])->name('model-keys.destroy');

        // Permissões
        Route::get('/permissions', [SettingsController::class, 'permissions'])->name('permissions');
        Route::get('/permissions/new', [SettingsController::class, 'createPermission'])->name('permissions.new');
        Route::post('/permissions/store', [SettingsController::class, 'storePermission'])->name('permissions.store');
        Route::get('/permissions/edit/{permission}', [SettingsController::class, 'editPermission'])->name('permissions.edit');
        Route::put('/permissions/update/{permission}', [SettingsController::class, 'updatePermission'])->name('permissions.update');
        Route::get('/permissions/delete/{permission}', [SettingsController::class, 'deletePermission'])->name('permissions.delete');

        // Rotas de logs do sistema
        Route::get('/settings/logs', [SystemLogController::class, 'index'])->name('settings.logs.index');
        Route::get('/settings/logs/export', [SystemLogController::class, 'export'])->name('settings.logs.export');
        Route::get('/transactions/export', [TransactionExportController::class, 'export'])->name('transactions.export');
        
        // ****** Mover a rota para cá ******
        Route::delete('/users/delete-data', [SettingsController::class, 'deleteUserData'])->name('deleteUserData'); 
        // *************************************
    });

    // Rotas para o novo controlador de usuários (com middleware de permissão)
    Route::middleware('permission:users.view_all')->get('/users', [App\Http\Controllers\UserController::class, 'index'])->name('users.index');
    Route::middleware('permission:users.create')->get('/users/create', [App\Http\Controllers\UserController::class, 'create'])->name('users.create');
    Route::middleware('permission:users.create')->post('/users', [App\Http\Controllers\UserController::class, 'store'])->name('users.store');

    // Rotas de notificações
    Route::middleware(['auth'])->prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [App\Http\Controllers\NotificationController::class, 'index'])->name('index');
        Route::get('/settings', [App\Http\Controllers\NotificationController::class, 'settings'])->name('settings');
        Route::post('/settings', [App\Http\Controllers\NotificationController::class, 'updateSettings'])->name('update-settings');
        Route::post('/test', [App\Http\Controllers\NotificationController::class, 'testNotification'])->name('test');
        Route::post('/send-to-all', [App\Http\Controllers\NotificationController::class, 'sendToAll'])->name('send-to-all');
    });

    // Rotas de notificações de vencimento
    Route::middleware(['auth'])->prefix('notifications/due-date')->name('notifications.due-date.')->group(function () {
        Route::get('/settings', [App\Http\Controllers\DueDateNotificationController::class, 'settings'])->name('settings');
        Route::post('/settings', [App\Http\Controllers\DueDateNotificationController::class, 'updateSettings'])->name('update-settings');
        Route::post('/test', [App\Http\Controllers\DueDateNotificationController::class, 'testNotification'])->name('test');
        Route::post('/preview-template', [App\Http\Controllers\DueDateNotificationController::class, 'previewTemplate'])->name('preview-template');
        Route::post('/run-check', [App\Http\Controllers\DueDateNotificationController::class, 'runCheck'])->middleware('admin')->name('run-check');
    });
    
    // Rota direta para a página de configurações de notificações
    Route::get('/settings/notifications', [NotificationConfigController::class, 'index'])->middleware(['auth'])->name('settings.notifications');
    
    // Rotas de configuração de notificações
    Route::middleware(['auth'])->prefix('settings/notifications')->name('settings.notifications.')->group(function () {
        Route::get('/', [NotificationConfigController::class, 'index'])->name('index');
        Route::post('/', [NotificationConfigController::class, 'update'])->name('update');
        Route::get('/whatsapp', [NotificationConfigController::class, 'whatsapp'])->name('whatsapp');
        Route::get('/email', [NotificationConfigController::class, 'email'])->name('email');
        Route::get('/push', [NotificationConfigController::class, 'push'])->name('push');
        Route::get('/templates', [NotificationConfigController::class, 'templates'])->name('templates');
        Route::post('/test', [NotificationConfigController::class, 'sendTest'])->name('test');
    });

    // Adicionar nova rota para obter transações via AJAX
    Route::get('/transactions/ajax/get', 'App\Http\Controllers\TempStatementImportController@getTransactions')
        ->name('transactions.ajax.get')
        ->middleware('auth');
});

// Rota de logout
Route::post('/logout', function () {
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect('/');
})->middleware('auth')->name('logout');
