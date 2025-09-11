<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Serviço de CDN para otimização de performance
 * 
 * Este serviço gerencia:
 * - Upload de assets para CDN
 * - Geração de URLs otimizadas
 * - Cache de assets
 * - Fallback para assets locais
 */
class CDNService
{
    protected $config;
    protected $enabled;

    public function __construct()
    {
        $this->config = config('cdn');
        $this->enabled = $this->config['enabled'] ?? false;
    }

    /**
     * Obter URL do asset com CDN
     */
    public function asset(string $path, string $type = 'images'): string
    {
        if (!$this->enabled) {
            return asset($path);
        }

        $cdnUrl = $this->getCDNUrl($type);
        $fullPath = rtrim($cdnUrl, '/') . '/' . ltrim($path, '/');

        // Verificar se o asset existe no CDN
        if ($this->assetExists($fullPath)) {
            return $fullPath;
        }

        // Fallback para asset local
        if ($this->config['fallback']['enabled']) {
            return asset($path);
        }

        return $fullPath;
    }

    /**
     * Obter URL do CSS com CDN
     */
    public function css(string $path): string
    {
        return $this->asset($path, 'css');
    }

    /**
     * Obter URL do JavaScript com CDN
     */
    public function js(string $path): string
    {
        return $this->asset($path, 'js');
    }

    /**
     * Obter URL da imagem com CDN
     */
    public function image(string $path): string
    {
        return $this->asset($path, 'images');
    }

    /**
     * Obter URL da fonte com CDN
     */
    public function font(string $path): string
    {
        return $this->asset($path, 'fonts');
    }

    /**
     * Obter URL base do CDN por tipo
     */
    protected function getCDNUrl(string $type): string
    {
        return $this->config['assets'][$type] ?? $this->config['url'];
    }

    /**
     * Verificar se asset existe no CDN
     */
    protected function assetExists(string $url): bool
    {
        if (!$this->config['cache']['enabled']) {
            return $this->checkAssetExists($url);
        }

        $cacheKey = 'cdn_asset_exists:' . md5($url);
        
        return Cache::remember($cacheKey, $this->config['cache']['ttl'], function () use ($url) {
            return $this->checkAssetExists($url);
        });
    }

    /**
     * Verificar se asset existe (sem cache)
     */
    protected function checkAssetExists(string $url): bool
    {
        try {
            $headers = get_headers($url, 1);
            return isset($headers[0]) && strpos($headers[0], '200') !== false;
        } catch (\Exception $e) {
            Log::warning('Erro ao verificar asset no CDN', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Upload de asset para CDN
     */
    public function uploadAsset(string $localPath, string $remotePath, string $type = 'images'): bool
    {
        if (!$this->enabled) {
            return false;
        }

        try {
            $cdnUrl = $this->getCDNUrl($type);
            $fullRemotePath = rtrim($cdnUrl, '/') . '/' . ltrim($remotePath, '/');

            // Upload para AWS S3
            if ($this->config['providers']['aws']['enabled']) {
                return $this->uploadToAWS($localPath, $remotePath);
            }

            // Upload para Cloudflare
            if ($this->config['providers']['cloudflare']['enabled']) {
                return $this->uploadToCloudflare($localPath, $remotePath);
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Erro ao fazer upload para CDN', [
                'local_path' => $localPath,
                'remote_path' => $remotePath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Upload para AWS S3
     */
    protected function uploadToAWS(string $localPath, string $remotePath): bool
    {
        $awsConfig = $this->config['providers']['aws'];
        
        try {
            $s3 = Storage::disk('s3');
            $s3->put($remotePath, file_get_contents($localPath), 'public');
            
            Log::info('Asset enviado para AWS S3', [
                'local_path' => $localPath,
                'remote_path' => $remotePath
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Erro ao enviar para AWS S3', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Upload para Cloudflare
     */
    protected function uploadToCloudflare(string $localPath, string $remotePath): bool
    {
        $cloudflareConfig = $this->config['providers']['cloudflare'];
        
        try {
            // Implementar upload para Cloudflare usando API
            $client = new \GuzzleHttp\Client();
            
            $response = $client->post("https://api.cloudflare.com/client/v4/zones/{$cloudflareConfig['zone_id']}/purge_cache", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $cloudflareConfig['api_token'],
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'files' => [$remotePath]
                ]
            ]);

            if ($response->getStatusCode() === 200) {
                Log::info('Cache do Cloudflare limpo', [
                    'path' => $remotePath
                ]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Erro ao limpar cache do Cloudflare', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Limpar cache do CDN
     */
    public function clearCache(array $paths = []): bool
    {
        if (!$this->enabled) {
            return false;
        }

        try {
            // Limpar cache local
            if ($this->config['cache']['enabled']) {
                foreach ($paths as $path) {
                    $cacheKey = 'cdn_asset_exists:' . md5($path);
                    Cache::forget($cacheKey);
                }
            }

            // Limpar cache do Cloudflare
            if ($this->config['providers']['cloudflare']['enabled']) {
                return $this->clearCloudflareCache($paths);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Erro ao limpar cache do CDN', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Limpar cache do Cloudflare
     */
    protected function clearCloudflareCache(array $paths): bool
    {
        $cloudflareConfig = $this->config['providers']['cloudflare'];
        
        try {
            $client = new \GuzzleHttp\Client();
            
            $response = $client->post("https://api.cloudflare.com/client/v4/zones/{$cloudflareConfig['zone_id']}/purge_cache", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $cloudflareConfig['api_token'],
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'files' => $paths
                ]
            ]);

            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            Log::error('Erro ao limpar cache do Cloudflare', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Obter estatísticas do CDN
     */
    public function getStats(): array
    {
        return [
            'enabled' => $this->enabled,
            'config' => $this->config,
            'cache_enabled' => $this->config['cache']['enabled'],
            'fallback_enabled' => $this->config['fallback']['enabled'],
        ];
    }
}
