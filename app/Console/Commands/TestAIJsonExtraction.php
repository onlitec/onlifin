<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AICategorizationService;
use App\Models\User;

class TestAIJsonExtraction extends Command
{
    protected $signature = 'test:ai-json-extraction {user_id=2}';
    protected $description = 'Testa a extração de JSON das respostas da IA';

    public function handle()
    {
        $userId = $this->argument('user_id');
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("Usuário com ID {$userId} não encontrado");
            return 1;
        }
        
        auth()->login($user);
        
        $this->info("🧪 Testando extração de JSON das respostas da IA");
        $this->line("  Usuário: {$user->name} (ID: {$user->id})");
        
        // Transação simples para teste
        $testTransactions = [
            [
                'date' => '2025-07-13',
                'description' => 'Compra no débito - PADARIA TESTE',
                'amount' => 1500, // R$ 15,00
                'type' => 'expense'
            ]
        ];
        
        $this->info("\n📋 Transação de teste:");
        $this->line("  Descrição: " . $testTransactions[0]['description']);
        $this->line("  Valor: R$ " . number_format($testTransactions[0]['amount'] / 100, 2, ',', '.'));
        $this->line("  Tipo: " . $testTransactions[0]['type']);
        
        // Testar categorização
        $this->info("\n🤖 Executando categorização...");
        
        try {
            $categorizationService = new AICategorizationService();
            
            // Usar reflexão para acessar método privado de debug
            $reflection = new \ReflectionClass($categorizationService);
            
            // Testar método callAI diretamente
            $callAIMethod = $reflection->getMethod('callAI');
            $callAIMethod->setAccessible(true);
            
            // Construir prompt simples
            $prompt = $this->buildTestPrompt($testTransactions);
            
            $this->line("📝 Prompt enviado:");
            $this->line(substr($prompt, 0, 200) . "...");
            
            $result = $callAIMethod->invoke($categorizationService, $prompt);
            
            $this->info("\n✅ Categorização bem-sucedida!");
            $this->line("  Resultados obtidos: " . count($result));
            
            foreach ($result as $i => $item) {
                $this->line("  Transação {$i}:");
                $this->line("    Categoria: " . ($item['suggested_category_name'] ?? 'N/A'));
                $this->line("    Confiança: " . round(($item['confidence'] ?? 0) * 100) . "%");
            }
            
        } catch (\Exception $e) {
            $this->error("\n❌ Erro na categorização: " . $e->getMessage());
            
            // Tentar obter mais detalhes do erro
            $this->line("\n🔍 Detalhes do erro:");
            $this->line("  Classe: " . get_class($e));
            $this->line("  Arquivo: " . $e->getFile() . ":" . $e->getLine());
            
            // Verificar se é erro de JSON
            if (strpos($e->getMessage(), 'JSON') !== false) {
                $this->warn("  ⚠️  Erro relacionado à extração de JSON");
                $this->line("  💡 Possíveis causas:");
                $this->line("     • Resposta da IA em formato inesperado");
                $this->line("     • Caracteres especiais na resposta");
                $this->line("     • Timeout ou resposta incompleta");
            }
            
            return 1;
        }
        
        // Testar com diferentes provedores se disponível
        $this->info("\n🔄 Testando provedores individuais...");
        
        $this->testProviderDirectly('gemini');
        $this->testProviderDirectly('groq');
        
        return 0;
    }
    
    private function buildTestPrompt(array $transactions): string
    {
        $prompt = "Analise as seguintes transações e categorize cada uma:\n\n";
        
        foreach ($transactions as $i => $transaction) {
            $prompt .= "Transação {$i}:\n";
            $prompt .= "- Data: {$transaction['date']}\n";
            $prompt .= "- Descrição: {$transaction['description']}\n";
            $prompt .= "- Valor: R$ " . number_format($transaction['amount'] / 100, 2, ',', '.') . "\n";
            $prompt .= "- Tipo: {$transaction['type']}\n\n";
        }
        
        $prompt .= "Responda APENAS com um array JSON no formato:\n";
        $prompt .= "[\n";
        $prompt .= "  {\n";
        $prompt .= "    \"transaction_index\": 0,\n";
        $prompt .= "    \"suggested_category_name\": \"Nome da Categoria\",\n";
        $prompt .= "    \"confidence\": 0.95,\n";
        $prompt .= "    \"reasoning\": \"Explicação da categorização\"\n";
        $prompt .= "  }\n";
        $prompt .= "]\n";
        
        return $prompt;
    }
    
    private function testProviderDirectly(string $provider): void
    {
        $this->line("\n🧪 Testando provedor: {$provider}");
        
        try {
            $config = $this->getProviderConfig($provider);
            
            if (!$config) {
                $this->warn("  ⚠️  Configuração não encontrada para {$provider}");
                return;
            }
            
            $this->line("  ✅ Configuração encontrada");
            $this->line("  🔗 Testando conexão...");
            
            // Teste simples de conexão
            $testResult = $this->testProviderConnection($provider, $config);
            
            if ($testResult) {
                $this->info("  ✅ Conexão bem-sucedida");
            } else {
                $this->error("  ❌ Falha na conexão");
            }
            
        } catch (\Exception $e) {
            $this->error("  ❌ Erro: " . $e->getMessage());
        }
    }
    
    private function getProviderConfig(string $provider): ?array
    {
        if ($provider === 'gemini') {
            $aiConfigService = new \App\Services\AIConfigService();
            return $aiConfigService->getAIConfig('gemini');
        }
        
        if ($provider === 'groq') {
            $groqConfig = \App\Models\ModelApiKey::where('provider', 'groq')
                ->where('is_active', true)
                ->first();
                
            if ($groqConfig) {
                return [
                    'provider' => 'groq',
                    'model' => $groqConfig->model,
                    'api_key' => $groqConfig->api_token
                ];
            }
        }
        
        return null;
    }
    
    private function testProviderConnection(string $provider, array $config): bool
    {
        try {
            if ($provider === 'gemini') {
                $response = \Illuminate\Support\Facades\Http::timeout(30)
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/{$config['model']}:generateContent?key={$config['api_key']}", [
                        'contents' => [
                            ['parts' => [['text' => 'Test connection']]]
                        ]
                    ]);
                    
                return $response->successful();
            }
            
            if ($provider === 'groq') {
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'Authorization' => 'Bearer ' . $config['api_key'],
                    'Content-Type' => 'application/json'
                ])->timeout(30)->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => $config['model'],
                    'messages' => [
                        ['role' => 'user', 'content' => 'Test connection']
                    ],
                    'max_tokens' => 10
                ]);
                
                return $response->successful();
            }
            
            return false;
            
        } catch (\Exception $e) {
            return false;
        }
    }
}
