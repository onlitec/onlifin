<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Transaction;
use App\Services\AIConfigService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TransferDetectionService
{
    private AIConfigService $aiConfigService;

    public function __construct()
    {
        $this->aiConfigService = new AIConfigService();
    }

    /**
     * Detecta e processa transferências entre contas usando IA
     */
    public function detectAndProcessTransfers(array $transactions, int $currentAccountId): array
    {
        if (!$this->aiConfigService->isAIConfigured()) {
            Log::warning('IA não configurada para detecção de transferências');
            return $transactions;
        }

        try {
            // Buscar todas as contas do usuário
            $userAccounts = $this->getUserAccounts();
            
            if (count($userAccounts) < 2) {
                Log::info('Usuário tem menos de 2 contas, não há transferências possíveis');
                return $transactions;
            }

            // Filtrar transações que podem ser transferências
            $potentialTransfers = $this->filterPotentialTransfers($transactions);
            
            if (empty($potentialTransfers)) {
                Log::info('Nenhuma transação potencial de transferência encontrada');
                return $transactions;
            }

            // Analisar com IA
            $transferAnalysis = $this->analyzeTransfersWithAI($potentialTransfers, $userAccounts, $currentAccountId);
            
            // Aplicar resultados da análise
            return $this->applyTransferAnalysis($transactions, $transferAnalysis);
            
        } catch (\Exception $e) {
            Log::error('Erro na detecção de transferências', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);
            
            return $transactions;
        }
    }

    /**
     * Busca todas as contas do usuário
     */
    private function getUserAccounts(): array
    {
        return Account::where('user_id', auth()->id())
            ->where('active', true)
            ->select('id', 'name', 'type', 'description')
            ->get()
            ->toArray();
    }

    /**
     * Filtra transações que podem ser transferências
     */
    private function filterPotentialTransfers(array $transactions): array
    {
        $potentialTransfers = [];
        
        foreach ($transactions as $index => $transaction) {
            $description = strtolower($transaction['description']);
            
            // Palavras-chave que indicam transferência
            $transferKeywords = [
                'transferencia', 'transfer', 'ted', 'doc', 'pix',
                'conta corrente', 'conta poupanca', 'poupança',
                'banco', 'agencia', 'agência', 'saque', 'deposito',
                'debito automatico', 'credito', 'remessa'
            ];
            
            foreach ($transferKeywords as $keyword) {
                if (strpos($description, $keyword) !== false) {
                    $potentialTransfers[] = array_merge($transaction, ['original_index' => $index]);
                    break;
                }
            }
        }
        
        return $potentialTransfers;
    }

    /**
     * Analisa transferências com IA
     */
    private function analyzeTransfersWithAI(array $potentialTransfers, array $userAccounts, int $currentAccountId): array
    {
        $prompt = $this->buildTransferAnalysisPrompt($potentialTransfers, $userAccounts, $currentAccountId);
        
        Log::info('Analisando transferências com IA', [
            'potential_transfers_count' => count($potentialTransfers),
            'user_accounts_count' => count($userAccounts),
            'current_account_id' => $currentAccountId
        ]);

        $config = $this->aiConfigService->getAIConfig();
        $provider = strtolower($config['provider']);
        
        try {
            if ($provider === 'google' || $provider === 'gemini') {
                return $this->callGoogleGemini($config, $prompt);
            } elseif ($provider === 'groq') {
                return $this->callGroqWithFallback($config, $prompt);
            } else {
                return $this->callOpenAICompatible($config, $prompt);
            }
        } catch (\Exception $e) {
            Log::error('Erro na chamada principal da IA para transferências', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            // Tentar fallback para outros provedores configurados
            return $this->tryFallbackProviders($prompt, $provider);
        }
    }

    /**
     * Constrói prompt para análise de transferências
     */
    private function buildTransferAnalysisPrompt(array $potentialTransfers, array $userAccounts, int $currentAccountId): string
    {
        $accountsJson = json_encode($userAccounts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $transactionsJson = json_encode($potentialTransfers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        $currentAccount = collect($userAccounts)->firstWhere('id', $currentAccountId);
        $currentAccountName = $currentAccount['name'] ?? 'Conta Atual';

        return "
# SISTEMA DE DETECÇÃO DE TRANSFERÊNCIAS ENTRE CONTAS

Você é um especialista em análise de transações bancárias. Sua tarefa é identificar transferências entre contas do mesmo usuário.

## CONTAS DO USUÁRIO
{$accountsJson}

## CONTA ATUAL (IMPORTANDO EXTRATO)
ID: {$currentAccountId}
Nome: {$currentAccountName}

## TRANSAÇÕES POTENCIAIS DE TRANSFERÊNCIA
{$transactionsJson}

## INSTRUÇÕES DE ANÁLISE

1. **IDENTIFICAÇÃO DE TRANSFERÊNCIAS**:
   - Analise cada transação para determinar se é uma transferência entre contas do usuário
   - Procure por indicadores como: TED, DOC, PIX, transferência, saque, depósito
   - Identifique menções a outras contas do usuário na descrição

2. **DETERMINAÇÃO DE ORIGEM E DESTINO**:
   - Se a transação é RECEITA (income) na conta atual → Origem é outra conta, Destino é conta atual
   - Se a transação é DESPESA (expense) na conta atual → Origem é conta atual, Destino é outra conta

3. **CORRESPONDÊNCIA DE CONTAS**:
   - Tente identificar qual conta específica está envolvida na transferência
   - Use nomes, tipos de conta, ou outras pistas na descrição
   - Se não conseguir identificar a conta específica, indique como 'unknown'

4. **CRITÉRIOS DE VALIDAÇÃO**:
   - A transferência deve ser entre contas diferentes
   - Deve haver evidência clara na descrição da transação
   - O valor deve ser consistente com uma transferência

## FORMATO DE RESPOSTA

Responda APENAS com um array JSON contendo objetos com os seguintes campos:
- `original_index`: Índice da transação no array original
- `is_transfer`: true se é transferência, false caso contrário
- `confidence`: Nível de confiança (0.0 a 1.0)
- `origin_account_id`: ID da conta de origem (null se não identificada)
- `destination_account_id`: ID da conta de destino (null se não identificada)
- `origin_account_name`: Nome da conta de origem
- `destination_account_name`: Nome da conta de destino
- `reasoning`: Explicação da análise

Exemplo de resposta:
```json
[
  {
    \"original_index\": 0,
    \"is_transfer\": true,
    \"confidence\": 0.95,
    \"origin_account_id\": 2,
    \"destination_account_id\": 1,
    \"origin_account_name\": \"Conta Poupança\",
    \"destination_account_name\": \"Conta Corrente\",
    \"reasoning\": \"Transferência TED da poupança para conta corrente identificada na descrição\"
  }
]
```

IMPORTANTE: Responda APENAS com o JSON, sem texto adicional.
";
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
            throw new \Exception('Erro na chamada do Google Gemini: ' . $response->body());
        }

        $responseData = $response->json();
        
        if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
            $content = $responseData['candidates'][0]['content']['parts'][0]['text'];
        } else {
            throw new \Exception('Formato de resposta do Gemini não reconhecido');
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
                    'content' => 'Você é um especialista em análise de transferências bancárias. Responda sempre em JSON válido.'
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
            Log::error('Erro na chamada do Groq para transferências', [
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

        Log::info('Iniciando chamada Groq com fallback para transferências', [
            'total_groq_configs' => count($groqConfigs),
            'primary_model' => $config['model'],
            'user_id' => auth()->id()
        ]);

        // Tentar primeiro com a configuração principal
        try {
            return $this->callGroq($config, $prompt);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $isRateLimit = $this->isRateLimitError($errorMessage);

            Log::warning('Erro na configuração principal do Groq para transferências', [
                'error' => $errorMessage,
                'is_rate_limit' => $isRateLimit,
                'model' => $config['model'],
                'user_id' => auth()->id()
            ]);

            // Se for erro de limite de taxa, tentar fallback
            if ($isRateLimit && count($groqConfigs) > 1) {
                return $this->tryGroqFallback($groqConfigs, $prompt, $config['model']);
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
                Log::info('Tentando fallback Groq para transferências', [
                    'fallback_index' => $index,
                    'fallback_model' => $fallbackConfig['model'],
                    'user_id' => auth()->id()
                ]);

                $result = $this->callGroq($fallbackConfig, $prompt);

                Log::info('Fallback Groq bem-sucedido para transferências', [
                    'fallback_model' => $fallbackConfig['model'],
                    'user_id' => auth()->id()
                ]);

                return $result;
            } catch (\Exception $e) {
                Log::warning('Fallback Groq falhou para transferências', [
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
        $aiConfigService = new \App\Services\AIConfigService();
        $mainConfig = $aiConfigService->getAIConfig('groq');
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
        Log::info('Tentando fallback para outros provedores (transferências)', [
            'failed_provider' => $failedProvider,
            'user_id' => auth()->id()
        ]);

        // Obter outros provedores configurados
        $otherProviders = $this->getOtherConfiguredProviders($failedProvider);

        foreach ($otherProviders as $providerConfig) {
            try {
                Log::info('Tentando provedor de fallback para transferências', [
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
                Log::warning('Provedor de fallback falhou para transferências', [
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
                    'content' => 'Você é um especialista em análise de transferências bancárias. Responda sempre em JSON válido.'
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
        // Tentar extrair JSON da resposta
        $jsonStart = strpos($content, '[');
        $jsonEnd = strrpos($content, ']');
        
        if ($jsonStart !== false && $jsonEnd !== false) {
            $jsonContent = substr($content, $jsonStart, $jsonEnd - $jsonStart + 1);
            $decoded = json_decode($jsonContent, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        
        // Tentar encontrar JSON em blocos de código
        if (preg_match('/```json\s*(\[.*?\])\s*```/s', $content, $matches)) {
            $decoded = json_decode($matches[1], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        
        throw new \Exception('Resposta da IA não contém JSON válido: ' . substr($content, 0, 500));
    }

    /**
     * Aplica análise de transferências às transações
     */
    private function applyTransferAnalysis(array $transactions, array $transferAnalysis): array
    {
        foreach ($transferAnalysis as $analysis) {
            $originalIndex = $analysis['original_index'];
            
            if (isset($transactions[$originalIndex]) && $analysis['is_transfer']) {
                $transactions[$originalIndex]['is_transfer'] = true;
                $transactions[$originalIndex]['transfer_confidence'] = $analysis['confidence'];
                $transactions[$originalIndex]['origin_account_id'] = $analysis['origin_account_id'];
                $transactions[$originalIndex]['destination_account_id'] = $analysis['destination_account_id'];
                $transactions[$originalIndex]['origin_account_name'] = $analysis['origin_account_name'];
                $transactions[$originalIndex]['destination_account_name'] = $analysis['destination_account_name'];
                $transactions[$originalIndex]['transfer_reasoning'] = $analysis['reasoning'];
            }
        }
        
        return $transactions;
    }

    /**
     * Obtém endpoint da API
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
