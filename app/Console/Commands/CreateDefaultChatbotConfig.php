<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ChatbotConfig;
use App\Models\User;
use App\Services\AIConfigService;

class CreateDefaultChatbotConfig extends Command
{
    protected $signature = 'chatbot:create-default-config {--user-id=1}';
    protected $description = 'Cria uma configuração padrão do chatbot financeiro';

    public function handle()
    {
        $this->info("🤖 Criando Configuração Padrão do Chatbot Financeiro");
        
        $userId = $this->option('user-id');
        
        // Verificar usuário
        $user = User::find($userId);
        if (!$user) {
            $this->error("❌ Usuário com ID {$userId} não encontrado");
            return 1;
        }
        
        $this->info("👤 Usuário: {$user->name} (ID: {$user->id})");
        
        // Verificar se já existe configuração
        $existingConfig = ChatbotConfig::getDefault($userId);
        if ($existingConfig) {
            $this->warn("⚠️  Já existe uma configuração padrão para este usuário");
            
            if (!$this->confirm('Deseja sobrescrever a configuração existente?')) {
                $this->info("❌ Operação cancelada");
                return 0;
            }
            
            $existingConfig->delete();
            $this->info("🗑️  Configuração anterior removida");
        }
        
        // Obter configuração geral de IA para usar como base
        $aiConfigService = new AIConfigService();
        $generalConfig = $aiConfigService->getAIConfig();
        
        if (!$generalConfig || !$generalConfig['is_configured']) {
            $this->error("❌ Nenhuma configuração geral de IA encontrada");
            $this->info("💡 Configure primeiro um provedor de IA em /iaprovider-config");
            return 1;
        }
        
        $this->info("🔧 Usando configuração base:");
        $this->line("   - Provedor: {$generalConfig['provider']}");
        $this->line("   - Modelo: {$generalConfig['model']}");
        
        // Criar configuração do chatbot
        try {
            $chatbotConfig = ChatbotConfig::create([
                'user_id' => $userId,
                'name' => 'Assistente Financeiro IA',
                'provider' => $generalConfig['provider'],
                'model' => $generalConfig['model'],
                'api_key' => $generalConfig['api_key'],
                'endpoint' => null,
                'system_prompt' => ChatbotConfig::getDefaultFinancialPrompt(),
                'temperature' => 0.7,
                'max_tokens' => 1500,
                'enabled' => true,
                'is_default' => true,
                'settings' => [
                    'created_by_command' => true,
                    'auto_generated' => true,
                    'version' => '1.0'
                ]
            ]);
            
            $this->info("✅ Configuração do chatbot criada com sucesso!");
            $this->line("   - ID: {$chatbotConfig->id}");
            $this->line("   - Nome: {$chatbotConfig->name}");
            $this->line("   - Provedor: {$chatbotConfig->provider}");
            $this->line("   - Modelo: {$chatbotConfig->model}");
            $this->line("   - Status: " . ($chatbotConfig->enabled ? 'Ativo' : 'Inativo'));
            
            // Testar configuração
            $this->info("\n🧪 Testando configuração...");
            
            $testResult = $chatbotConfig->testConnection();
            if ($testResult['success']) {
                $this->info("✅ Teste de conexão bem-sucedido!");
                if (isset($testResult['response_time'])) {
                    $this->line("   - Tempo de resposta: {$testResult['response_time']}");
                }
            } else {
                $this->warn("⚠️  Teste de conexão falhou: {$testResult['message']}");
                $this->info("💡 A configuração foi criada, mas pode precisar de ajustes");
            }
            
            // Informações úteis
            $this->info("\n📋 Próximos Passos:");
            $this->line("1. Acesse http://172.20.120.180/settings/chatbot-config para ajustar");
            $this->line("2. Teste o chatbot em http://172.20.120.180/chatbot");
            $this->line("3. Execute 'php artisan test:financial-chatbot' para validar");
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Erro ao criar configuração: {$e->getMessage()}");
            return 1;
        }
    }
}
