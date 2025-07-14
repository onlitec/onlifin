<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FinancialChatbotService;
use App\Services\AIConfigService;
use App\Models\User;

class TestChatbotSimple extends Command
{
    protected $signature = 'test:chatbot-simple {--user-id=1}';
    protected $description = 'Teste simples do chatbot para debug';

    public function handle()
    {
        $this->info("🔍 Teste Simples do Chatbot - Debug");
        
        $userId = $this->option('user-id');
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("❌ Usuário não encontrado");
            return 1;
        }
        
        $this->info("👤 Usuário: {$user->name}");
        
        try {
            $aiConfigService = new AIConfigService();
            $financialChatbotService = new FinancialChatbotService($aiConfigService);
            
            $this->info("🧪 Testando mensagem simples...");
            
            $message = "Olá";
            $this->line("📝 Mensagem: {$message}");
            
            $result = $financialChatbotService->processMessage($message, $user);
            
            $this->info("📊 Resultado:");
            $this->line("   - Sucesso: " . ($result['success'] ? 'Sim' : 'Não'));
            
            if ($result['success']) {
                $this->info("✅ Resposta gerada com sucesso!");
                $this->line("   - Intenção: " . ($result['intent'] ?? 'desconhecida'));
                $this->line("   - Tipo: " . ($result['response']['type'] ?? 'text'));

                $confidence = $result['response']['confidence'] ?? 0;
                if (is_array($confidence)) {
                    $confidence = json_encode($confidence);
                }
                $this->line("   - Confiança: {$confidence}");

                $responseText = $result['response']['text'] ?? 'Sem resposta';
                if (is_array($responseText)) {
                    $responseText = json_encode($responseText);
                }
                $preview = strlen($responseText) > 200 ? substr($responseText, 0, 200) . '...' : $responseText;
                $this->line("   - Resposta: {$preview}");

                if (isset($result['data_used']) && is_array($result['data_used'])) {
                    $this->line("   - Dados usados: " . implode(', ', $result['data_used']));
                }
            } else {
                $this->error("❌ Erro: {$result['error']}");
                if (isset($result['debug'])) {
                    $this->line("🐛 Debug: {$result['debug']}");
                }
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Exceção: {$e->getMessage()}");
            $this->line("📍 Arquivo: {$e->getFile()}:{$e->getLine()}");
            $this->line("🔍 Trace:");
            $this->line($e->getTraceAsString());
        }
        
        return 0;
    }
}
