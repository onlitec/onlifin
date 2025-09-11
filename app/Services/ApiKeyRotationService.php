<?php

namespace App\Services;

use App\Models\User;
use App\Models\ApiKeyPolicy;
use App\Models\ApiKeyRotation;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ApiKeyRotationService
{
    /**
     * Rotacionar API keys de um usuário específico
     */
    public function rotateUserKeys(int $userId, string $reason = 'manual', array $metadata = []): array
    {
        $user = User::find($userId);

        if (!$user) {
            Log::warning('User not found for API key rotation', ['user_id' => $userId]);
            return [
                'success' => false,
                'message' => 'Usuário não encontrado',
                'rotated_count' => 0,
            ];
        }

        $policy = ApiKeyPolicy::getPolicyForUser($user);
        $tokens = $this->getRotatableTokens($user);

        $rotatedCount = 0;
        $errors = [];

        foreach ($tokens as $token) {
            try {
                $this->rotateSingleToken($user, $token, $policy, $reason, $metadata);
                $rotatedCount++;
            } catch (\Exception $e) {
                $errors[] = "Token {$token->id}: {$e->getMessage()}";
                Log::error('Failed to rotate API token', [
                    'user_id' => $userId,
                    'token_id' => $token->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Notificar usuário se configurado
        if ($policy->notify_user && $rotatedCount > 0) {
            $this->notifyUserOfRotation($user, $rotatedCount, $reason);
        }

        return [
            'success' => true,
            'message' => "Rotacionadas {$rotatedCount} API keys",
            'rotated_count' => $rotatedCount,
            'errors' => $errors,
        ];
    }

    /**
     * Rotacionar API keys expiradas ou que precisam de rotação
     */
    public function rotateExpiredKeys(): array
    {
        $stats = [
            'processed_users' => 0,
            'rotated_tokens' => 0,
            'errors' => [],
        ];

        // Buscar usuários com tokens que precisam de rotação
        $usersWithExpirableTokens = User::whereHas('tokens', function ($query) {
            $query->whereNotNull('expires_at')
                  ->where('expires_at', '<=', now()->addDays(7)); // Próximos 7 dias
        })->get();

        foreach ($usersWithExpirableTokens as $user) {
            try {
                $policy = ApiKeyPolicy::getPolicyForUser($user);

                if (!$policy->auto_rotate) {
                    continue;
                }

                $tokensToRotate = $this->getTokensNeedingRotation($user, $policy);

                foreach ($tokensToRotate as $token) {
                    $this->rotateSingleToken($user, $token, $policy, 'auto', [
                        'trigger' => 'scheduled_rotation',
                        'expires_at' => $token->expires_at?->toDateString(),
                    ]);
                    $stats['rotated_tokens']++;
                }

                $stats['processed_users']++;

            } catch (\Exception $e) {
                $stats['errors'][] = "User {$user->id}: {$e->getMessage()}";
                Log::error('Failed to rotate expired keys for user', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Completed scheduled API key rotation', $stats);

        return $stats;
    }

    /**
     * Rotacionar um token específico
     */
    public function rotateSingleToken(
        User $user,
        PersonalAccessToken $token,
        ApiKeyPolicy $policy,
        string $reason = 'manual',
        array $metadata = []
    ): PersonalAccessToken {
        DB::beginTransaction();

        try {
            // Gerar novo token
            $newToken = $this->generateNewToken($user, $token->name, $policy);

            // Registrar rotação
            ApiKeyRotation::logRotation(
                $user->id,
                hash('sha256', $token->token),
                hash('sha256', $newToken->token),
                $reason,
                array_merge($metadata, [
                    'old_token_id' => $token->id,
                    'new_token_id' => $newToken->id,
                    'policy_name' => $policy->name,
                ])
            );

            // Remover token antigo
            $token->delete();

            DB::commit();

            Log::info('API token rotated successfully', [
                'user_id' => $user->id,
                'old_token_id' => $token->id,
                'new_token_id' => $newToken->id,
                'reason' => $reason,
            ]);

            return $newToken;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Gerar novo token com política aplicada
     */
    protected function generateNewToken(User $user, string $name, ApiKeyPolicy $policy): PersonalAccessToken
    {
        $abilities = $policy->getDefaultAbilities();
        $expiresAt = $policy->getExpirationDate();

        return $user->createToken($name, $abilities, $expiresAt);
    }

    /**
     * Obter tokens que podem ser rotacionados
     */
    protected function getRotatableTokens(User $user): \Illuminate\Support\Collection
    {
        return $user->tokens()
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->get();
    }

    /**
     * Obter tokens que precisam de rotação baseada na política
     */
    protected function getTokensNeedingRotation(User $user, ApiKeyPolicy $policy): \Illuminate\Support\Collection
    {
        return $user->tokens()
            ->where(function ($query) use ($policy) {
                // Tokens expirados
                $query->where('expires_at', '<=', now())
                      ->orWhere(function ($subQuery) use ($policy) {
                          // Tokens que precisam rotação por idade
                          $subQuery->where('created_at', '<=', now()->subDays($policy->rotation_interval_days));
                      });
            })
            ->get();
    }

    /**
     * Notificar usuário sobre rotação
     */
    protected function notifyUserOfRotation(User $user, int $rotatedCount, string $reason): void
    {
        try {
            // Aqui você pode implementar notificação por email, push, etc.
            Log::info('User notified of API key rotation', [
                'user_id' => $user->id,
                'rotated_count' => $rotatedCount,
                'reason' => $reason,
            ]);

            // TODO: Implementar notificação real (email, push, etc.)

        } catch (\Exception $e) {
            Log::error('Failed to notify user of API key rotation', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Verificar status de rotação de um usuário
     */
    public function getUserRotationStatus(int $userId): array
    {
        $user = User::find($userId);

        if (!$user) {
            return [
                'error' => 'Usuário não encontrado',
            ];
        }

        $policy = ApiKeyPolicy::getPolicyForUser($user);
        $tokens = $user->tokens()->get();

        $status = [
            'user_id' => $userId,
            'policy' => $policy->name,
            'total_tokens' => $tokens->count(),
            'active_tokens' => $tokens->where('expires_at', '>', now())->count(),
            'expired_tokens' => $tokens->where('expires_at', '<=', now())->count(),
            'tokens_needing_rotation' => 0,
            'next_rotation' => null,
            'tokens' => [],
        ];

        foreach ($tokens as $token) {
            $tokenData = [
                'id' => $token->id,
                'name' => $token->name,
                'created_at' => $token->created_at,
                'expires_at' => $token->expires_at,
                'last_used_at' => $token->last_used_at,
                'is_expired' => $policy->isExpired($token),
                'needs_rotation' => $policy->needsRotation($token),
                'is_expiring_soon' => $policy->isExpiringSoon($token),
            ];

            if ($tokenData['needs_rotation']) {
                $status['tokens_needing_rotation']++;
            }

            if ($tokenData['needs_rotation'] && !$status['next_rotation']) {
                $status['next_rotation'] = $token->created_at->addDays($policy->rotation_interval_days);
            }

            $status['tokens'][] = $tokenData;
        }

        return $status;
    }

    /**
     * Forçar rotação de emergência para todos os usuários
     */
    public function emergencyRotation(string $reason = 'security', array $metadata = []): array
    {
        Log::critical('Emergency API key rotation initiated', [
            'reason' => $reason,
            'metadata' => $metadata,
        ]);

        $stats = [
            'processed_users' => 0,
            'rotated_tokens' => 0,
            'errors' => [],
        ];

        // Buscar todos os usuários com tokens ativos
        $users = User::whereHas('tokens')->get();

        foreach ($users as $user) {
            try {
                $result = $this->rotateUserKeys($user->id, $reason, array_merge($metadata, [
                    'emergency' => true,
                    'initiated_at' => now(),
                ]));

                $stats['processed_users']++;
                $stats['rotated_tokens'] += $result['rotated_count'];

                if (!empty($result['errors'])) {
                    $stats['errors'] = array_merge($stats['errors'], $result['errors']);
                }

            } catch (\Exception $e) {
                $stats['errors'][] = "User {$user->id}: {$e->getMessage()}";
            }
        }

        Log::critical('Emergency API key rotation completed', $stats);

        return $stats;
    }

    /**
     * Limpar tokens expirados
     */
    public function cleanupExpiredTokens(): int
    {
        $expiredTokens = PersonalAccessToken::where('expires_at', '<=', now())->get();
        $deletedCount = 0;

        foreach ($expiredTokens as $token) {
            try {
                // Registrar limpeza
                ApiKeyRotation::logRotation(
                    $token->tokenable_id,
                    hash('sha256', $token->token),
                    null,
                    'expired_cleanup',
                    ['cleanup_date' => now()]
                );

                $token->delete();
                $deletedCount++;

            } catch (\Exception $e) {
                Log::error('Failed to cleanup expired token', [
                    'token_id' => $token->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($deletedCount > 0) {
            Log::info('Cleaned up expired API tokens', [
                'deleted_count' => $deletedCount,
            ]);
        }

        return $deletedCount;
    }

    /**
     * Obter estatísticas de rotação
     */
    public function getRotationStatistics(): array
    {
        $stats = ApiKeyRotation::getRotationStats();

        // Adicionar estatísticas de tokens ativos
        $stats['active_tokens'] = PersonalAccessToken::where(function ($query) {
            $query->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
        })->count();

        $stats['expired_tokens'] = PersonalAccessToken::where('expires_at', '<=', now())->count();

        // Políticas mais usadas
        $stats['policy_usage'] = DB::table('api_key_policies')
            ->leftJoin('users', function ($join) {
                $join->on('users.id', '=', DB::raw('1')) // Placeholder para lógica de política
                     ->whereRaw('1=1'); // Será implementado conforme necessidade
            })
            ->select('api_key_policies.name', DB::raw('COUNT(*) as usage'))
            ->groupBy('api_key_policies.name')
            ->pluck('usage', 'name')
            ->toArray();

        return $stats;
    }
}
