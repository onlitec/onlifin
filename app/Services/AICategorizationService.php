<?php

namespace App\Services;

use App\Models\Category;
use App\Services\AIConfigService;
use App\Services\TransferDetectionService;
use App\Services\TransferProcessingService;
use App\Services\CategoryTypeService;
use App\Services\AIUsageMonitorService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AICategorizationService
{
    private AIConfigService $aiConfigService;
    private AIUsageMonitorService $usageMonitor;

    public function __construct()
    {
        $this->aiConfigService = new AIConfigService();
        $this->usageMonitor = new AIUsageMonitorService();
    }

    /**
     * Categoriza transações usando IA e detecta transferências
     */
    public function categorizeTransactions(array $transactions, int $currentAccountId = null): array
    {
        Log::info('Iniciando categorização de transações', [
            'transactions_count' => count($transactions),
            'user_id' => auth()->id()
        ]);

        if (!$this->aiConfigService->isAIConfigured()) {
            Log::warning('IA não configurada, usando categorização padrão', [
                'user_id' => auth()->id()
            ]);
            return $this->fallbackCategorization($transactions);
        }

        $config = $this->aiConfigService->getAIConfig();
        Log::info('Configuração de IA encontrada', [
            'provider' => $config['provider'],
            'model' => $config['model'],
            'user_id' => auth()->id()
        ]);

        try {
            // 1. DETECÇÃO DE TRANSFERÊNCIAS
            $transferDetectionService = new TransferDetectionService();
            $transactionsWithTransfers = $transferDetectionService->detectAndProcessTransfers($transactions, $currentAccountId);

            Log::info('Detecção de transferências concluída', [
                'transactions_count' => count($transactionsWithTransfers),
                'user_id' => auth()->id()
            ]);

            // 2. CATEGORIZAÇÃO NORMAL PARA TRANSAÇÕES NÃO-TRANSFERÊNCIA
            $nonTransferTransactions = array_filter($transactionsWithTransfers, function($t) {
                return !isset($t['is_transfer']) || !$t['is_transfer'];
            });

            if (!empty($nonTransferTransactions)) {
                // Buscar categorias existentes do usuário
                $existingCategories = $this->getUserCategories();
                Log::info('Categorias existentes encontradas', [
                    'categories_count' => count($existingCategories),
                    'user_id' => auth()->id()
                ]);

                // Preparar prompt para IA
                $prompt = $this->buildCategorizationPrompt($nonTransferTransactions, $existingCategories);
                Log::info('Prompt preparado para IA', [
                    'prompt_length' => strlen($prompt),
                    'user_id' => auth()->id()
                ]);

                // Fazer chamada para IA
                $aiResponse = $this->callAI($prompt);
                Log::info('Resposta da IA recebida', [
                    'response_count' => count($aiResponse),
                    'user_id' => auth()->id()
                ]);

                // Processar resposta da IA
                $categorizedNonTransfers = $this->processAIResponse($aiResponse, $nonTransferTransactions);

                // Mesclar transações categorizadas com transferências
                $finalTransactions = $this->mergeTransactionsWithTransfers($transactionsWithTransfers, $categorizedNonTransfers);
            } else {
                $finalTransactions = $transactionsWithTransfers;
            }

            // 3. PROCESSAR TRANSFERÊNCIAS DETECTADAS
            if ($currentAccountId) {
                $transferProcessingService = new TransferProcessingService();
                $finalTransactions = $transferProcessingService->processTransfers($finalTransactions, $currentAccountId);

                // Obter estatísticas de transferências
                $transferStats = $transferProcessingService->getTransferStats($finalTransactions);
                Log::info('Estatísticas de transferências', array_merge($transferStats, ['user_id' => auth()->id()]));
            }

            Log::info('Categorização e processamento de transferências concluídos', [
                'transactions_count' => count($transactions),
                'final_count' => count($finalTransactions),
                'user_id' => auth()->id()
            ]);

            return $finalTransactions;

        } catch (\Exception $e) {
            Log::error('Erro na categorização por IA', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
                'transactions_count' => count($transactions)
            ]);

            // Fallback para categorização básica garantindo que TODAS as transações tenham categoria
            $fallbackTransactions = $this->fallbackCategorization($transactions);

            Log::info('Fallback aplicado com sucesso', [
                'transactions_count' => count($fallbackTransactions),
                'user_id' => auth()->id()
            ]);

            return $fallbackTransactions;
        }
    }

    /**
     * Constrói o prompt para categorização
     */
    private function buildCategorizationPrompt(array $transactions, array $existingCategories): string
    {
        $categoriesJson = json_encode($existingCategories, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $transactionsJson = json_encode($transactions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return "
# SISTEMA DE CATEGORIZAÇÃO INTELIGENTE DE TRANSAÇÕES

Você é um especialista em categorização de transações financeiras. Sua tarefa é analisar as transações fornecidas e categorizá-las de forma inteligente.

## CATEGORIAS EXISTENTES DO USUÁRIO
{$categoriesJson}

## TRANSAÇÕES PARA CATEGORIZAR
{$transactionsJson}

## INSTRUÇÕES

1. **ANÁLISE DA DESCRIÇÃO**: Analise cuidadosamente a descrição de cada transação para identificar:
   - Tipo de estabelecimento (padaria, posto, farmácia, etc.)
   - Natureza da transação (compra, pagamento, recebimento, etc.)
   - Contexto financeiro (alimentação, transporte, saúde, etc.)

2. **CATEGORIZAÇÃO INTELIGENTE**:
   - **PRIORIDADE 1**: Use categorias existentes quando apropriado
   - **PRIORIDADE 2**: Crie novas categorias quando necessário
   - **PRIORIDADE 3**: Mantenha consistência com padrões financeiros

3. **REGRAS DE CATEGORIZAÇÃO OBRIGATÓRIAS**:
   - **TODA TRANSAÇÃO DEVE TER UMA CATEGORIA**
   - **IMPORTANTE**: Categorize baseado na NATUREZA do gasto/recebimento, NÃO no tipo da transação

   **CATEGORIAS PARA DESPESAS (expense)**:
   - Padarias, restaurantes, supermercados, mercados → Alimentação
   - Postos de combustível, Uber, taxi, estacionamento → Transporte
   - Farmácias, hospitais, clínicas, médicos → Saúde
   - Escolas, cursos, universidades, livros → Educação
   - Aluguel, condomínio, luz, água, internet → Casa
   - Cinema, Netflix, viagens, restaurantes → Lazer
   - Roupas, calçados, shopping, cosméticos → Vestuário
   - Eletrônicos, software, celular, computador → Tecnologia
   - PIX, TED, DOC, transferências → Transferências
   - Bancos, cartões, taxas → Serviços Financeiros
   - Outros gastos não identificados → Outros Gastos

   **CATEGORIAS PARA RECEITAS (income)**:
   - Salários, ordenados, vencimentos → Salário
   - Freelances, consultorias, trabalhos → Freelance
   - Vendas, comissões, negócios → Vendas
   - Investimentos, dividendos, juros → Investimentos
   - Outros recebimentos não identificados → Outros Recebimentos

   **REGRA CRÍTICA**:
   - Se a transação é EXPENSE, use APENAS categorias de despesa
   - Se a transação é INCOME, use APENAS categorias de receita
   - NUNCA misture tipos (ex: não use Salário para expense)

4. **CRIAÇÃO DE NOVAS CATEGORIAS**:
   - Use nomes claros e descritivos
   - Evite categorias muito específicas
   - Mantenha padrão de nomenclatura (primeira letra maiúscula)
   - Exemplos: 'Alimentação', 'Transporte', 'Saúde', 'Educação'

5. **TIPOS DE TRANSAÇÃO**:
   - 'expense' para gastos/despesas
   - 'income' para recebimentos/receitas

## FORMATO DE RESPOSTA

**IMPORTANTE**: TODA transação DEVE ter uma categoria sugerida. Nunca deixe `suggested_category_name` vazio.

Responda APENAS com um array JSON contendo objetos com os seguintes campos:
- `transaction_index`: Índice da transação no array original (0, 1, 2, ...)
- `suggested_category_name`: Nome da categoria sugerida (OBRIGATÓRIO - nunca vazio)
- `category_exists`: true se a categoria já existe, false se é nova
- `existing_category_id`: ID da categoria existente (null se é nova)
- `confidence`: Nível de confiança (0.0 a 1.0)
- `reasoning`: Breve explicação da categorização

Exemplo de resposta:
```json
[
  {
    \"transaction_index\": 0,
    \"suggested_category_name\": \"Alimentação\",
    \"category_exists\": true,
    \"existing_category_id\": 5,
    \"confidence\": 0.95,
    \"reasoning\": \"Padaria é claramente relacionada a alimentação\"
  },
  {
    \"transaction_index\": 1,
    \"suggested_category_name\": \"Transporte\",
    \"category_exists\": false,
    \"existing_category_id\": null,
    \"confidence\": 0.90,
    \"reasoning\": \"Posto de combustível indica gastos com transporte\"
  }
]
```

IMPORTANTE: Responda APENAS com o JSON, sem texto adicional.
";
    }

    /**
     * Faz chamada para IA com sistema de fallback automático
     */
    private function callAI(string $prompt): array
    {
        $config = $this->aiConfigService->getAIConfig();
        $provider = strtolower($config['provider']);

        Log::info('Fazendo chamada para IA', [
            'provider' => $provider,
            'model' => $config['model'],
            'user_id' => auth()->id()
        ]);

        $maxRetries = 2;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
                if ($provider === 'google' || $provider === 'gemini') {
                    return $this->callGoogleGemini($config, $prompt);
                } elseif ($provider === 'groq') {
                    return $this->callGroqWithFallback($config, $prompt);
                } else {
                    return $this->callOpenAICompatible($config, $prompt);
                }
            } catch (\Exception $e) {
                $attempt++;
                $isJsonError = strpos($e->getMessage(), 'JSON') !== false;

                Log::error('Erro na chamada principal da IA', [
                    'provider' => $provider,
                    'error' => $e->getMessage(),
                    'attempt' => $attempt,
                    'max_retries' => $maxRetries,
                    'is_json_error' => $isJsonError,
                    'user_id' => auth()->id()
                ]);

                // Se for erro de JSON e ainda há tentativas, retry
                if ($isJsonError && $attempt < $maxRetries) {
                    Log::info('Tentando novamente devido a erro de JSON', [
                        'attempt' => $attempt + 1,
                        'provider' => $provider
                    ]);
                    sleep(1); // Pequena pausa antes do retry
                    continue;
                }

                // Se esgotaram as tentativas ou não é erro de JSON, tentar fallback
                return $this->tryFallbackProviders($prompt, $provider);
            }
        }
    }

    /**
     * Chamada para Google Gemini
     */
    private function callGoogleGemini(array $config, string $prompt): array
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json'
        ])->timeout(60)->post($this->getApiEndpoint($config['provider']) . '?key=' . $config['api_key'], [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $prompt
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.1,
                'maxOutputTokens' => 4000
            ]
        ]);

        if (!$response->successful()) {
            Log::error('Erro na chamada do Google Gemini', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \Exception('Erro na chamada da IA: ' . $response->body());
        }

        $responseData = $response->json();

        // Extrair conteúdo da resposta do Gemini
        if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
            $content = $responseData['candidates'][0]['content']['parts'][0]['text'];
        } else {
            throw new \Exception('Formato de resposta do Gemini não reconhecido: ' . json_encode($responseData));
        }

        return $this->extractJsonFromResponse($content);
    }

    /**
     * Chamada para Groq
     */
    private function callGroq(array $config, string $prompt): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $config['api_key'],
            'Content-Type' => 'application/json'
        ])->timeout(60)->post('https://api.groq.com/openai/v1/chat/completions', [
            'model' => $config['model'],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Você é um assistente especializado em categorização de transações financeiras.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.1,
            'max_tokens' => 4000
        ]);

        if (!$response->successful()) {
            Log::error('Erro na chamada do Groq', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \Exception('Erro na chamada da IA: ' . $response->body());
        }

        $content = $response->json('choices.0.message.content');
        return $this->extractJsonFromResponse($content);
    }

    /**
     * Chamada para Groq com sistema de fallback automático
     */
    private function callGroqWithFallback(array $config, string $prompt): array
    {
        // Obter todas as configurações Groq disponíveis
        $groqConfigs = $this->getGroqConfigurations();

        Log::info('Iniciando chamada Groq com fallback', [
            'total_groq_configs' => count($groqConfigs),
            'primary_model' => $config['model'],
            'user_id' => auth()->id()
        ]);

        // Tentar primeiro com a configuração principal
        try {
            $result = $this->callGroq($config, $prompt);

            // Registrar sucesso
            $this->usageMonitor->recordUsage('groq', $config['model'], 'success');

            return $result;
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $isRateLimit = $this->isRateLimitError($errorMessage);

            // Registrar erro
            $this->usageMonitor->recordUsage('groq', $config['model'], 'error', $errorMessage);

            Log::warning('Erro na configuração principal do Groq', [
                'error' => $errorMessage,
                'is_rate_limit' => $isRateLimit,
                'model' => $config['model'],
                'user_id' => auth()->id()
            ]);

            // Se for erro de limite de taxa, marcar como problemático e tentar fallback
            if ($isRateLimit) {
                $this->usageMonitor->markProviderAsProblematic('groq', $config['model'], 30);

                if (count($groqConfigs) > 1) {
                    return $this->tryGroqFallback($groqConfigs, $prompt, $config['model']);
                }
            }

            // Se não for erro de limite ou não houver fallback, relançar exceção
            throw $e;
        }
    }

    /**
     * Tenta usar configurações Groq alternativas
     */
    private function tryGroqFallback(array $groqConfigs, string $prompt, string $primaryModel): array
    {
        foreach ($groqConfigs as $index => $fallbackConfig) {
            // Pular a configuração principal (já tentada)
            if ($fallbackConfig['model'] === $primaryModel) {
                continue;
            }

            try {
                Log::info('Tentando fallback Groq', [
                    'fallback_index' => $index,
                    'fallback_model' => $fallbackConfig['model'],
                    'user_id' => auth()->id()
                ]);

                $result = $this->callGroq($fallbackConfig, $prompt);

                // Registrar sucesso do fallback
                $this->usageMonitor->recordUsage('groq', $fallbackConfig['model'], 'success');

                Log::info('Fallback Groq bem-sucedido', [
                    'fallback_model' => $fallbackConfig['model'],
                    'user_id' => auth()->id()
                ]);

                return $result;
            } catch (\Exception $e) {
                // Registrar erro do fallback
                $this->usageMonitor->recordUsage('groq', $fallbackConfig['model'], 'error', $e->getMessage());

                Log::warning('Fallback Groq falhou', [
                    'fallback_model' => $fallbackConfig['model'],
                    'error' => $e->getMessage(),
                    'user_id' => auth()->id()
                ]);
                continue;
            }
        }

        throw new \Exception('Todos os provedores Groq configurados atingiram o limite ou falharam');
    }

    /**
     * Obtém todas as configurações Groq disponíveis
     */
    private function getGroqConfigurations(): array
    {
        $configs = [];

        // 1. Configuração principal (config/ai.php ou OpenRouterConfig)
        $mainConfig = $this->aiConfigService->getAIConfig('groq');
        if ($mainConfig && !empty($mainConfig['api_key'])) {
            $configs[] = $mainConfig;
        }

        // 2. Configurações múltiplas (ModelApiKey)
        $multipleConfigs = \App\Models\ModelApiKey::where('provider', 'groq')
            ->where('is_active', true)
            ->whereNotNull('api_token')
            ->where('api_token', '!=', '')
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($multipleConfigs as $config) {
            $configs[] = [
                'provider' => 'groq',
                'model' => $config->model,
                'api_key' => $config->api_token,
                'system_prompt' => $config->system_prompt,
                'chat_prompt' => $config->chat_prompt,
                'import_prompt' => $config->import_prompt,
                'source' => 'multiple_config',
                'config_id' => $config->id
            ];
        }

        Log::info('Configurações Groq encontradas', [
            'total_configs' => count($configs),
            'main_config' => !empty($mainConfig),
            'multiple_configs' => $multipleConfigs->count(),
            'user_id' => auth()->id()
        ]);

        return $configs;
    }

    /**
     * Verifica se o erro é relacionado a limite de taxa
     */
    private function isRateLimitError(string $errorMessage): bool
    {
        $rateLimitIndicators = [
            'rate limit',
            'rate_limit',
            'too many requests',
            '429',
            'quota exceeded',
            'limit exceeded',
            'overloaded',
            'capacity',
            'throttled'
        ];

        $errorLower = strtolower($errorMessage);

        foreach ($rateLimitIndicators as $indicator) {
            if (strpos($errorLower, $indicator) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Tenta fallback para outros provedores configurados
     */
    private function tryFallbackProviders(string $prompt, string $failedProvider): array
    {
        Log::info('Tentando fallback para outros provedores', [
            'failed_provider' => $failedProvider,
            'user_id' => auth()->id()
        ]);

        // Obter outros provedores configurados
        $otherProviders = $this->getOtherConfiguredProviders($failedProvider);

        foreach ($otherProviders as $providerConfig) {
            try {
                Log::info('Tentando provedor de fallback', [
                    'provider' => $providerConfig['provider'],
                    'model' => $providerConfig['model'],
                    'user_id' => auth()->id()
                ]);

                $provider = strtolower($providerConfig['provider']);

                if ($provider === 'google' || $provider === 'gemini') {
                    return $this->callGoogleGemini($providerConfig, $prompt);
                } elseif ($provider === 'groq') {
                    return $this->callGroq($providerConfig, $prompt);
                } else {
                    return $this->callOpenAICompatible($providerConfig, $prompt);
                }
            } catch (\Exception $e) {
                Log::warning('Provedor de fallback falhou', [
                    'provider' => $providerConfig['provider'],
                    'error' => $e->getMessage(),
                    'user_id' => auth()->id()
                ]);
                continue;
            }
        }

        throw new \Exception('Todos os provedores de IA configurados falharam ou atingiram limites');
    }

    /**
     * Obtém outros provedores configurados além do que falhou
     */
    private function getOtherConfiguredProviders(string $failedProvider): array
    {
        $providers = [];

        // Verificar configurações múltiplas de outros provedores
        $otherConfigs = \App\Models\ModelApiKey::where('provider', '!=', $failedProvider)
            ->where('is_active', true)
            ->whereNotNull('api_token')
            ->where('api_token', '!=', '')
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($otherConfigs as $config) {
            $providers[] = [
                'provider' => $config->provider,
                'model' => $config->model,
                'api_key' => $config->api_token,
                'system_prompt' => $config->system_prompt,
                'chat_prompt' => $config->chat_prompt,
                'import_prompt' => $config->import_prompt
            ];
        }

        return $providers;
    }

    /**
     * Chamada para APIs compatíveis com OpenAI
     */
    private function callOpenAICompatible(array $config, string $prompt): array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$config['api_key']}",
            'Content-Type' => 'application/json'
        ])->timeout(60)->post($this->getApiEndpoint($config['provider']), [
            'model' => $config['model'],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Você é um especialista em categorização de transações financeiras. Responda sempre em JSON válido.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.1,
            'max_tokens' => 4000
        ]);

        if (!$response->successful()) {
            throw new \Exception('Erro na chamada da IA: ' . $response->body());
        }

        $responseData = $response->json();

        // Extrair conteúdo da resposta OpenAI
        if (isset($responseData['choices'][0]['message']['content'])) {
            $content = $responseData['choices'][0]['message']['content'];
        } else {
            throw new \Exception('Formato de resposta da IA não reconhecido');
        }

        return $this->extractJsonFromResponse($content);
    }

    /**
     * Extrai JSON da resposta da IA
     */
    private function extractJsonFromResponse(string $content): array
    {
        Log::info('Resposta bruta da IA', ['content' => substr($content, 0, 1000)]);

        // Limpar a resposta removendo caracteres desnecessários
        $cleanContent = trim($content);

        // Método 1: Tentar encontrar JSON em blocos de código markdown (mais robusto)
        if (preg_match('/```json\s*(.*?)\s*```/s', $cleanContent, $matches)) {
            $jsonContent = trim($matches[1]);

            // Limpar caracteres de escape desnecessários
            $jsonContent = str_replace('\\"', '"', $jsonContent);
            $jsonContent = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $jsonContent);

            Log::info('JSON encontrado em bloco markdown', ['json_preview' => substr($jsonContent, 0, 200)]);

            $decoded = json_decode($jsonContent, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                Log::info('JSON extraído de bloco markdown com sucesso', ['decoded_count' => count($decoded)]);
                return $decoded;
            } else {
                Log::warning('Erro ao decodificar JSON do bloco markdown', [
                    'json_error' => json_last_error_msg(),
                    'json_content' => substr($jsonContent, 0, 500)
                ]);
            }
        }

        // Método 2: Buscar JSON completo entre colchetes (mais robusto)
        if (preg_match('/\[\s*\{.*?\}\s*\]/s', $cleanContent, $matches)) {
            $jsonContent = $matches[0];

            // Limpar caracteres problemáticos
            $jsonContent = str_replace('\\"', '"', $jsonContent);
            $jsonContent = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $jsonContent);

            Log::info('JSON encontrado por padrão completo', ['json_preview' => substr($jsonContent, 0, 200)]);

            $decoded = json_decode($jsonContent, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                Log::info('JSON extraído por padrão completo com sucesso', ['decoded_count' => count($decoded)]);
                return $decoded;
            }
        }

        // Método 3: Extrair pela posição dos colchetes (método original melhorado)
        $jsonStart = strpos($cleanContent, '[');
        $jsonEnd = strrpos($cleanContent, ']');

        if ($jsonStart !== false && $jsonEnd !== false && $jsonEnd > $jsonStart) {
            $jsonContent = substr($cleanContent, $jsonStart, $jsonEnd - $jsonStart + 1);

            // Limpar caracteres problemáticos mas preservar estrutura
            $jsonContent = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $jsonContent);

            Log::info('JSON extraído por posição', ['json_preview' => substr($jsonContent, 0, 200)]);

            $decoded = json_decode($jsonContent, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                Log::info('JSON extraído por posição com sucesso', ['decoded_count' => count($decoded)]);
                return $decoded;
            } else {
                Log::error('Erro ao decodificar JSON por posição', [
                    'json_error' => json_last_error_msg(),
                    'json_content' => substr($jsonContent, 0, 500)
                ]);
            }
        }

        // Método 4: Tentar reparar JSON comum (aspas escapadas)
        $repairedContent = str_replace('\\"', '"', $cleanContent);
        if (preg_match('/\[.*?\]/s', $repairedContent, $matches)) {
            $jsonContent = $matches[0];
            $decoded = json_decode($jsonContent, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                Log::info('JSON reparado e extraído com sucesso', ['decoded_count' => count($decoded)]);
                return $decoded;
            }
        }

        // Log detalhado do erro para debug
        Log::error('Falha completa na extração de JSON', [
            'content_length' => strlen($cleanContent),
            'content_preview' => substr($cleanContent, 0, 1000),
            'json_start_pos' => $jsonStart,
            'json_end_pos' => $jsonEnd
        ]);

        // Método 5: Fallback para resposta estruturada simples
        if (preg_match('/categoria[:\s]*([^\n\r,]+)/i', $cleanContent, $matches)) {
            Log::warning('Usando fallback de categoria simples', [
                'categoria_encontrada' => trim($matches[1])
            ]);

            return [
                [
                    'transaction_index' => 0,
                    'suggested_category_name' => trim($matches[1]),
                    'confidence' => 0.7,
                    'reasoning' => 'Categoria extraída por fallback devido a erro de JSON'
                ]
            ];
        }

        throw new \Exception('Resposta da IA não contém JSON válido: ' . substr($cleanContent, 0, 500));
    }

    /**
     * Processa resposta da IA
     */
    private function processAIResponse(array $aiResponse, array $originalTransactions): array
    {
        $categorizedTransactions = [];

        foreach ($originalTransactions as $index => $transaction) {
            // Buscar categorização da IA para esta transação
            $aiCategorization = collect($aiResponse)->firstWhere('transaction_index', $index);

            if ($aiCategorization && !empty($aiCategorization['suggested_category_name'])) {
                $suggestedCategoryName = $aiCategorization['suggested_category_name'];

                // Validar se a categoria é apropriada para o tipo da transação
                $isValidCategory = CategoryTypeService::validateCategoryForTransaction($suggestedCategoryName, $transaction['type']);

                if (!$isValidCategory) {
                    Log::warning('Categoria sugerida pela IA não é apropriada para o tipo da transação', [
                        'suggested_category' => $suggestedCategoryName,
                        'transaction_type' => $transaction['type'],
                        'description' => $transaction['description']
                    ]);

                    // Usar categoria padrão apropriada
                    $suggestedCategoryName = CategoryTypeService::suggestCategoryForTransaction($transaction['type']);
                }

                $transaction['suggested_category_name'] = $suggestedCategoryName;
                $transaction['suggested_category_id'] = $aiCategorization['existing_category_id'];
                $transaction['category_confidence'] = $aiCategorization['confidence'] ?? 0.8;
                $transaction['ai_reasoning'] = $aiCategorization['reasoning'] ?? '';
                $transaction['is_new_category'] = !$aiCategorization['category_exists'];
            } else {
                // Fallback com categoria padrão baseada no tipo
                $defaultCategory = $this->getDefaultCategoryForType($transaction['type']);
                $transaction['suggested_category_name'] = $defaultCategory;
                $transaction['suggested_category_id'] = null;
                $transaction['category_confidence'] = 0.3;
                $transaction['ai_reasoning'] = 'Categoria padrão aplicada (IA não categorizou)';
                $transaction['is_new_category'] = true;

                Log::warning('IA não categorizou transação, aplicando categoria padrão', [
                    'transaction_index' => $index,
                    'description' => $transaction['description'],
                    'default_category' => $defaultCategory
                ]);
            }

            $categorizedTransactions[] = $transaction;
        }

        return $categorizedTransactions;
    }

    /**
     * Retorna categoria padrão baseada no tipo da transação
     */
    private function getDefaultCategoryForType(string $type): string
    {
        return $type === 'income' ? 'Outros Recebimentos' : 'Outros Gastos';
    }

    /**
     * Busca categorias existentes do usuário
     */
    private function getUserCategories(): array
    {
        return Category::where('user_id', auth()->id())
            ->select('id', 'name', 'type')
            ->get()
            ->toArray();
    }

    /**
     * Categorização de fallback quando IA não está disponível
     */
    private function fallbackCategorization(array $transactions): array
    {
        Log::info('Usando categorização de fallback (sem IA)', [
            'transactions_count' => count($transactions),
            'user_id' => auth()->id()
        ]);

        // Buscar categorias padrão do usuário
        $defaultCategories = $this->getUserCategories();

        foreach ($transactions as &$transaction) {
            // Aplicar categorização básica baseada em palavras-chave simples
            $categoryName = $this->getBasicCategoryFromDescription($transaction['description'], $transaction['type']);

            // Tentar encontrar categoria existente
            $existingCategory = collect($defaultCategories)->first(function($cat) use ($categoryName) {
                return strtolower($cat['name']) === strtolower($categoryName);
            });

            if ($existingCategory) {
                $transaction['suggested_category_name'] = $existingCategory['name'];
                $transaction['suggested_category_id'] = $existingCategory['id'];
                $transaction['is_new_category'] = false;
                $transaction['category_confidence'] = 0.6; // Maior confiança para categorias existentes
            } else {
                $transaction['suggested_category_name'] = $categoryName;
                $transaction['suggested_category_id'] = null;
                $transaction['is_new_category'] = true;
                $transaction['category_confidence'] = 0.4;
            }

            $transaction['ai_reasoning'] = 'Categorização básica (fallback)';

            Log::info('Transação categorizada no fallback', [
                'description' => substr($transaction['description'], 0, 50),
                'category' => $transaction['suggested_category_name'],
                'confidence' => $transaction['category_confidence']
            ]);
        }

        return $transactions;
    }

    /**
     * Categorização básica baseada em palavras-chave simples
     */
    private function getBasicCategoryFromDescription(string $description, string $type): string
    {
        $description = strtolower($description);

        if ($type === 'income') {
            if (strpos($description, 'salario') !== false || strpos($description, 'salário') !== false) {
                return 'Salário';
            }
            if (strpos($description, 'freelance') !== false) {
                return 'Freelance';
            }
            if (strpos($description, 'venda') !== false) {
                return 'Vendas';
            }
            return 'Outros Recebimentos';
        } else {
            // Categorização básica para despesas
            if (strpos($description, 'padaria') !== false || strpos($description, 'restaurante') !== false ||
                strpos($description, 'supermercado') !== false || strpos($description, 'mercado') !== false) {
                return 'Alimentação';
            }
            if (strpos($description, 'posto') !== false || strpos($description, 'gasolina') !== false ||
                strpos($description, 'uber') !== false || strpos($description, 'taxi') !== false) {
                return 'Transporte';
            }
            if (strpos($description, 'farmacia') !== false || strpos($description, 'farmácia') !== false ||
                strpos($description, 'hospital') !== false || strpos($description, 'clinica') !== false) {
                return 'Saúde';
            }
            if (strpos($description, 'netflix') !== false || strpos($description, 'cinema') !== false ||
                strpos($description, 'viagem') !== false) {
                return 'Lazer';
            }
            if (strpos($description, 'pix') !== false || strpos($description, 'ted') !== false ||
                strpos($description, 'transferencia') !== false || strpos($description, 'transferência') !== false) {
                return 'Transferências';
            }
            return 'Outros Gastos';
        }
    }

    /**
     * Mescla transações categorizadas com transferências
     */
    private function mergeTransactionsWithTransfers(array $transactionsWithTransfers, array $categorizedNonTransfers): array
    {
        $mergedTransactions = [];
        $categorizedIndex = 0;

        foreach ($transactionsWithTransfers as $transaction) {
            if (isset($transaction['is_transfer']) && $transaction['is_transfer']) {
                // Manter transferência como está
                $mergedTransactions[] = $transaction;
            } else {
                // Usar versão categorizada
                if (isset($categorizedNonTransfers[$categorizedIndex])) {
                    $mergedTransactions[] = $categorizedNonTransfers[$categorizedIndex];
                    $categorizedIndex++;
                } else {
                    $mergedTransactions[] = $transaction;
                }
            }
        }

        return $mergedTransactions;
    }

    /**
     * Obtém endpoint da API baseado no provedor
     */
    private function getApiEndpoint(string $provider): string
    {
        switch (strtolower($provider)) {
            case 'openai':
                return 'https://api.openai.com/v1/chat/completions';
            case 'openrouter':
                return 'https://openrouter.ai/api/v1/chat/completions';
            case 'google':
            case 'gemini':
                return 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';
            default:
                return 'https://api.openai.com/v1/chat/completions';
        }
    }
}
