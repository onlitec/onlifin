<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FinancialChatbotService;
use App\Services\AIConfigService;
use App\Models\User;
use App\Models\ChatbotConfig;

class ValidateChatbot extends Command
{
    protected $signature = 'validate:chatbot {--user-id=1}';
    protected $description = 'Valida se o chatbot está funcionando corretamente';

    public function handle()
    {
        $this->info("🔍 VALIDAÇÃO DO CHATBOT FINANCEIRO");
        
        $userId = $this->option('user-id');
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("❌ Usuário não encontrado");
            return 1;
        }
        
        $this->info("👤 Usuário: {$user->name}");
        
        $issues = 0;
        
        // 1. Verificar configuração
        $this->info("\n🔧 Verificando configuração...");
        
        $chatbotConfig = ChatbotConfig::getDefault($userId);
        if ($chatbotConfig && $chatbotConfig->enabled) {
            $this->info("✅ Configuração do chatbot ativa");
        } else {
            $this->warn("⚠️  Configuração do chatbot não encontrada ou inativa");
        }
        
        // 2. Testar serviço básico
        $this->info("\n🧪 Testando serviço básico...");
        
        try {
            $aiConfigService = new AIConfigService();
            $financialChatbotService = new FinancialChatbotService($aiConfigService);
            
            $result = $financialChatbotService->processMessage("Olá", $user);
            
            if (isset($result['success']) && $result['success']) {
                $this->info("✅ Serviço de chatbot funcionando");
                
                if (isset($result['response']['text']) && !empty($result['response']['text'])) {
                    $this->info("✅ Resposta gerada com sucesso");
                } else {
                    $this->warn("⚠️  Resposta vazia ou inválida");
                    $issues++;
                }
                
            } else {
                $this->error("❌ Erro no serviço de chatbot");
                $issues++;
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Exceção no serviço: " . substr($e->getMessage(), 0, 100));
            $issues++;
        }
        
        // 3. Testar previsões
        $this->info("\n📈 Testando previsões...");
        
        try {
            $aiConfigService = new AIConfigService();
            $financialChatbotService = new FinancialChatbotService($aiConfigService);
            
            $predictions = $financialChatbotService->generatePredictions($user, 1);
            
            if (isset($predictions['success']) && $predictions['success']) {
                $this->info("✅ Previsões funcionando");
            } else {
                $this->warn("⚠️  Problema nas previsões");
                $issues++;
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Erro nas previsões: " . substr($e->getMessage(), 0, 100));
            $issues++;
        }
        
        // 4. Verificar arquivos essenciais
        $this->info("\n📁 Verificando arquivos...");
        
        $files = [
            'app/Services/FinancialChatbotService.php',
            'app/Models/ChatbotConfig.php',
            'app/Http/Controllers/ChatbotConfigController.php',
            'resources/views/settings/chatbot-config.blade.php'
        ];
        
        foreach ($files as $file) {
            if (file_exists(base_path($file))) {
                $this->info("✅ {$file}");
            } else {
                $this->error("❌ {$file} não encontrado");
                $issues++;
            }
        }
        
        // 5. Verificar rotas
        $this->info("\n🌐 Verificando rotas...");
        
        try {
            $routes = [
                'chatbot.ask',
                'settings.chatbot-config.index',
                'settings.chatbot-config.store'
            ];
            
            foreach ($routes as $routeName) {
                if (route($routeName, [], false)) {
                    $this->info("✅ Rota {$routeName}");
                } else {
                    $this->error("❌ Rota {$routeName} não encontrada");
                    $issues++;
                }
            }
            
        } catch (\Exception $e) {
            $this->warn("⚠️  Erro ao verificar rotas: " . substr($e->getMessage(), 0, 50));
        }
        
        // 6. Resumo final
        $this->info("\n📊 RESULTADO DA VALIDAÇÃO:");
        
        if ($issues === 0) {
            $this->info("🎉 SUCESSO: Sistema do chatbot está funcionando perfeitamente!");
            $this->info("✅ Todos os componentes validados");
            $this->info("✅ Serviços operacionais");
            $this->info("✅ Arquivos presentes");
            $this->info("✅ Rotas configuradas");
            
            $this->info("\n🌐 URLs para teste:");
            $this->line("   - Chatbot: http://172.20.120.180/chatbot");
            $this->line("   - Configuração: http://172.20.120.180/settings/chatbot-config");
            
            return 0;
            
        } else {
            $this->warn("⚠️  PROBLEMAS ENCONTRADOS: {$issues} issues");
            $this->info("💡 Verifique os itens marcados com ❌ acima");
            
            return 1;
        }
    }
}
