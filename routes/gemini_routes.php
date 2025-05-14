<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GeminiController;

/**
 * Rotas para o serviço de categorização de transações via Gemini AI
 */

Route::middleware(['auth'])->group(function () {
    // Rota para categorizar transações com IA
    Route::post('/api/transactions/categorize', [GeminiController::class, 'categorizeTransactions'])
        ->name('api.transactions.categorize');
        
    // Rota para análise em tempo real durante importação
    Route::post('/api/transactions/analyze-realtime', [GeminiController::class, 'analyzeTransactionsRealtime'])
        ->name('api.transactions.analyze-realtime');
});
