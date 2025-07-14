<?php

namespace App\Services;

use App\Models\ModelApiKey;
use App\Models\AiCallLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AIUsageMonitorService
{
    /**
     * Registra o uso de um provedor de IA
     */
    public function recordUsage(string $provider, string $model, string $status, ?string $errorMessage = null): void
    {
        try {
            // Registrar no log de chamadas de IA se a tabela existir
            if (class_exists('\\App\\Models\\AiCallLog')) {
                AiCallLog::create([
                    'provider' => $provider,
                    'model' => $model,
                    'status' => $status,
                    'error_message' => $errorMessage,
                    'user_id' => auth()->id(),
                    'created_at' => now()
                ]);
            }

            // Atualizar cache de estatísticas
            $this->updateUsageStats($provider, $model, $status);

            Log::info('Uso de IA registrado', [
                'provider' => $provider,
                'model' => $model,
                'status' => $status,
                'user_id' => auth()->id()
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao registrar uso de IA', [
                'provider' => $provider,
                'model' => $model,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Verifica se um provedor está com problemas recentes
     */
    public function isProviderHealthy(string $provider, string $model): bool
    {
        $cacheKey = "ai_health_{$provider}_{$model}";
        
        // Verificar cache primeiro
        $cachedHealth = Cache::get($cacheKey);
        if ($cachedHealth !== null) {
            return $cachedHealth;
        }

        // Verificar logs recentes (últimas 2 horas)
        $recentErrors = 0;
        $recentSuccess = 0;

        if (class_exists('\\App\\Models\\AiCallLog')) {
            $recentLogs = AiCallLog::where('provider', $provider)
                ->where('model', $model)
                ->where('created_at', '>=', now()->subHours(2))
                ->get();

            foreach ($recentLogs as $log) {
                if ($log->status === 'success') {
                    $recentSuccess++;
                } else {
                    $recentErrors++;
                }
            }
        }

        // Considerar saudável se taxa de sucesso > 70% ou se não há dados suficientes
        $totalCalls = $recentErrors + $recentSuccess;
        $isHealthy = $totalCalls < 5 || ($recentSuccess / $totalCalls) > 0.7;

        // Cache por 10 minutos
        Cache::put($cacheKey, $isHealthy, 600);

        return $isHealthy;
    }

    /**
     * Obtém o próximo provedor Groq disponível
     */
    public function getNextGroqProvider(string $excludeModel = null): ?array
    {
        $groqConfigs = ModelApiKey::where('provider', 'groq')
            ->where('is_active', true)
            ->whereNotNull('api_token')
            ->where('api_token', '!=', '')
            ->when($excludeModel, function($query, $excludeModel) {
                return $query->where('model', '!=', $excludeModel);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($groqConfigs as $config) {
            // Verificar se o provedor está saudável
            if ($this->isProviderHealthy('groq', $config->model)) {
                return [
                    'provider' => 'groq',
                    'model' => $config->model,
                    'api_key' => $config->api_token,
                    'system_prompt' => $config->system_prompt,
                    'chat_prompt' => $config->chat_prompt,
                    'import_prompt' => $config->import_prompt,
                    'config_id' => $config->id
                ];
            }
        }

        return null;
    }

    /**
     * Obtém estatísticas de uso dos provedores
     */
    public function getUsageStats(int $hours = 24): array
    {
        $stats = [];

        if (class_exists('\\App\\Models\\AiCallLog')) {
            $logs = AiCallLog::where('created_at', '>=', now()->subHours($hours))
                ->get()
                ->groupBy(['provider', 'model']);

            foreach ($logs as $provider => $models) {
                $stats[$provider] = [];
                
                foreach ($models as $model => $calls) {
                    $successCount = $calls->where('status', 'success')->count();
                    $errorCount = $calls->where('status', '!=', 'success')->count();
                    $totalCalls = $calls->count();

                    $stats[$provider][$model] = [
                        'total_calls' => $totalCalls,
                        'success_calls' => $successCount,
                        'error_calls' => $errorCount,
                        'success_rate' => $totalCalls > 0 ? round(($successCount / $totalCalls) * 100, 2) : 0,
                        'last_call' => $calls->max('created_at'),
                        'is_healthy' => $this->isProviderHealthy($provider, $model)
                    ];
                }
            }
        }

        return $stats;
    }

    /**
     * Marca um provedor como problemático temporariamente
     */
    public function markProviderAsProblematic(string $provider, string $model, int $minutes = 30): void
    {
        $cacheKey = "ai_health_{$provider}_{$model}";
        Cache::put($cacheKey, false, $minutes * 60);

        Log::warning('Provedor marcado como problemático', [
            'provider' => $provider,
            'model' => $model,
            'duration_minutes' => $minutes
        ]);
    }

    /**
     * Obtém recomendação do melhor provedor disponível
     */
    public function getBestAvailableProvider(string $preferredProvider = 'groq'): ?array
    {
        // Tentar primeiro o provedor preferido
        if ($preferredProvider === 'groq') {
            $groqProvider = $this->getNextGroqProvider();
            if ($groqProvider) {
                return $groqProvider;
            }
        }

        // Fallback para outros provedores configurados
        $otherProviders = ModelApiKey::where('provider', '!=', $preferredProvider)
            ->where('is_active', true)
            ->whereNotNull('api_token')
            ->where('api_token', '!=', '')
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($otherProviders as $config) {
            if ($this->isProviderHealthy($config->provider, $config->model)) {
                return [
                    'provider' => $config->provider,
                    'model' => $config->model,
                    'api_key' => $config->api_token,
                    'system_prompt' => $config->system_prompt,
                    'chat_prompt' => $config->chat_prompt,
                    'import_prompt' => $config->import_prompt,
                    'config_id' => $config->id
                ];
            }
        }

        return null;
    }

    /**
     * Atualiza estatísticas de uso em cache
     */
    private function updateUsageStats(string $provider, string $model, string $status): void
    {
        $cacheKey = "ai_stats_{$provider}_{$model}";
        $stats = Cache::get($cacheKey, [
            'total_calls' => 0,
            'success_calls' => 0,
            'error_calls' => 0,
            'last_updated' => now()
        ]);

        $stats['total_calls']++;
        if ($status === 'success') {
            $stats['success_calls']++;
        } else {
            $stats['error_calls']++;
        }
        $stats['last_updated'] = now();

        // Cache por 1 hora
        Cache::put($cacheKey, $stats, 3600);
    }

    /**
     * Limpa cache de saúde dos provedores
     */
    public function clearHealthCache(): void
    {
        $providers = ['groq', 'openai', 'anthropic', 'gemini', 'openrouter'];
        
        foreach ($providers as $provider) {
            $configs = ModelApiKey::where('provider', $provider)->get();
            foreach ($configs as $config) {
                $cacheKey = "ai_health_{$provider}_{$config->model}";
                Cache::forget($cacheKey);
            }
        }

        Log::info('Cache de saúde dos provedores limpo');
    }
}
