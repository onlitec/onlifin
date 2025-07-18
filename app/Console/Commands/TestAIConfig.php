<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AIConfigService;
use App\Models\ModelApiKey;
use App\Models\OpenRouterConfig;

class TestAIConfig extends Command
{
    protected $signature = 'ai:test-config';
    protected $description = 'Testa a configuração de IA do sistema';

    public function handle()
    {
        $this->info('🔍 Testando configuração de IA...');
        
        $aiConfigService = new AIConfigService();
        
        // Verificar se IA está configurada
        $isConfigured = $aiConfigService->isAIConfigured();
        $this->info("IA configurada: " . ($isConfigured ? '✅ Sim' : '❌ Não'));
        
        if ($isConfigured) {
            $config = $aiConfigService->getAIConfig();
            $this->info("Provedor: " . ($config['provider'] ?? 'N/A'));
            $this->info("Modelo: " . ($config['model'] ?? 'N/A'));
            $this->info("Tem API Key: " . ($config['has_api_key'] ? '✅ Sim' : '❌ Não'));
        }
        
        // Verificar configurações no banco
        $this->info("\n📊 Configurações no banco de dados:");
        
        // ModelApiKey
        $modelKeys = ModelApiKey::all();
        $this->info("ModelApiKey registros: " . $modelKeys->count());
        foreach ($modelKeys as $key) {
            $this->info("  - {$key->provider}/{$key->model} (ativo: " . ($key->is_active ? 'Sim' : 'Não') . ")");
        }
        
        // OpenRouterConfig
        $openRouterConfigs = OpenRouterConfig::all();
        $this->info("OpenRouterConfig registros: " . $openRouterConfigs->count());
        foreach ($openRouterConfigs as $config) {
            $this->info("  - {$config->provider} (API Key: " . (empty($config->api_key) ? 'Não' : 'Sim') . ")");
        }
        
        // Verificar config/ai.php
        $this->info("\n⚙️ Configuração em config/ai.php:");
        $aiEnabled = config('ai.enabled', false);
        $this->info("AI habilitada: " . ($aiEnabled ? '✅ Sim' : '❌ Não'));
        
        if ($aiEnabled) {
            $provider = config('ai.provider');
            $this->info("Provedor: " . ($provider ?? 'N/A'));
            
            if ($provider) {
                $apiKey = config("ai.{$provider}.api_key");
                $model = config("ai.{$provider}.model");
                $this->info("API Key configurada: " . (!empty($apiKey) ? '✅ Sim' : '❌ Não'));
                $this->info("Modelo: " . ($model ?? 'N/A'));
            }
        }
        
        $this->info("\n🎯 Recomendações:");
        if (!$isConfigured) {
            $this->warn("1. Configure um provedor de IA em /iaprovider-config");
            $this->warn("2. Ou configure via config/ai.php");
            $this->warn("3. Certifique-se de ter uma API Key válida");
        } else {
            $this->info("✅ Configuração de IA está funcionando!");
        }
        
        return 0;
    }
}
