<?php

use App\Http\Controllers\MultipleAIConfigController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Multiple AI Configuration Routes
|--------------------------------------------------------------------------
|
| Rotas para gerenciar configurações múltiplas de IA por provedor
|
*/

// Grupo de rotas para configurações múltiplas de IA
Route::prefix('api/multiple-ai-config')->middleware(['web', 'auth'])->group(function () {
    
    // Listar todos os provedores disponíveis com estatísticas
    Route::get('/providers', [MultipleAIConfigController::class, 'getProviders'])
        ->name('multiple-ai-config.providers');
    
    // Obter estatísticas gerais
    Route::get('/stats', [MultipleAIConfigController::class, 'getStats'])
        ->name('multiple-ai-config.stats');
    
    // Rotas específicas por provedor
    Route::prefix('provider/{provider}')->group(function () {
        
        // Listar configurações de um provedor específico
        Route::get('/configurations', [MultipleAIConfigController::class, 'getProviderConfigurations'])
            ->name('multiple-ai-config.provider.configurations');
        
        // Configurar múltiplas IAs para um provedor
        Route::post('/configure', [MultipleAIConfigController::class, 'configureMultipleAIs'])
            ->name('multiple-ai-config.provider.configure');
        
        // Validar uma configuração antes de salvar
        Route::post('/validate', [MultipleAIConfigController::class, 'validateConfiguration'])
            ->name('multiple-ai-config.provider.validate');
        
        // Remover todas as configurações de um provedor
        Route::delete('/configurations', [MultipleAIConfigController::class, 'removeAllProviderConfigurations'])
            ->name('multiple-ai-config.provider.remove-all');
        
        // Rotas específicas por modelo
        Route::prefix('model/{model}')->group(function () {
            
            // Ativar/desativar uma configuração específica
            Route::patch('/toggle', [MultipleAIConfigController::class, 'toggleConfiguration'])
                ->name('multiple-ai-config.provider.model.toggle');
            
            // Remover uma configuração específica
            Route::delete('/', [MultipleAIConfigController::class, 'removeConfiguration'])
                ->name('multiple-ai-config.provider.model.remove');
        });
    });
});

// Rotas para interface web (se necessário)
Route::prefix('multiple-ai-config')->middleware(['web', 'auth'])->group(function () {
    
    // Página principal de configurações múltiplas
    Route::get('/', function () {
        return view('multiple-ai-config.index');
    })->name('multiple-ai-config.index');
    
    // Página de configuração por provedor
    Route::get('/provider/{provider}', function ($provider) {
        return view('multiple-ai-config.provider', compact('provider'));
    })->name('multiple-ai-config.provider');
});