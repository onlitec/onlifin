<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Category;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Categorias por tipo
Route::get('/categories', function (Request $request) {
    $type = $request->type ?? 'expense';
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

// Documentação da API
Route::get('/docs', [App\Http\Controllers\Api\DocumentationController::class, 'index']);
Route::get('/docs/openapi', [App\Http\Controllers\Api\DocumentationController::class, 'openapi']);