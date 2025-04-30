<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpenRouterConfig extends Model
{
    protected $table = 'open_router_configs';

    protected $fillable = [
        'provider',
        'model',
        'custom_model',
        'api_key',
        'endpoint',
        'system_prompt',
    ];
} 