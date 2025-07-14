<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FinancialChatbotService;
use App\Services\AIConfigService;
use App\Models\User;

class DebugChatbot extends Command
{
    protected $signature = 'debug:chatbot {--user-id=1}';
    protected $description = 'Debug específico do chatbot';

    public function handle()
    {
        $this->info("🐛 Debug Específico do Chatbot");
        
        $userId = $this->option('user-id');
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("❌ Usuário não encontrado");
            return 1;
        }
        
        try {
            $aiConfigService = new AIConfigService();
            $financialChatbotService = new FinancialChatbotService($aiConfigService);
            
            $message = "Olá";
            $result = $financialChatbotService->processMessage($message, $user);
            
            $this->info("🔍 Analisando resultado...");
            $this->line("Tipo do resultado: " . gettype($result));
            
            if (is_array($result)) {
                $this->line("Chaves do resultado: " . implode(', ', array_keys($result)));
                
                if (isset($result['success'])) {
                    $this->line("Success: " . ($result['success'] ? 'true' : 'false'));
                }
                
                if (isset($result['response'])) {
                    $this->line("Tipo da response: " . gettype($result['response']));
                    
                    if (is_array($result['response'])) {
                        $this->line("Chaves da response: " . implode(', ', array_keys($result['response'])));
                        
                        foreach ($result['response'] as $key => $value) {
                            $type = gettype($value);
                            $preview = is_array($value) ? '[array]' : (is_string($value) ? substr($value, 0, 50) . '...' : $value);
                            $this->line("  {$key}: {$type} = {$preview}");
                        }
                    }
                }
                
                if (isset($result['intent'])) {
                    $this->line("Intent: " . gettype($result['intent']) . " = " . $result['intent']);
                }
                
                if (isset($result['data_used'])) {
                    $this->line("Data used: " . gettype($result['data_used']) . " = " . json_encode($result['data_used']));
                }
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Exceção: {$e->getMessage()}");
            $this->line("📍 Arquivo: {$e->getFile()}:{$e->getLine()}");
        }
        
        return 0;
    }
}
