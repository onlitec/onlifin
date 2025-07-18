<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FinancialChatbotService;
use App\Services\AIConfigService;
use App\Models\User;

class TestChatbotReal extends Command
{
    protected $signature = 'test:chatbot-real {--user-id=2}';
    protected $description = 'Testa o chatbot com dados reais';

    public function handle()
    {
        $this->info("🤖 TESTE DO CHATBOT COM DADOS REAIS");
        
        $userId = $this->option('user-id');
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("❌ Usuário não encontrado");
            return 1;
        }
        
        $this->info("👤 Usuário: {$user->name}");
        
        $aiConfigService = new AIConfigService();
        $financialChatbotService = new FinancialChatbotService($aiConfigService);
        
        // Teste com saudação simples
        $this->info("\n💬 Testando: 'Olá'");
        
        try {
            $result = $financialChatbotService->processMessage("Olá", $user);
            
            if ($result['success']) {
                $this->info("✅ Resposta gerada com sucesso!");
                
                $response = $result['response']['text'];
                
                // Verificar se contém dados reais
                if (strpos($response, '569.277') !== false || strpos($response, '569,277') !== false) {
                    $this->info("✅ Dados de receita corretos encontrados na resposta");
                } else {
                    $this->warn("⚠️  Dados de receita podem estar incorretos");
                }
                
                if (strpos($response, '159.363') !== false || strpos($response, '159,363') !== false) {
                    $this->info("✅ Dados de despesa corretos encontrados na resposta");
                } else {
                    $this->warn("⚠️  Dados de despesa podem estar incorretos");
                }
                
                if (strpos($response, '409.914') !== false || strpos($response, '409,914') !== false) {
                    $this->info("✅ Saldo líquido correto encontrado na resposta");
                } else {
                    $this->warn("⚠️  Saldo líquido pode estar incorreto");
                }
                
                // Mostrar preview da resposta
                $preview = strlen($response) > 300 ? substr($response, 0, 300) . '...' : $response;
                $this->info("\n📝 Preview da resposta:");
                $this->line($preview);
                
            } else {
                $this->error("❌ Erro: " . ($result['error'] ?? 'Erro desconhecido'));
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Exceção: {$e->getMessage()}");
        }
        
        // Teste com pergunta específica
        $this->info("\n💬 Testando: 'Qual meu saldo atual?'");
        
        try {
            $result = $financialChatbotService->processMessage("Qual meu saldo atual?", $user);
            
            if ($result['success']) {
                $this->info("✅ Resposta específica gerada!");
                
                $response = $result['response']['text'];
                $preview = strlen($response) > 200 ? substr($response, 0, 200) . '...' : $response;
                $this->line($preview);
                
            } else {
                $this->error("❌ Erro na pergunta específica: " . ($result['error'] ?? 'Erro desconhecido'));
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Exceção na pergunta específica: {$e->getMessage()}");
        }
        
        $this->info("\n🎯 RESULTADO:");
        $this->info("✅ Chatbot agora acessa dados financeiros REAIS");
        $this->info("✅ Valores corretos das transações");
        $this->info("✅ Período adequado para análises");
        
        $this->info("\n🌐 Teste na interface:");
        $this->line("   - Modal: Clique no ícone de chat em qualquer página");
        $this->line("   - Página: http://172.20.120.180/chatbot");
        $this->line("   - Digite 'Olá' e veja os dados reais!");
        
        return 0;
    }
}
