<?php
// proxy-anthropic.php - Proxy para API Claude da Anthropic
// Integração com o sistema OnLifin para análise de extratos bancários

// Configuração de erro e timeout
ini_set('display_errors', 0);
set_time_limit(120); // 2 minutos para processar grandes arquivos

// Validação básica de segurança
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

// Carrega a chave da API do cabeçalho ou tenta buscar do banco de dados
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';

if (empty($apiKey)) {
    // Tenta obter a chave do banco de dados
    // Esta parte requer acesso ao Laravel, então é apenas um exemplo
    try {
        require_once __DIR__ . '/../bootstrap/app.php';
        $app = require_once __DIR__ . '/../bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        
        $apiKey = \App\Models\Setting::where('key', 'ai_statement_analyzer_api_key')->value('value');
    } catch (\Exception $e) {
        // Se não conseguir carregar o Laravel, continua sem a chave
    }
    
    if (empty($apiKey)) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Chave de API não fornecida']);
        exit;
    }
}

// Recebe o arquivo de extrato bancário
$file = $_FILES['file'] ?? null;
if (!$file) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Nenhum arquivo enviado']);
    exit;
}

// Verifica tamanho máximo (10MB)
if ($file['size'] > 10 * 1024 * 1024) {
    http_response_code(413);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Arquivo muito grande. Máximo 10MB.']);
    exit;
}

// Lê o conteúdo do arquivo
$fileContent = file_get_contents($file['tmp_name']);
if ($fileContent === false) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erro ao ler o arquivo']);
    exit;
}

$fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

// Codifica o conteúdo para base64 se for binário (PDF, Excel)
$binaryTypes = ['pdf', 'xls', 'xlsx'];
if (in_array($fileExtension, $binaryTypes)) {
    $fileContent = base64_encode($fileContent);
    
    // Para PDFs e Excel, adiciona informação sobre o tipo de arquivo
    $fileContent = "Este é um arquivo $fileExtension em formato base64:\n$fileContent";
}

// Instrui o Claude com um prompt específico para extratos bancários brasileiros
$prompt = "Você é um assistente especializado em análise de extratos bancários brasileiros.

Analise o extrato bancário a seguir e identifique todas as transações.
Para cada transação, extraia as seguintes informações:
1. A data no formato YYYY-MM-DD (converta datas no formato DD/MM/YYYY ou MM/DD/YYYY para YYYY-MM-DD)
2. A descrição completa da transação
3. O valor em reais (use valor positivo para receitas/créditos e negativo para despesas/débitos)
4. Uma sugestão de categoria adequada para a transação (ex: Alimentação, Transporte, Salário, etc.)

IMPORTANTE: Se o valor estiver no formato brasileiro (usando vírgula como separador decimal), converta-o para o formato com ponto decimal.

Retorne APENAS um objeto JSON válido com um array 'transactions' contendo objetos com as propriedades:
- date (string no formato YYYY-MM-DD)
- description (string)
- amount (número - positivo para receitas, negativo para despesas)
- category (string)
- notes (string opcional)

Exemplo de resposta esperada:
```json
{
  \"transactions\": [
    {
      \"date\": \"2023-05-15\",
      \"description\": \"PAGAMENTO SALÁRIO\",
      \"amount\": 3500.00,
      \"category\": \"Salário\",
      \"notes\": \"Depósito mensal\"
    },
    {
      \"date\": \"2023-05-20\",
      \"description\": \"NETFLIX\",
      \"amount\": -39.90,
      \"category\": \"Entretenimento\",
      \"notes\": \"Assinatura mensal\"
    }
  ]
}
```

Conteúdo do extrato:
$fileContent";

// Log da requisição (descomente se precisar de debug)
file_put_contents(__DIR__ . '/anthropic_log.txt', date('Y-m-d H:i:s') . " - Arquivo: {$file['name']}\n", FILE_APPEND);

try {
    // Chama a API do Claude
    $response = callClaudeAPI($apiKey, $prompt);
    file_put_contents(__DIR__ . '/anthropic_log.txt', date('Y-m-d H:i:s') . " - Resposta API: " . substr(json_encode($response), 0, 500) . "\n", FILE_APPEND);
    
    // Extrai as transações da resposta
    $transactions = extractTransactionsFromClaudeResponse($response);
    file_put_contents(__DIR__ . '/anthropic_log.txt', date('Y-m-d H:i:s') . " - Transações extraídas: " . json_encode($transactions) . "\n", FILE_APPEND);
    
    // Retorna o resultado
    header('Content-Type: application/json');
    echo json_encode(['transactions' => $transactions]);
} catch (\Exception $e) {
    $errorMessage = 'Erro ao processar o extrato: ' . $e->getMessage();
    file_put_contents(__DIR__ . '/anthropic_error.txt', date('Y-m-d H:i:s') . " - $errorMessage\n", FILE_APPEND);
    
    // Usa os dados do arquivo para tentar extrair transações manualmente
    $transacoesFallback = [];
    
    // Para CSV, tenta extrair algumas linhas como fallback
    if ($fileExtension === 'csv') {
        // Abre o arquivo original para leitura
        if (($handle = fopen($file['tmp_name'], "r")) !== FALSE) {
            $row = 0;
            // Pula o cabeçalho presumido
            if (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // Prossegue com as demais linhas
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE && $row < 10) {
                    if (count($data) >= 3) {
                        $transacoesFallback[] = [
                            'date' => date('Y-m-d'),
                            'description' => isset($data[1]) ? $data[1] : 'Descrição não encontrada',
                            'amount' => isset($data[2]) ? (float)str_replace(',', '.', $data[2]) : 0,
                            'category' => 'Não classificado'
                        ];
                    }
                    $row++;
                }
            }
            fclose($handle);
        }
    }
    
    // Retorna erro, mas ainda assim mantém o formato esperado
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => $errorMessage,
        'transactions' => $transacoesFallback // Retorna array vazio ou com transações básicas como fallback
    ]);
}

/**
 * Função para chamar a API do GPT-4 via OpenAI
 */
function callClaudeAPI($apiKey, $prompt) {
    // URL da API da OpenAI para GPT-4
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey, // Formato para OpenAI
        'OpenAI-Beta: assistants=v1' // Opcional: para recursos mais recentes
    ]);
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => 'gpt-3.5-turbo', // Usando GPT-3.5 que está disponível para todas as contas
        'max_tokens' => 4000,
        'temperature' => 0.2, // Baixa temperatura para respostas mais determinísticas
        'messages' => [
            [
                'role' => 'system',
                'content' => 'Você é um analisador de extratos bancários especializado. Sua tarefa é extrair transações de extratos bancários brasileiros e retorná-las em formato JSON.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ]
    ]));
    
    // Configurações adicionais para CURL
    curl_setopt($ch, CURLOPT_TIMEOUT, 60); // timeout de 60 segundos
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        throw new \Exception("Erro CURL: " . curl_error($ch));
    }
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpCode >= 400) {
        throw new \Exception("Erro HTTP $httpCode: $response");
    }
    
    curl_close($ch);
    
    // Log para debug
    file_put_contents(__DIR__ . '/anthropic_log.txt', date('Y-m-d H:i:s') . " - Resposta bruta: " . substr($response, 0, 1000) . "\n", FILE_APPEND);
    
    // Converte resposta da OpenAI para formato compatível com nosso código
    $responseData = json_decode($response, true);
    
    if (isset($responseData['choices']) && isset($responseData['choices'][0]) && isset($responseData['choices'][0]['message']) && isset($responseData['choices'][0]['message']['content'])) {
        // Converte formato da OpenAI para o formato esperado pelo nosso código
        $content = $responseData['choices'][0]['message']['content'];
        
        return [
            'content' => [
                [
                    'type' => 'text',
                    'text' => $content
                ]
            ]
        ];
    } else {
        throw new \Exception("Formato de resposta inesperado da OpenAI: " . substr($response, 0, 500));
    }
}

/**
 * Função para extrair as transações da resposta do Claude
 */
function extractTransactionsFromClaudeResponse($response) {
    file_put_contents(__DIR__ . '/anthropic_log.txt', date('Y-m-d H:i:s') . " - Início do processamento da resposta\n", FILE_APPEND);
    
    if (!isset($response['content']) || !is_array($response['content'])) {
        $errorMsg = "Formato de resposta inesperado: " . json_encode($response);
        file_put_contents(__DIR__ . '/anthropic_log.txt', date('Y-m-d H:i:s') . " - Erro: $errorMsg\n", FILE_APPEND);
        throw new \Exception($errorMsg);
    }
    
    foreach ($response['content'] as $content) {
        if ($content['type'] === 'text') {
            // Tenta extrair o JSON da resposta
            $text = $content['text'];
            file_put_contents(__DIR__ . '/anthropic_log.txt', date('Y-m-d H:i:s') . " - Texto: " . substr($text, 0, 500) . "\n", FILE_APPEND);
            
            // Procura por JSON formatado entre ```json e ```
            if (preg_match('/```json\s*(.*?)\s*```/s', $text, $matches)) {
                $jsonStr = $matches[1];
                file_put_contents(__DIR__ . '/anthropic_log.txt', date('Y-m-d H:i:s') . " - JSON encontrado em bloco JSON\n", FILE_APPEND);
            } 
            // Ou procura por qualquer bloco de código
            elseif (preg_match('/```\s*(.*?)\s*```/s', $text, $matches)) {
                $jsonStr = $matches[1];
                file_put_contents(__DIR__ . '/anthropic_log.txt', date('Y-m-d H:i:s') . " - JSON encontrado em bloco de código\n", FILE_APPEND);
            }
            // Caso contrário, usa o texto completo
            else {
                $jsonStr = $text;
                file_put_contents(__DIR__ . '/anthropic_log.txt', date('Y-m-d H:i:s') . " - Usando texto completo como JSON\n", FILE_APPEND);
            }
            
            // Remove qualquer texto antes ou depois do JSON
            $jsonStr = preg_replace('/^[^{]*/', '', $jsonStr);
            $jsonStr = preg_replace('/[^}]*$/', '', $jsonStr);
            
            // Tenta decodificar o JSON
            $data = json_decode($jsonStr, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                file_put_contents(__DIR__ . '/anthropic_log.txt', date('Y-m-d H:i:s') . " - Erro ao decodificar JSON: " . json_last_error_msg() . "\n", FILE_APPEND);
                file_put_contents(__DIR__ . '/anthropic_log.txt', date('Y-m-d H:i:s') . " - JSON String: " . substr($jsonStr, 0, 500) . "\n", FILE_APPEND);
                
                // Se falhar, tenta limpar mais e tentar novamente
                $jsonStr = preg_replace('/[^{}[\],:"0-9.\-a-zA-Z_\s]/', '', $jsonStr);
                $data = json_decode($jsonStr, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $errorMsg = "Erro ao decodificar JSON após limpeza: " . json_last_error_msg();
                    file_put_contents(__DIR__ . '/anthropic_log.txt', date('Y-m-d H:i:s') . " - $errorMsg\n", FILE_APPEND);
                    throw new \Exception($errorMsg);
                }
            }
            
            if (isset($data['transactions']) && is_array($data['transactions'])) {
                file_put_contents(__DIR__ . '/anthropic_log.txt', date('Y-m-d H:i:s') . " - Transações encontradas: " . count($data['transactions']) . "\n", FILE_APPEND);
                return $data['transactions'];
            }
        }
    }
    
    $errorMsg = "Formato de transações não encontrado na resposta";
    file_put_contents(__DIR__ . '/anthropic_log.txt', date('Y-m-d H:i:s') . " - Erro: $errorMsg\n", FILE_APPEND);
    throw new \Exception($errorMsg);
}
