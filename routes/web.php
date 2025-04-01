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
    
    // Perfil do usuário
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    
    // Transações
    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/', [TransactionController::class, 'index'])->name('index');
        Route::get('/income', Income::class)->name('income');
        Route::get('/expenses', Expenses::class)->name('expenses');
        Route::get('/create/{type?}', [TransactionController::class, 'create'])->name('create');
        Route::post('/', [TransactionController::class, 'store'])->name('store');
        Route::get('/{transaction}/edit', [TransactionController::class, 'edit'])->name('edit');
        Route::put('/{transaction}', [TransactionController::class, 'update'])->name('update');
        Route::delete('/{transaction}', [TransactionController::class, 'destroy'])->name('destroy');
        Route::patch('/{transaction}/mark-as-paid', [TransactionController::class, 'markAsPaid'])->name('mark-as-paid');
    });
    
    // Importação de extratos bancários
    Route::prefix('statements')->name('statements.')->group(function () {
        Route::get('/import', [App\Http\Controllers\StatementImportController::class, 'index'])->name('import');
        Route::post('/upload', [App\Http\Controllers\StatementImportController::class, 'upload'])->name('upload');
        Route::get('/mapping', [App\Http\Controllers\StatementImportController::class, 'showMapping'])->name('mapping');
        Route::post('/save', [App\Http\Controllers\StatementImportController::class, 'saveTransactions'])->name('save');
        Route::get('/config', [App\Http\Controllers\StatementImportController::class, 'showConfig'])->name('config');
        Route::post('/config', [App\Http\Controllers\StatementImportController::class, 'saveConfig'])->name('save-config');
    });
    
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
        
        // Configuração de IA para análise de extratos
        Route::get('/ai-config', [App\Http\Controllers\StatementImportController::class, 'showConfig'])->name('ai-config');
        Route::post('/ai-config', [App\Http\Controllers\StatementImportController::class, 'saveConfig'])->name('ai-config.save');
        
        // Rotas do Replicate
        Route::get('/replicate', [ReplicateSettingController::class, 'index'])->name('replicate.index');
        Route::post('/replicate', [ReplicateSettingController::class, 'store'])->name('replicate.store');
        Route::post('/replicate/test', [ReplicateSettingController::class, 'test'])->name('replicate.test');

        // Permissões
        Route::get('/permissions', [SettingsController::class, 'permissions'])->name('permissions');
        Route::get('/permissions/new', [SettingsController::class, 'createPermission'])->name('permissions.new');
        Route::post('/permissions/store', [SettingsController::class, 'storePermission'])->name('permissions.store');
        Route::get('/permissions/edit/{permission}', [SettingsController::class, 'editPermission'])->name('permissions.edit');
        Route::put('/permissions/update/{permission}', [SettingsController::class, 'updatePermission'])->name('permissions.update');
        Route::get('/permissions/delete/{permission}', [SettingsController::class, 'deletePermission'])->name('permissions.delete');

        // Rotas de logs do sistema
        Route::get('/logs', [SystemLogController::class, 'index'])->name('logs.index');
        Route::get('/logs/{log}', [SystemLogController::class, 'show'])->name('logs.show');
        Route::get('/logs/export', [SystemLogController::class, 'export'])->name('logs.export');
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
});

// Rota de logout
Route::post('/logout', function () {
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect('/');
})->middleware('auth')->name('logout');
