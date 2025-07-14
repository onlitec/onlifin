<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FinancialChatbotService;
use App\Services\AIConfigService;
use App\Models\User;
use App\Models\ChatbotConfig;

class TestChatbotFinal extends Command
{
    protected $signature = 'test:chatbot-final {--user-id=1}';
    protected $description = 'Teste final completo do chatbot financeiro';

    public function handle()
    {
        $this->info("🎯 TESTE FINAL DO CHATBOT FINANCEIRO");
        
        $userId = $this->option('user-id');
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("❌ Usuário não encontrado");
            return 1;
        }
        
        $this->info("👤 Usuário: {$user->name} (ID: {$user->id})");
        
        // 1. Verificar configuração
        $this->info("\n🔧 1. Verificando Configuração...");
        
        $chatbotConfig = ChatbotConfig::getDefault($userId);
        if ($chatbotConfig) {
            $this->info("✅ Configuração do chatbot encontrada");
            $this->line("   - Provedor: {$chatbotConfig->provider}");
            $this->line("   - Modelo: {$chatbotConfig->model}");
            $this->line("   - Status: " . ($chatbotConfig->enabled ? 'Ativo' : 'Inativo'));
        } else {
            $this->warn("⚠️  Nenhuma configuração específica do chatbot");
        }
        
        // 2. Testar perguntas financeiras
        $this->info("\n💬 2. Testando Perguntas Financeiras...");
        
        $questions = [
            "Qual é o meu saldo atual?",
            "Quanto gastei este mês?",
            "Quais são minhas principais categorias de gastos?",
            "Me faça uma análise das minhas finanças"
        ];
        
        $aiConfigService = new AIConfigService();
        $financialChatbotService = new FinancialChatbotService($aiConfigService);
        
        $successCount = 0;
        $totalQuestions = count($questions);
        
        foreach ($questions as $index => $question) {
            $this->line("\n" . ($index + 1) . ". Pergunta: \"{$question}\"");
            
            try {
                $startTime = microtime(true);
                $result = $financialChatbotService->processMessage($question, $user);
                $responseTime = round((microtime(true) - $startTime) * 1000, 2);
                
                if ($result['success']) {
                    $this->info("   ✅ Sucesso em {$responseTime}ms");

                    $intent = $result['intent'] ?? 'desconhecida';
                    $this->line("   🎯 Intenção: {$intent}");

                    $response = $result['response']['text'] ?? 'Sem resposta';
                    if (is_array($response)) {
                        $response = json_encode($response);
                    }
                    $preview = strlen($response) > 100 ? substr($response, 0, 100) . '...' : $response;
                    $this->line("   💬 Resposta: {$preview}");

                    $successCount++;
                } else {
                    $error = $result['error'] ?? 'Erro desconhecido';
                    if (is_array($error)) {
                        $error = json_encode($error);
                    }
                    $this->error("   ❌ Erro: {$error}");
                }
                
            } catch (\Exception $e) {
                $this->error("   ❌ Exceção: {$e->getMessage()}");
            }
        }
        
        // 3. Testar previsões
        $this->info("\n📈 3. Testando Previsões Financeiras...");
        
        try {
            $predictions = $financialChatbotService->generatePredictions($user, 2);
            
            if ($predictions['success']) {
                $this->info("✅ Previsões geradas com sucesso");
                foreach ($predictions['predictions'] as $prediction) {
                    $this->line("   📅 {$prediction['month_name']}: " .
                               "Receita R$ " . number_format($prediction['predicted_income'], 2, ',', '.') .
                               " | Despesa R$ " . number_format($prediction['predicted_expense'], 2, ',', '.') .
                               " | Confiança: " . ($prediction['confidence'] * 100) . "%");
                }
            } else {
                $this->error("❌ Erro ao gerar previsões: {$predictions['error']}");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Exceção ao gerar previsões: {$e->getMessage()}");
        }
        
        // 4. Verificar URLs
        $this->info("\n🌐 4. URLs de Teste:");
        $this->line("   - Chatbot: http://172.20.120.180/chatbot");
        $this->line("   - Configuração: http://172.20.120.180/settings/chatbot-config");
        $this->line("   - Provedores IA: http://172.20.120.180/iaprovider-config");
        
        // 5. Resumo final
        $this->info("\n📊 RESUMO FINAL:");
        
        $successRate = ($successCount / $totalQuestions) * 100;
        
        if ($successRate >= 75) {
            $this->info("🎉 EXCELENTE: {$successCount}/{$totalQuestions} perguntas respondidas com sucesso ({$successRate}%)");
            $this->info("✅ Sistema do chatbot funcionando perfeitamente!");
        } elseif ($successRate >= 50) {
            $this->warn("⚠️  BOM: {$successCount}/{$totalQuestions} perguntas respondidas com sucesso ({$successRate}%)");
            $this->info("💡 Sistema funcionando, mas pode precisar de ajustes");
        } else {
            $this->error("❌ PROBLEMAS: {$successCount}/{$totalQuestions} perguntas respondidas com sucesso ({$successRate}%)");
            $this->info("🔧 Sistema precisa de correções");
        }
        
        // 6. Instruções finais
        $this->info("\n📋 PRÓXIMOS PASSOS:");
        
        if ($successRate >= 75) {
            $this->line("1. ✅ Sistema pronto para uso!");
            $this->line("2. 🧪 Teste na interface web: http://172.20.120.180/chatbot");
            $this->line("3. ⚙️ Ajuste configurações se necessário");
            $this->line("4. 📊 Monitore performance e precisão das respostas");
        } else {
            $this->line("1. 🔧 Verifique configuração da IA");
            $this->line("2. 🔑 Confirme se API keys estão corretas");
            $this->line("3. 📊 Verifique se há dados financeiros suficientes");
            $this->line("4. 🧪 Execute novamente após correções");
        }
        
        return $successRate >= 50 ? 0 : 1;
    }
}
