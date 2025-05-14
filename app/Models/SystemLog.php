<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'module',
        'description',
        'details',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'details' => 'array'
    ];

    /**
     * Obtém o usuário relacionado ao log
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Registra uma nova ação no log do sistema
     */
    public static function register(string $action, string $module, string $description, array $details = []): self
    {
        return static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'details' => $details,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }
} 