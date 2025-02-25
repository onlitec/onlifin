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

// Rotas públicas
Route::get('/', function () {
    return redirect()->route('login');
})->middleware('guest');

// Rotas de autenticação
Route::middleware('guest')->group(function () {
    Route::get('login', Login::class)->name('login');
    Route::get('register', Register::class)->name('register');
    
    // Rotas de recuperação de senha
    Route::get('forgot-password', \App\Livewire\Auth\ForgotPassword::class)->name('password.request');
    Route::get('reset-password/{token}', \App\Livewire\Auth\ResetPassword::class)->name('password.reset');
});

// Rotas protegidas
Route::middleware(['web', 'auth'])->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index']);
    
    // Transactions - Agrupe todas as rotas de transações aqui
    Route::prefix('transactions')->group(function () {
        Route::get('/', [TransactionController::class, 'index'])->name('transactions');
        Route::get('/create', [TransactionController::class, 'create'])->name('transactions.create');
        Route::post('/', [TransactionController::class, 'store'])->name('transactions.store');
        Route::get('/{transaction}/edit', [TransactionController::class, 'edit'])->name('transactions.edit');
        Route::put('/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
        Route::delete('/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');
        Route::patch('/{transaction}/mark-as-paid', [TransactionController::class, 'markAsPaid'])->name('transactions.mark-as-paid');
    });
    
    // Rotas de Despesas
    Route::get('/expenses', \App\Livewire\Expenses\ExpenseList::class)->name('expenses.index');
    
    // Rotas de Receitas
    Route::get('/incomes', \App\Livewire\Incomes\IncomeList::class)->name('incomes.index');

    // Categories
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories');
    Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    
    // Accounts
    Route::get('/accounts', [AccountController::class, 'index'])->name('accounts');
    Route::get('/accounts/create', [AccountController::class, 'create'])->name('accounts.create');
    Route::post('/accounts', [AccountController::class, 'store'])->name('accounts.store');
    Route::get('/accounts/{account}/edit', [AccountController::class, 'edit'])->name('accounts.edit');
    Route::put('/accounts/{account}', [AccountController::class, 'update'])->name('accounts.update');
    Route::delete('/accounts/{account}', [AccountController::class, 'destroy'])->name('accounts.destroy');

    // Configurações (protegidas por middleware admin)
    Route::prefix('settings')->name('settings.')->middleware(['web', 'auth', AdminMiddleware::class])->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::get('/users', [SettingsController::class, 'users'])->name('users');
        Route::get('/roles', [SettingsController::class, 'roles'])->name('roles');
        
        // Rotas de Relatórios
        Route::get('/reports', [SettingsController::class, 'reports'])->name('reports');
        Route::post('/reports/transactions', [SettingsController::class, 'generateTransactionsReport'])->name('reports.transactions');
        
        // Rotas de Backup
        Route::get('/backup', [SettingsController::class, 'backup'])->name('backup');
        Route::post('/backup', [SettingsController::class, 'createBackup'])->name('backup.create');
        Route::get('/backup/{filename}', [SettingsController::class, 'downloadBackup'])->name('backup.download');
        Route::delete('/backup/{filename}', [SettingsController::class, 'deleteBackup'])->name('backup.delete');
        Route::post('/backup/restore', [SettingsController::class, 'restoreBackup'])->name('backup.restore');
    });

    // Rota de teste para o middleware admin
    Route::get('/test-admin', function () {
        return 'Se você vê isso, você é admin!';
    })->middleware(AdminMiddleware::class);
});

// Rota de logout
Route::post('/logout', function () {
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect('/');
})->middleware('auth')->name('logout');
