<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Category;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\AIController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Rotas de autenticação (públicas)
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);

    // Rotas protegidas de autenticação
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('logout-all', [AuthController::class, 'logoutAll']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
        Route::get('verify-token', [AuthController::class, 'verifyToken']);
        Route::get('tokens', [AuthController::class, 'tokens']);
        Route::delete('tokens/{tokenId}', [AuthController::class, 'revokeToken']);
    });
});

// Rotas protegidas da API
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {

    // Usuário autenticado
    Route::get('/user', function (Request $request) {
        return response()->json([
            'success' => true,
            'data' => ['user' => $request->user()]
        ]);
    });

    // Transações
    Route::prefix('transactions')->group(function () {
        Route::get('/', [TransactionController::class, 'index']);
        Route::post('/', [TransactionController::class, 'store']);
        Route::get('/summary', [TransactionController::class, 'summary']);
        Route::get('/{id}', [TransactionController::class, 'show']);
        Route::put('/{id}', [TransactionController::class, 'update']);
        Route::delete('/{id}', [TransactionController::class, 'destroy']);
    });

    // Contas
    Route::prefix('accounts')->group(function () {
        Route::get('/', [AccountController::class, 'index']);
        Route::post('/', [AccountController::class, 'store']);
        Route::get('/summary', [AccountController::class, 'summary']);
        Route::get('/{id}', [AccountController::class, 'show']);
        Route::put('/{id}', [AccountController::class, 'update']);
        Route::delete('/{id}', [AccountController::class, 'destroy']);
    });

    // Categorias
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::post('/', [CategoryController::class, 'store']);
        Route::get('/stats', [CategoryController::class, 'stats']);
        Route::get('/{id}', [CategoryController::class, 'show']);
        Route::put('/{id}', [CategoryController::class, 'update']);
        Route::delete('/{id}', [CategoryController::class, 'destroy']);
    });

    // Relatórios
    Route::prefix('reports')->group(function () {
        Route::get('/dashboard', [ReportController::class, 'dashboard']);
        Route::get('/cash-flow', [ReportController::class, 'cashFlow']);
        Route::get('/by-category', [ReportController::class, 'byCategory']);
        Route::get('/by-account', [ReportController::class, 'byAccount']);
    });

    // Configurações
    Route::prefix('settings')->group(function () {
        Route::get('/', [SettingsController::class, 'index']);
        Route::put('/profile', [SettingsController::class, 'updateProfile']);
        Route::post('/profile/photo', [SettingsController::class, 'updateProfilePhoto']);
        Route::put('/password', [SettingsController::class, 'updatePassword']);
        Route::put('/notifications', [SettingsController::class, 'updateNotifications']);
        Route::put('/two-factor', [SettingsController::class, 'updateTwoFactor']);
        Route::delete('/account', [SettingsController::class, 'deleteAccount']);
        Route::get('/export', [SettingsController::class, 'exportData']);
    });

    // Inteligência Artificial
    Route::prefix('ai')->group(function () {
        Route::post('/chat', [AIController::class, 'chat']);
        Route::post('/analysis', [AIController::class, 'financialAnalysis']);
        Route::post('/categorization', [AIController::class, 'categorizationSuggestions']);
        Route::get('/insights', [AIController::class, 'insights']);
    });
});

// Categorias por tipo (compatibilidade com sistema existente)
/*
 * ATENÇÃO: Endpoint /api/categories retorna todas as categorias por tipo.
 * NÃO MODIFICAR ESSA LÓGICA SEM AUTORIZAÇÃO EXPLÍCITA.
 */
Route::middleware(['web', 'auth'])->get('/categories', function (Request $request) {
    $type = $request->type ?? 'expense';
    $userId = auth()->id();
    if (!$userId) {
        return response()->json([], 401);
    }

    // Return all categories by type
    return Category::where('type', $type)
        ->orderBy('name')
        ->get(['id', 'name']);
});

// Rotas de Notificações
Route::middleware('auth:sanctum')->prefix('notifications')->group(function () {
    Route::get('/unread', [App\Http\Controllers\NotificationController::class, 'getUnreadNotifications']);
    Route::post('/mark-as-read', [App\Http\Controllers\NotificationController::class, 'markAsRead']);
    Route::post('/settings', [App\Http\Controllers\NotificationController::class, 'updateSettings']);
}); 

// Documentação da API (públicas)
Route::get('/docs', [App\Http\Controllers\Api\DocumentationController::class, 'index']);
Route::get('/docs/openapi', [App\Http\Controllers\Api\DocumentationController::class, 'openapi']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/chatbot/ask', [App\Http\Controllers\GoogleChatbotController::class, 'ask']);
});