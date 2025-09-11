<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Serviço de Cache para otimização de performance
 * 
 * Este serviço implementa estratégias de cache inteligente para:
 * - Consultas complexas de banco de dados
 * - Dados de dashboard e relatórios
 * - Configurações do sistema
 * - Dados de usuário frequentes
 */
class CacheService
{
    /**
     * Prefixos para diferentes tipos de cache
     */
    const PREFIX_DASHBOARD = 'dashboard';
    const PREFIX_REPORTS = 'reports';
    const PREFIX_USER = 'user';
    const PREFIX_SYSTEM = 'system';
    const PREFIX_TRANSACTIONS = 'transactions';
    const PREFIX_ACCOUNTS = 'accounts';
    const PREFIX_CATEGORIES = 'categories';

    /**
     * Tempos de expiração em minutos
     */
    const TTL_DASHBOARD = 15;      // 15 minutos
    const TTL_REPORTS = 30;        // 30 minutos
    const TTL_USER = 60;           // 1 hora
    const TTL_SYSTEM = 120;        // 2 horas
    const TTL_TRANSACTIONS = 10;   // 10 minutos
    const TTL_ACCOUNTS = 30;       // 30 minutos
    const TTL_CATEGORIES = 60;     // 1 hora

    /**
     * Cache de dados do dashboard
     */
    public static function cacheDashboardData(int $userId, callable $callback, int $ttl = null)
    {
        $key = self::PREFIX_DASHBOARD . ":user:{$userId}";
        $ttl = $ttl ?? self::TTL_DASHBOARD;

        return Cache::remember($key, $ttl * 60, function () use ($callback) {
            Log::info('Cache miss - Dashboard data', ['user_id' => auth()->id()]);
            return $callback();
        });
    }

    /**
     * Cache de relatórios financeiros
     */
    public static function cacheReportData(string $reportType, array $params, callable $callback, int $ttl = null)
    {
        $key = self::PREFIX_REPORTS . ":{$reportType}:" . md5(serialize($params));
        $ttl = $ttl ?? self::TTL_REPORTS;

        return Cache::remember($key, $ttl * 60, function () use ($callback, $reportType) {
            Log::info('Cache miss - Report data', ['report_type' => $reportType]);
            return $callback();
        });
    }

    /**
     * Cache de dados do usuário
     */
    public static function cacheUserData(int $userId, string $dataType, callable $callback, int $ttl = null)
    {
        $key = self::PREFIX_USER . ":{$userId}:{$dataType}";
        $ttl = $ttl ?? self::TTL_USER;

        return Cache::remember($key, $ttl * 60, function () use ($callback, $dataType) {
            Log::info('Cache miss - User data', ['data_type' => $dataType]);
            return $callback();
        });
    }

    /**
     * Cache de transações
     */
    public static function cacheTransactions(int $userId, array $filters, callable $callback, int $ttl = null)
    {
        $key = self::PREFIX_TRANSACTIONS . ":user:{$userId}:" . md5(serialize($filters));
        $ttl = $ttl ?? self::TTL_TRANSACTIONS;

        return Cache::remember($key, $ttl * 60, function () use ($callback) {
            Log::info('Cache miss - Transactions data');
            return $callback();
        });
    }

    /**
     * Cache de contas do usuário
     */
    public static function cacheAccounts(int $userId, callable $callback, int $ttl = null)
    {
        $key = self::PREFIX_ACCOUNTS . ":user:{$userId}";
        $ttl = $ttl ?? self::TTL_ACCOUNTS;

        return Cache::remember($key, $ttl * 60, function () use ($callback) {
            Log::info('Cache miss - Accounts data');
            return $callback();
        });
    }

    /**
     * Cache de categorias
     */
    public static function cacheCategories(int $userId, string $type, callable $callback, int $ttl = null)
    {
        $key = self::PREFIX_CATEGORIES . ":user:{$userId}:{$type}";
        $ttl = $ttl ?? self::TTL_CATEGORIES;

        return Cache::remember($key, $ttl * 60, function () use ($callback, $type) {
            Log::info('Cache miss - Categories data', ['type' => $type]);
            return $callback();
        });
    }

    /**
     * Cache de configurações do sistema
     */
    public static function cacheSystemConfig(string $configKey, callable $callback, int $ttl = null)
    {
        $key = self::PREFIX_SYSTEM . ":config:{$configKey}";
        $ttl = $ttl ?? self::TTL_SYSTEM;

        return Cache::remember($key, $ttl * 60, function () use ($callback, $configKey) {
            Log::info('Cache miss - System config', ['config_key' => $configKey]);
            return $callback();
        });
    }

    /**
     * Invalidar cache do usuário
     */
    public static function invalidateUserCache(int $userId): void
    {
        $patterns = [
            self::PREFIX_DASHBOARD . ":user:{$userId}",
            self::PREFIX_USER . ":{$userId}:*",
            self::PREFIX_TRANSACTIONS . ":user:{$userId}:*",
            self::PREFIX_ACCOUNTS . ":user:{$userId}",
            self::PREFIX_CATEGORIES . ":user:{$userId}:*",
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($pattern, '*')) {
                // Para padrões com wildcard, usar tags se disponível
                Cache::tags(['user:' . $userId])->flush();
            } else {
                Cache::forget($pattern);
            }
        }

        Log::info('User cache invalidated', ['user_id' => $userId]);
    }

    /**
     * Invalidar cache de relatórios
     */
    public static function invalidateReportsCache(): void
    {
        Cache::tags(['reports'])->flush();
        Log::info('Reports cache invalidated');
    }

    /**
     * Invalidar cache de transações
     */
    public static function invalidateTransactionsCache(int $userId = null): void
    {
        if ($userId) {
            Cache::tags(['transactions', 'user:' . $userId])->flush();
        } else {
            Cache::tags(['transactions'])->flush();
        }
        
        Log::info('Transactions cache invalidated', ['user_id' => $userId]);
    }

    /**
     * Limpar todo o cache
     */
    public static function clearAllCache(): void
    {
        Cache::flush();
        Log::info('All cache cleared');
    }

    /**
     * Estatísticas de cache
     */
    public static function getCacheStats(): array
    {
        $stats = [
            'driver' => config('cache.default'),
            'prefix' => config('cache.prefix'),
            'stores' => array_keys(config('cache.stores')),
        ];

        // Tentar obter estatísticas do Redis se disponível
        if (config('cache.default') === 'redis') {
            try {
                $redis = Cache::getRedis();
                $info = $redis->info();
                $stats['redis'] = [
                    'used_memory' => $info['used_memory_human'] ?? 'N/A',
                    'connected_clients' => $info['connected_clients'] ?? 'N/A',
                    'total_commands_processed' => $info['total_commands_processed'] ?? 'N/A',
                ];
            } catch (\Exception $e) {
                $stats['redis'] = ['error' => 'Unable to get Redis stats'];
            }
        }

        return $stats;
    }

    /**
     * Cache com tags para invalidação seletiva
     */
    public static function rememberWithTags(string $key, array $tags, int $ttl, callable $callback)
    {
        return Cache::tags($tags)->remember($key, $ttl * 60, $callback);
    }

    /**
     * Cache de consultas SQL complexas
     */
    public static function cacheQuery(string $queryHash, callable $callback, int $ttl = 10)
    {
        $key = 'query:' . $queryHash;
        return Cache::remember($key, $ttl * 60, function () use ($callback, $queryHash) {
            Log::info('Cache miss - Complex query', ['query_hash' => $queryHash]);
            return $callback();
        });
    }
}
