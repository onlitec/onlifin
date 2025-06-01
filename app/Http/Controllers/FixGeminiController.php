<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Category;

/**
 * Este controlador contém apenas os métodos relacionados à IA Gemini
 * para facilitar a resolução de problemas
 */
class FixGeminiController extends Controller
{
    /**
     * Analisa transações usando a API Gemini
     * Versão simplificada que acessa diretamente as variáveis de ambiente
     */
    public function analyzeTransactionsWithGemini($transactions)
    {
        // Se não houver transações, retorna nulo
        if (empty($transactions)) {
            return null;
        }
        
        try {
            // Carregar chave API diretamente das variáveis de ambiente
            $apiKey = env('GEMINI_API_KEY');
            $model = env('GEMINI_MODEL', 'gemini-1.5-pro');
            
            if (empty($apiKey)) {
                Log::error('Chave API Gemini não encontrada nas variáveis de ambiente');
                return null;
            }
            
            // Preparar as transações para análise
            $transactionData = [];
            foreach ($transactions as $index => $transaction) {
                $transactionData[] = [
                    'id' => $index,
                    'date' => $transaction['date'] ?? '',
                    'description' => $transaction['description'] ?? '',
                    'amount' => $transaction['amount'] ?? 0
                ];
            }
            
            // Obter categorias do usuário
            $categories = Category::where('user_id', auth()->id())
                ->orderBy('name')
                ->get()
                ->groupBy('type')
                ->toArray();
            
            // Construir o prompt para a API
            $prompt = "Por favor, analise e categorize as seguintes transações bancárias:\n\n";
            $prompt .= "Transações:\n" . json_encode($transactionData, JSON_PRETTY_PRINT) . "\n\n";
            $prompt .= "Categorias disponíveis:\n" . json_encode($categories, JSON_PRETTY_PRINT) . "\n\n";
            $prompt .= "Instruções:\n"; 
            $prompt .= "1. Para cada transação, determine se é uma RECEITA ou DESPESA\n";
            $prompt .= "2. Atribua cada transação a uma categoria existente usando o ID da categoria, quando apropriado\n";
            $prompt .= "3. Se uma transação não se encaixar bem em nenhuma categoria existente, sugira uma nova categoria\n";
            $prompt .= "4. Ao sugerir novas categorias, mantenha consistência com as categorias existentes\n\n";
            $prompt .= "Formato esperado da resposta (apenas JSON):\n";
            $prompt .= "{\n  \"transactions\": [\n    {\n      \"id\": 0,\n      \"type\": \"income\" ou \"expense\",\n      \"category_id\": ID da categoria existente ou null,\n      \"suggested_category\": \"Nova categoria\" (apenas se category_id for null)\n    }\n  ]\n}";
            
            // Configurar endpoint e payload
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
            $data = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.2,
                    'maxOutputTokens' => 4096
                ]
            ];
            
            // Configurar a requisição
            $options = [
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: application/json',
                    'content' => json_encode($data),
                    'timeout' => 60
                ]
            ];
            
            // Registrar a tentativa de chamada em log
            Log::info('Iniciando chamada à API Gemini', [
                'model' => $model,
                'total_transacoes' => count($transactions)
            ]);
            
            // Executar a chamada à API
            $context = stream_context_create($options);
            $result = file_get_contents($endpoint, false, $context);
            
            if ($result === false) {
                Log::error('Falha na chamada à API Gemini');
                return null;
            }
            
            // Processar a resposta
            $responseData = json_decode($result, true);
            if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                $text = $responseData['candidates'][0]['content']['parts'][0]['text'];
                
                // Extrair o JSON da resposta (pode estar envolto em markdown)
                $cleanText = $text;
                
                // Remover todos os tipos de blocos de código markdown
                $cleanText = preg_replace('/```(?:json)?\s*/i', '', $cleanText);
                $cleanText = preg_replace('/\s*```/', '', $cleanText);
                
                // Remover qualquer texto antes do primeiro '{' ou '[' e depois do último '}' ou ']'
                if (preg_match('/[\{\[].*[\}\]]/s', $cleanText, $matches)) {
                    $cleanText = $matches[0];
                }
                
                // Tentar extrair JSON da resposta limpa
                preg_match('/[\{\[].*[\}\]]/s', $cleanText, $matches);
                $jsonContent = !empty($matches[0]) ? $matches[0] : trim($cleanText);
                
                // Limpar caracteres problemáticos
                $jsonContent = preg_replace('/[\x00-\x1F\x7F]/u', '', $jsonContent);
                
                // Decodificar o JSON
                $processedResponse = json_decode($jsonContent, true);
                
                // Log de erro se a decodificação falhar
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('❌ Erro ao decodificar JSON da resposta do Gemini no FixGeminiController', [
                        'error' => json_last_error_msg(),
                        'json_extract' => substr($jsonContent, 0, 500) . (strlen($jsonContent) > 500 ? '...' : '')
                    ]);
                    return null;
                }
                
                if ($processedResponse && isset($processedResponse['transactions'])) {
                    Log::info('Análise com Gemini concluída com sucesso', [
                        'total_transacoes_analisadas' => count($processedResponse['transactions'])
                    ]);
                    return $processedResponse;
                }
            }
            
            Log::error('Falha ao processar resposta da API Gemini');
            return null;
            
        } catch (\Exception $e) {
            Log::error('Exceção ao analisar transações com Gemini', [
                'mensagem' => $e->getMessage()
            ]);
            return null;
        }
    }
}
