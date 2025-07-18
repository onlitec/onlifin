<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ChatbotConfig;
use App\Models\ModelApiKey;

class FixDeprecatedModels extends Command
{
    protected $signature = 'fix:deprecated-models';
    protected $description = 'Corrige modelos de IA descontinuados na plataforma';

    // Mapeamento de modelos descontinuados para modelos ativos
    private $modelMigrations = [
        'groq' => [
            'llama-3.1-70b-versatile' => 'llama-3.3-70b-versatile',
            'mixtral-8x7b-32768' => 'gemma2-9b-it',
        ],
        'openai' => [
            'gpt-3.5-turbo-16k' => 'gpt-3.5-turbo',
            'gpt-4-turbo-preview' => 'gpt-4-turbo',
        ]
    ];

    public function handle()
    {
        $this->info("🔧 CORREÇÃO DE MODELOS DESCONTINUADOS");
        
        $totalFixed = 0;
        
        // 1. Corrigir ChatbotConfig
        $this->info("\n🤖 Verificando configurações do chatbot...");
        
        $chatbotConfigs = ChatbotConfig::all();
        foreach ($chatbotConfigs as $config) {
            if (isset($this->modelMigrations[$config->provider][$config->model])) {
                $oldModel = $config->model;
                $newModel = $this->modelMigrations[$config->provider][$config->model];
                
                $config->model = $newModel;
                $config->save();
                
                $this->line("✅ ChatbotConfig ID {$config->id} (User {$config->user_id}): {$oldModel} → {$newModel}");
                $totalFixed++;
            }
        }
        
        // 2. Corrigir ModelApiKey
        $this->info("\n🔑 Verificando chaves de API...");
        
        $modelApiKeys = ModelApiKey::all();
        foreach ($modelApiKeys as $apiKey) {
            if (isset($this->modelMigrations[$apiKey->provider][$apiKey->model])) {
                $oldModel = $apiKey->model;
                $newModel = $this->modelMigrations[$apiKey->provider][$apiKey->model];
                
                $apiKey->model = $newModel;
                $apiKey->save();
                
                $this->line("✅ ModelApiKey ID {$apiKey->id}: {$oldModel} → {$newModel}");
                $totalFixed++;
            }
        }
        
        // 3. Verificar modelos ainda problemáticos
        $this->info("\n🔍 Verificando modelos potencialmente problemáticos...");
        
        $deprecatedModels = [
            'llama-3.1-70b-versatile',
            'mixtral-8x7b-32768',
            'gpt-3.5-turbo-16k',
            'gpt-4-turbo-preview'
        ];
        
        $remainingIssues = 0;
        
        foreach ($deprecatedModels as $model) {
            $chatbotCount = ChatbotConfig::where('model', $model)->count();
            $apiKeyCount = ModelApiKey::where('model', $model)->count();
            
            if ($chatbotCount > 0 || $apiKeyCount > 0) {
                $this->warn("⚠️  Modelo {$model} ainda em uso:");
                if ($chatbotCount > 0) {
                    $this->line("   - {$chatbotCount} configuração(ões) de chatbot");
                }
                if ($apiKeyCount > 0) {
                    $this->line("   - {$apiKeyCount} chave(s) de API");
                }
                $remainingIssues++;
            }
        }
        
        // 4. Listar modelos ativos recomendados
        $this->info("\n📋 MODELOS ATIVOS RECOMENDADOS:");
        
        $activeModels = [
            'groq' => [
                'llama-3.3-70b-versatile' => 'Llama 3.3 70B Versatile (Recomendado)',
                'llama-3.1-8b-instant' => 'Llama 3.1 8B Instant (Rápido)',
                'gemma2-9b-it' => 'Gemma 2 9B IT (Eficiente)',
                'deepseek-r1-distill-llama-70b' => 'DeepSeek R1 Distill Llama 70B (Preview)'
            ],
            'openai' => [
                'gpt-4-turbo' => 'GPT-4 Turbo (Recomendado)',
                'gpt-3.5-turbo' => 'GPT-3.5 Turbo (Econômico)',
                'gpt-4o' => 'GPT-4o (Mais recente)',
                'gpt-4o-mini' => 'GPT-4o Mini (Rápido)'
            ],
            'gemini' => [
                'gemini-2.0-flash' => 'Gemini 2.0 Flash (Recomendado)',
                'gemini-1.5-pro' => 'Gemini 1.5 Pro (Avançado)',
                'gemini-1.5-flash' => 'Gemini 1.5 Flash (Rápido)'
            ]
        ];
        
        foreach ($activeModels as $provider => $models) {
            $this->info("\n🔹 {$provider}:");
            foreach ($models as $model => $description) {
                $this->line("   • {$model} - {$description}");
            }
        }
        
        // 5. Resumo final
        $this->info("\n📊 RESUMO DA CORREÇÃO:");
        
        if ($totalFixed > 0) {
            $this->info("✅ {$totalFixed} configuração(ões) corrigida(s) com sucesso!");
        } else {
            $this->info("✅ Nenhuma correção necessária - todos os modelos estão atualizados");
        }
        
        if ($remainingIssues > 0) {
            $this->warn("⚠️  {$remainingIssues} modelo(s) ainda precisam de atenção manual");
            $this->info("💡 Acesse /settings/chatbot-config para atualizar manualmente");
        } else {
            $this->info("🎉 Todos os modelos estão atualizados e funcionais!");
        }
        
        // 6. URLs úteis
        $this->info("\n🌐 URLs para configuração:");
        $this->line("   - Chatbot: http://172.20.120.180/settings/chatbot-config");
        $this->line("   - Provedores: http://172.20.120.180/iaprovider-config");
        $this->line("   - Múltiplas IAs: http://172.20.120.180/multiple-ai-config");
        
        return $remainingIssues === 0 ? 0 : 1;
    }
}
