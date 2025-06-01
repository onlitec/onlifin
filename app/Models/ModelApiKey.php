<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModelApiKey extends Model
{
    protected $fillable = [
        'provider',
        'model',
        'api_token',
        'system_prompt',
        'chat_prompt',
        'import_prompt',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    /**
     * Busca a chave API para um modelo específico
     */
    public static function getForModel(string $provider, string $model)
    {
        return static::where('provider', $provider)
            ->where('model', $model)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Verifica se existe uma chave API configurada para o modelo
     */
    public static function hasConfigForModel(string $provider, string $model): bool
    {
        return static::where('provider', $provider)
            ->where('model', $model)
            ->where('is_active', true)
            ->exists();
    }
    
    /**
     * Obtém todas as configurações ativas para um provedor
     */
    public static function getForProvider(string $provider)
    {
        return static::where('provider', $provider)
            ->where('is_active', true)
            ->get();
    }
}
