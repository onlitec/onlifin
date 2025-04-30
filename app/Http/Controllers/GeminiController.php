<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Category;

class GeminiController extends Controller
{
    /**
     * Categoriza transações usando a API Gemini
     * 
     * @param Request $request Contém as transações para categorizar
     * @return \Illuminate\Http\JsonResponse
     */
    public function categorizeTransactions(Request $request)
    {
        $transactions = $request->input('transactions', []);
        
        if (empty($transactions)) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhuma transação para analisar'
            ]);
        }
        
        // Analisar transações com Gemini
        $result = $this->processTransactionsWithGemini($transactions);
        
        if ($result) {
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } else {
            // Gerar resposta simulada em caso de falha
            $mockResponse = $this->generateMockResponse($transactions);
            return response()->json([
                'success' => true,
                'data' => $mockResponse,
                'mock' => true
            ]);
        }
    }
    
    /**
     * Analisa transações em tempo real durante a importação
     * 
     * @param Request $request Contém as transações para analisar em tempo real
     * @return \Illuminate\Http\JsonResponse
     */
    public function analyzeTransactionsRealtime(Request $request)
    {
        // Validar os dados recebidos
        $validated = $request->validate([
            'transactions' => 'required|array|min:1',
            'current_index' => 'required|integer|min:0',
            'batch_size' => 'required|integer|min:1|max:10' // Limitar o tamanho do lote para evitar sobrecarga
        ]);

        $transactions = $validated['transactions'];
        $currentIndex = $validated['current_index'];
        $batchSize = $validated['batch_size'];
        
        if (empty($transactions)) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhuma transação para analisar'
            ]);
        }
        
        // Determinar o lote atual de transações a analisar
        $batch = array_slice($transactions, $currentIndex, $batchSize);
        
        if (empty($batch)) {
            return response()->json([
                'success' => true,
                'complete' => true,
                'message' => 'Análise concluída',
                'progress' => 100
            ]);
        }
        
        // Calcular progresso
        $totalTransactions = count($transactions);
        $progress = min(100, round(($currentIndex + count($batch)) / $totalTransactions * 100));
        
        // Analisar o lote atual com Gemini
        $result = $this->processTransactionsWithGemini($batch);
        
        if (!$result) {
            // Usar categorização local em caso de falha
            $result = $this->generateMockResponse($batch);
        }
        
        return response()->json([
            'success' => true,
            'complete' => ($currentIndex + $batchSize) >= $totalTransactions,
            'data' => $result,
            'progress' => $progress,
            'next_index' => $currentIndex + $batchSize
        ]);
    }
    
    /**
     * Processa transações com a API Gemini
     * 
     * @param array $transactions Lista de transações para categorizar
     * @return array|null Transações categorizadas ou null em caso de falha
     */
    private function processTransactionsWithGemini($transactions)
    {
        try {
            // Obter credenciais diretamente das variáveis de ambiente
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
            
            // Atualizar prompt para usar placeholders e substituição dinâmica
            $promptTemplate = "Categorize as seguintes transações bancárias:\n\nTransações:\n{{transactions}}\n\nCategorias disponíveis:\n{{categories}}\n\nPara cada transação, determine:\n1. Se é uma RECEITA ou DESPESA\n2. A categoria existente mais adequada (pelo ID)\n3. Sugira nova categoria se nenhuma existente for adequada\n\nResponda apenas com JSON no formato:\n{
  \"transactions\": [
    {
      \"id\": 0,
      \"type\": \"income\" ou \"expense\",\n      \"category_id\": ID ou null,\n      \"suggested_category\": \"Nome\" (se category_id for null)\n    }
  ]
}";
            $prompt = str_replace(['{{transactions}}', '{{categories}}'], [json_encode($transactionData, JSON_PRETTY_PRINT), json_encode($categories, JSON_PRETTY_PRINT)], $promptTemplate);
            
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
            
            // Configurar requisição HTTP
            $options = [
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: application/json',
                    'content' => json_encode($data),
                    'timeout' => 60
                ]
            ];
            
            // Log da requisição
            Log::info('Enviando requisição para API Gemini', [
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
            $response = json_decode($result, true);
            
            if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
                $text = $response['candidates'][0]['content']['parts'][0]['text'];
                
                // Extrair JSON da resposta (pode estar dentro de markdown)
                preg_match('/\{.*\}/s', $text, $matches);
                $jsonContent = !empty($matches[0]) ? $matches[0] : $text;
                
                // Decodificar o JSON
                $processedData = json_decode($jsonContent, true);
                
                if ($processedData && isset($processedData['transactions'])) {
                    Log::info('Categorização com Gemini concluída', [
                        'total_categorizado' => count($processedData['transactions'])
                    ]);
                    return $processedData;
                }
            }
            
            Log::error('Formato de resposta Gemini inesperado');
            return null;
            
        } catch (\Exception $e) {
            Log::error('Erro ao processar transações com Gemini', [
                'mensagem' => $e->getMessage(),
                'arquivo' => $e->getFile(),
                'linha' => $e->getLine()
            ]);
            return null;
        }
    }
    
    /**
     * Gera uma resposta simulada para categorização
     * 
     * @param array $transactions Transações para categorizar
     * @return array Resposta simulada
     */
    private function generateMockResponse($transactions)
    {
        $response = ['transactions' => []];
        $keywords = [
            'salário' => ['salário', 'salario', 'pagamento', 'pgto', 'proventos'],
            'alimentação' => ['mercado', 'supermercado', 'restaurante', 'lanchonete', 'padaria', 'carrefour', 'pão de açúcar'],
            'transporte' => ['uber', '99', 'taxi', 'táxi', 'combustível', 'combustivel', 'gasolina', 'estacionamento'],
            'moradia' => ['aluguel', 'condomínio', 'condominio', 'iptu', 'luz', 'água', 'agua', 'energia', 'gás', 'gas'],
            'lazer' => ['cinema', 'teatro', 'show', 'netflix', 'spotify', 'viagem', 'passeio'],
            'saúde' => ['farmácia', 'farmacia', 'médico', 'medico', 'consulta', 'exame', 'hospital'],
            'educação' => ['escola', 'faculdade', 'curso', 'livro', 'mensalidade'],
        ];
        
        // Categorias existentes
        $categories = Category::where('user_id', auth()->id())->get();
        $expenseCategories = $categories->where('type', 'expense')->pluck('id', 'name')->toArray();
        $incomeCategories = $categories->where('type', 'income')->pluck('id', 'name')->toArray();
        
        foreach ($transactions as $index => $transaction) {
            $description = strtolower($transaction['description'] ?? '');
            $amount = $transaction['amount'] ?? 0;
            $type = $amount >= 0 ? 'income' : 'expense';
            
            $categoryId = null;
            $suggestedCategory = null;
            
            // Tentativa simples de categorização baseada em palavras-chave
            if ($type == 'income') {
                // Verifica palavras-chave para receitas
                if (in_array('salário', $incomeCategories) && $this->containsAnyKeyword($description, $keywords['salário'])) {
                    $categoryId = $incomeCategories['salário'];
                } else {
                    $suggestedCategory = 'Receitas Diversas';
                }
            } else {
                // Verifica palavras-chave para despesas
                foreach ($keywords as $category => $terms) {
                    if ($this->containsAnyKeyword($description, $terms)) {
                        if (isset($expenseCategories[ucfirst($category)])) {
                            $categoryId = $expenseCategories[ucfirst($category)];
                            break;
                        } else {
                            $suggestedCategory = ucfirst($category);
                            break;
                        }
                    }
                }
                
                // Se não encontrou categoria, sugere uma genérica
                if (!$categoryId && !$suggestedCategory) {
                    $suggestedCategory = 'Despesas Diversas';
                }
            }
            
            $response['transactions'][] = [
                'id' => $index,
                'type' => $type,
                'category_id' => $categoryId,
                'suggested_category' => $suggestedCategory
            ];
        }
        
        return $response;
    }
    
    /**
     * Verifica se um texto contém alguma das palavras-chave
     */
    private function containsAnyKeyword($text, $keywords)
    {
        foreach ($keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }
}
