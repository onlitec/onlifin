<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AICategorizationService;
use App\Services\AIConfigService;
use App\Models\User;
use App\Models\ModelApiKey;

class DebugAIProviders extends Command
{
    protected $signature = 'debug:ai-providers {user_id=2}';
    protected $description = 'Debug dos provedores de IA para identificar problemas';

    public function handle()
    {
        $userId = $this->argument('user_id');
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("Usuário com ID {$userId} não encontrado");
            return 1;
        }
        
        auth()->login($user);
        
        $this->info("🔍 Debug dos Provedores de IA");
        $this->line("  Usuário: {$user->name} (ID: {$user->id})");
        
        // 1. Verificar configuração principal
        $this->info("\n1️⃣ Configuração Principal:");
        
        $aiConfigService = new AIConfigService();
        $mainConfig = $aiConfigService->getAIConfig();
        
        if ($mainConfig && isset($mainConfig['provider'])) {
            $this->line("  ✅ Provedor: {$mainConfig['provider']}");
            $this->line("  ✅ Modelo: " . ($mainConfig['model'] ?? 'N/A'));
            $this->line("  ✅ API Key: " . (empty($mainConfig['api_key']) ? 'NÃO' : 'SIM'));
            
            // Testar configuração principal
            $this->line("  🧪 Testando configuração principal...");
            try {
                $categorizationService = new AICategorizationService();
                $testTransactions = [
                    [
                        'date' => '2025-07-13',
                        'description' => 'Teste de conexão',
                        'amount' => 1000,
                        'type' => 'expense'
                    ]
                ];
                
                $result = $categorizationService->categorizeTransactions($testTransactions, 6);
                $this->info("  ✅ Configuração principal funcionando");
                
            } catch (\Exception $e) {
                $this->error("  ❌ Erro na configuração principal: " . $e->getMessage());
            }
        } else {
            $this->error("  ❌ Nenhuma configuração principal encontrada");
        }
        
        // 2. Verificar configurações múltiplas
        $this->info("\n2️⃣ Configurações Múltiplas:");
        
        $multipleConfigs = ModelApiKey::where('is_active', true)
            ->whereNotNull('api_token')
            ->where('api_token', '!=', '')
            ->get();
            
        if ($multipleConfigs->count() > 0) {
            $this->line("  Total de configurações: " . $multipleConfigs->count());
            
            foreach ($multipleConfigs as $config) {
                $this->line("  • {$config->provider}/{$config->model} (ID: {$config->id})");
                
                // Testar cada configuração
                $this->line("    🧪 Testando...");
                try {
                    $testResult = $this->testProviderConfig($config);
                    if ($testResult) {
                        $this->info("    ✅ Funcionando");
                    } else {
                        $this->error("    ❌ Falhou");
                    }
                } catch (\Exception $e) {
                    $this->error("    ❌ Erro: " . substr($e->getMessage(), 0, 50) . "...");
                }
            }
        } else {
            $this->error("  ❌ Nenhuma configuração múltipla encontrada");
        }
        
        // 3. Testar método getOtherConfiguredProviders
        $this->info("\n3️⃣ Testando Fallback Logic:");
        
        $categorizationService = new AICategorizationService();
        
        // Usar reflexão para acessar método privado
        $reflection = new \ReflectionClass($categorizationService);
        $method = $reflection->getMethod('getOtherConfiguredProviders');
        $method->setAccessible(true);
        
        try {
            $otherProviders = $method->invoke($categorizationService, 'gemini');
            $this->line("  Provedores de fallback encontrados: " . count($otherProviders));
            
            foreach ($otherProviders as $i => $provider) {
                $this->line("  {$i}: {$provider['provider']}/{$provider['model']}");
            }
            
            if (empty($otherProviders)) {
                $this->warn("  ⚠️  Nenhum provedor de fallback configurado!");
            }
            
        } catch (\Exception $e) {
            $this->error("  ❌ Erro ao obter provedores de fallback: " . $e->getMessage());
        }
        
        // 4. Verificar configuração específica do Groq
        $this->info("\n4️⃣ Configurações Groq:");
        
        $groqConfigs = ModelApiKey::where('provider', 'groq')
            ->where('is_active', true)
            ->whereNotNull('api_token')
            ->where('api_token', '!=', '')
            ->get();
            
        $this->line("  Total Groq: " . $groqConfigs->count());
        
        if ($groqConfigs->count() < 2) {
            $this->warn("  ⚠️  Menos de 2 configurações Groq - fallback limitado");
        }
        
        foreach ($groqConfigs as $config) {
            $this->line("  • {$config->model} (ID: {$config->id})");
        }
        
        // 5. Recomendações
        $this->info("\n💡 Recomendações:");
        
        if ($multipleConfigs->count() < 2) {
            $this->warn("  • Configure pelo menos 2 provedores para fallback robusto");
        }
        
        if ($groqConfigs->count() < 2) {
            $this->warn("  • Configure múltiplos provedores Groq em /multiple-ai-config");
        }
        
        $this->info("  • Monitore logs regularmente: tail -f storage/logs/laravel-*.log");
        $this->info("  • Use: php artisan ai:monitor-providers para estatísticas");
        
        return 0;
    }
    
    private function testProviderConfig($config): bool
    {
        try {
            // Teste simples de conexão baseado no provedor
            switch ($config->provider) {
                case 'groq':
                    $response = \Illuminate\Support\Facades\Http::withHeaders([
                        'Authorization' => 'Bearer ' . $config->api_token,
                        'Content-Type' => 'application/json'
                    ])->timeout(30)->post('https://api.groq.com/openai/v1/chat/completions', [
                        'model' => $config->model,
                        'messages' => [
                            ['role' => 'user', 'content' => 'Test']
                        ],
                        'max_tokens' => 10
                    ]);
                    
                    return $response->successful();
                    
                case 'gemini':
                    $response = \Illuminate\Support\Facades\Http::timeout(30)
                        ->post("https://generativelanguage.googleapis.com/v1beta/models/{$config->model}:generateContent?key={$config->api_token}", [
                            'contents' => [
                                ['parts' => [['text' => 'Test']]]
                            ]
                        ]);
                    
                    return $response->successful();
                    
                default:
                    return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }
}
