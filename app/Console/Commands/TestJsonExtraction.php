<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AICategorizationService;
use ReflectionClass;

class TestJsonExtraction extends Command
{
    protected $signature = 'ai:test-json-extraction';
    protected $description = 'Testa a extração de JSON da resposta da IA';

    public function handle()
    {
        $this->info("🧪 Testando extração de JSON da IA...");
        
        // Exemplo real que está falhando baseado no log
        $aiResponse = '```json
[
  {
    "transaction_index": 0,
    "suggested_category_name": "Receitas de Trabalho",
    "category_exists": true,
    "existing_category_id": 28,
    "confidence": 0.8,
    "reasoning": "Transferência recebida via Pix, provavelmente relacionada a trabalho."
  },
  {
    "transaction_index": 1,
    "suggested_category_name": "Alimentação",
    "category_exists": true,
    "existing_category_id": 15,
    "confidence": 0.95,
    "reasoning": "Padaria é claramente relacionada a alimentação."
  }
]
```';

        $this->info("📝 Resposta da IA para teste:");
        $this->line(substr($aiResponse, 0, 200) . "...");
        
        try {
            // Usar reflexão para acessar o método privado
            $aiService = new AICategorizationService();
            $reflection = new ReflectionClass($aiService);
            $method = $reflection->getMethod('extractJsonFromResponse');
            $method->setAccessible(true);
            
            $result = $method->invoke($aiService, $aiResponse);
            
            $this->info("\n✅ Extração bem-sucedida!");
            $this->line("Número de itens extraídos: " . count($result));
            
            foreach ($result as $i => $item) {
                $this->line("  Item {$i}:");
                $this->line("    - transaction_index: " . ($item['transaction_index'] ?? 'N/A'));
                $this->line("    - suggested_category_name: " . ($item['suggested_category_name'] ?? 'N/A'));
                $this->line("    - confidence: " . ($item['confidence'] ?? 'N/A'));
                $this->line("    - reasoning: " . substr($item['reasoning'] ?? 'N/A', 0, 50) . "...");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Erro na extração: " . $e->getMessage());
            
            // Testar métodos alternativos manualmente
            $this->info("\n🔧 Testando métodos alternativos...");
            
            // Método 1: Regex simples
            if (preg_match('/```json\s*(.*?)\s*```/s', $aiResponse, $matches)) {
                $jsonContent = trim($matches[1]);
                $this->info("Método 1 - JSON extraído:");
                $this->line(substr($jsonContent, 0, 200) . "...");
                
                $decoded = json_decode($jsonContent, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $this->info("✅ Método 1 funcionou! Itens: " . count($decoded));
                } else {
                    $this->error("❌ Método 1 falhou: " . json_last_error_msg());
                }
            }
            
            // Método 2: Busca por colchetes
            $jsonStart = strpos($aiResponse, '[');
            $jsonEnd = strrpos($aiResponse, ']');
            
            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonContent = substr($aiResponse, $jsonStart, $jsonEnd - $jsonStart + 1);
                $this->info("Método 2 - JSON por posição:");
                $this->line(substr($jsonContent, 0, 200) . "...");
                
                $decoded = json_decode($jsonContent, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $this->info("✅ Método 2 funcionou! Itens: " . count($decoded));
                } else {
                    $this->error("❌ Método 2 falhou: " . json_last_error_msg());
                }
            }
        }
        
        return 0;
    }
}
