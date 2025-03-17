<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReplicateSetting extends Model
{
    protected $fillable = [
        'api_token',
        'model_version',
        'system_prompt',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

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
        $settings = static::getActive();
        return $settings && !empty($settings->api_token);
    }
}
