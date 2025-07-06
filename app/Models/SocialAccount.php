<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
        'provider_email',
        'provider_name',
        'provider_avatar',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'provider_data',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'provider_data' => 'array',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    /**
     * Relacionamento com User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Verifica se o token ainda é válido
     */
    public function isTokenValid(): bool
    {
        if (!$this->token_expires_at) {
            return true; // Se não tem expiração definida, considera válido
        }

        return $this->token_expires_at->isFuture();
    }

    /**
     * Obtém a conta social por provedor e ID do provedor
     */
    public static function findByProvider(string $provider, string $providerId): ?self
    {
        return static::where('provider', $provider)
                    ->where('provider_id', $providerId)
                    ->first();
    }

    /**
     * Obtém todas as contas sociais de um usuário
     */
    public static function getByUser(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('user_id', $userId)->get();
    }

    /**
     * Verifica se um usuário tem conta vinculada a um provedor específico
     */
    public static function userHasProvider(int $userId, string $provider): bool
    {
        return static::where('user_id', $userId)
                    ->where('provider', $provider)
                    ->exists();
    }

    /**
     * Lista de provedores suportados
     */
    public static function getSupportedProviders(): array
    {
        return [
            'google' => [
                'name' => 'Google',
                'icon' => 'google',
                'color' => '#4285F4'
            ],
            'facebook' => [
                'name' => 'Facebook',
                'icon' => 'facebook',
                'color' => '#1877F2'
            ],
            'twitter' => [
                'name' => 'Twitter',
                'icon' => 'twitter',
                'color' => '#1DA1F2'
            ],
            'github' => [
                'name' => 'GitHub',
                'icon' => 'github',
                'color' => '#333333'
            ],
            'linkedin' => [
                'name' => 'LinkedIn',
                'icon' => 'linkedin',
                'color' => '#0A66C2'
            ],
        ];
    }

    /**
     * Obtém informações do provedor
     */
    public function getProviderInfo(): array
    {
        $providers = static::getSupportedProviders();
        return $providers[$this->provider] ?? [
            'name' => ucfirst($this->provider),
            'icon' => 'user',
            'color' => '#6B7280'
        ];
    }
}
