<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ReplicateSetting extends Model
{
    protected $table = 'replicate_settings';

    protected $fillable = [
        'provider',
        'api_token',
        'endpoint',
        'model_version',
        'system_prompt',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    /**
     * Desativa todas as configurações existentes
     */
    public static function deactivateAll()
    {
        return static::where('is_active', true)->update(['is_active' => false]);
    }

    /**
     * Retorna as configurações ativas
     */
    public static function getActive()
    {
        return static::where('is_active', true)->first();
    }

    /**
     * Verifica se o Replicate está configurado e ativo
     */
    public static function isConfigured()
    {
        $settings = static::where('is_active', true)->first();
        
        Log::info('Verificando configuração da IA em detalhes', [
            'has_settings' => !empty($settings),
            'is_active' => $settings ? $settings->is_active : false,
            'has_api_token' => $settings ? !empty($settings->api_token) : false,
            'model_version' => $settings ? $settings->model_version : 'N/A',
            'provider' => $settings ? $settings->provider : 'N/A'
        ]);
        
        return $settings && 
               $settings->is_active && 
               !empty($settings->api_token) && 
               !empty($settings->model_version) && 
               !empty($settings->provider);
    }
}
