<?php

/*
 * ========================================================================
 * ARQUIVO PROTEGIDO - MODIFICAÇÕES REQUEREM AUTORIZAÇÃO EXPLÍCITA
 * ========================================================================
 * 
 * ATENÇÃO: Este arquivo contém código crítico para o funcionamento do sistema.
 * Qualquer modificação deve ser previamente autorizada e documentada.
 * 
 * Responsável: Equipe de Desenvolvimento
 * Última modificação autorizada: 2025-05-31
 * 
 * Para solicitar modificações, entre em contato com a equipe responsável.
 * ========================================================================
 */

namespace App\Services;

use App\Models\ModelApiKey;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class AIConfigService
{
    /**
     * Retorna as configurações da IA ativa do banco de dados
     * 
     * @param string|null $filterProvider Filtra por provedor específico (opcional)
     * @return array
     */
    public function getAIConfig($filterProvider = null)
    {
        $config = [
            'is_configured' => false,
            'provider' => null,
            'model' => null,
            'has_api_key' => false
        ];
        
        // PRIORIDADE 1: Verificar OpenRouter primeiro
        if (class_exists('\\App\\Models\\OpenRouterConfig')) {
            $openRouterConfig = \App\Models\OpenRouterConfig::first();
            if ($openRouterConfig && !empty($openRouterConfig->api_key)) {
                // Se temos um filtro de provedor e não corresponde, pular
                if ($filterProvider && $openRouterConfig->provider != $filterProvider) {
                    // Continuar para o próximo
                } else {
                    Log::info('Usando configuração de provedor de IA:', [
                        'provider' => $openRouterConfig->provider,
                        'model' => $openRouterConfig->model
                    ]);
                    $config['is_configured'] = true;
                    $config['provider'] = $openRouterConfig->provider;
                    $config['model'] = $openRouterConfig->model === 'custom' ? $openRouterConfig->custom_model : $openRouterConfig->model;
                    $config['api_key'] = $openRouterConfig->api_key;
                    $config['model_name'] = $openRouterConfig->model;
                    $config['system_prompt'] = $openRouterConfig->system_prompt;
                    $config['chat_prompt'] = $openRouterConfig->chat_prompt ?? $openRouterConfig->system_prompt;
                    $config['import_prompt'] = $openRouterConfig->import_prompt ?? '';
                    $config['has_api_key'] = true;
                    return $config;
                }
            }
        }
        
        // PRIORIDADE 2: Verificar configuração em config/ai.php
        if (Config::get('ai.enabled', false)) {
            $provider = Config::get('ai.provider');
            
            // Pular se temos filtro e o provedor não corresponde
            if ($filterProvider && $provider != $filterProvider) {
                // Continuar para o próximo
            } else {
                $apiKey = Config::get("ai.{$provider}.api_key");
                $model = Config::get("ai.{$provider}.model");
                if ($provider && $apiKey) {
                    Log::info('Usando configuração de config/ai.php:', ['provider' => $provider, 'model' => $model]);
                    $config['is_configured'] = true;
                    $config['provider'] = $provider;
                    $config['model'] = $model;
                    $config['api_key'] = $apiKey;
                    $config['model_name'] = $model;
                    $config['system_prompt'] = Config::get("ai.{$provider}.system_prompt");
                    $config['chat_prompt'] = Config::get("ai.{$provider}.chat_prompt");
                    $config['import_prompt'] = Config::get("ai.{$provider}.import_prompt");
                    $config['has_api_key'] = true;
                    return $config;
                }
            }
        }
        
        // PRIORIDADE 3: Verificar se há chave de API por modelo ativa (ModelApiKey)
        $activeKeyQuery = ModelApiKey::where('is_active', true);
        if ($filterProvider) {
            $activeKeyQuery->where('provider', $filterProvider);
        }
        $activeKey = $activeKeyQuery->first();
        
        if ($activeKey) {
            Log::info('Usando configuração de ModelApiKey:', ['provider' => $activeKey->provider, 'model' => $activeKey->model]);
            $config['is_configured'] = true;
            $config['provider'] = $activeKey->provider;
            $config['model'] = $activeKey->model;
            $config['api_key'] = $activeKey->api_token;
            $config['model_name'] = $activeKey->model;
            $config['system_prompt'] = $activeKey->system_prompt;
            $config['chat_prompt'] = $activeKey->chat_prompt ?? $activeKey->system_prompt;
            $config['import_prompt'] = $activeKey->import_prompt ?? '';
            $config['has_api_key'] = !empty($activeKey->api_token);
            return $config;
        }

        // PRIORIDADE 4: Verificar ReplicateSetting
        if (class_exists('\\App\\Models\\ReplicateSetting')) {
            $replicateQuery = \App\Models\ReplicateSetting::where('is_active', true);
            
            // Aplicar filtro por provedor, se fornecido
            if ($filterProvider) {
                $replicateQuery->where('provider', $filterProvider);
            }
            
            $settings = $replicateQuery->first();
            
            if ($settings && $settings->isConfigured()) {
                Log::info('Usando configuração do Replicate:', [
                    'provider' => $settings->provider,
                    'model' => $settings->model_version
                ]);
                $config['is_configured'] = true;
                $config['provider'] = $settings->provider;
                $config['model'] = $settings->model_version;
                $config['api_key'] = $settings->api_token;
                $config['model_name'] = $settings->model_version;
                $config['system_prompt'] = $settings->system_prompt;
                $config['chat_prompt'] = $settings->chat_prompt ?? $settings->system_prompt;
                $config['import_prompt'] = $settings->import_prompt ?? '';
                $config['has_api_key'] = !empty($settings->api_token);
                return $config;
            }
        }
        
        // Não logar warning para provedores não utilizados
        return $config;
    }
    
    /**
     * Retorna a primeira configuração de IA ativa do banco de dados
     * 
     * @return ModelApiKey|null
     */
    private function getActiveModelKey(): ?ModelApiKey
    {
        return ModelApiKey::where('is_active', true)->first();
    }
    
    /**
     * Verifica se há alguma IA configurada no banco de dados
     * 
     * @return bool
     */
    public function isAIConfigured()
    {
        $config = $this->getAIConfig();
        return $config['is_configured'];
    }
    
    /**
     * Processa documento usando a API e modelo configurados no banco de dados
     * 
     * @param string $filePath Caminho do arquivo a ser processado
     * @return array Dados extraídos do documento
     * @throws \Exception
     */
    public function processDocument(string $filePath): array
    {
        if (!$this->isAIConfigured()) {
            throw new \Exception('Nenhum modelo de IA está ativo no sistema');
        }
        
        $config = $this->getAIConfig();
        $apiKey = $config['api_key'];
        $modelName = $config['model_name'];
        $provider = $config['provider'];
        $systemPrompt = $config['system_prompt'];
        
        try {
            // Ler o conteúdo do arquivo
            $content = file_get_contents($filePath);
            if ($content === false) {
                throw new \Exception('Não foi possível ler o arquivo');
            }
            
            // Codificar o conteúdo em base64
            $base64Content = base64_encode($content);
            
            // Detectar o tipo de arquivo
            $mimeType = mime_content_type($filePath);
            
            // Preparar a chamada para a API
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json'
            ])->post($this->getApiEndpoint($provider, $modelName), [
                'model' => $modelName,
                'document' => [
                    'content' => $base64Content,
                    'mime_type' => $mimeType
                ],
                'features' => [
                    'extractEntities' => true,
                    'extractText' => true
                ]
            ]);
            
            if ($response->failed()) {
                Log::error('Erro na API de IA: ' . $response->body());
                throw new \Exception('Falha na API de IA: ' . $response->status());
            }
            
            return $this->processApiResponse($response->json());
            
        } catch (\Exception $e) {
            Log::error('Erro ao processar documento com IA: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Retorna o endpoint da API baseado no provedor e modelo configurado
     * 
     * @param string $provider Provedor da IA
     * @param string $modelName Nome do modelo
     * @return string
     */
    private function getApiEndpoint(string $provider, string $modelName): string
    {
        // Verificar se temos o endpoint explícito configurado para OpenRouter
        if (strtolower($provider) === 'openrouter') {
            $openRouterConfig = \App\Models\OpenRouterConfig::first();
            if ($openRouterConfig && !empty($openRouterConfig->endpoint)) {
                return $openRouterConfig->endpoint . '/chat/completions';
            }
            // Endpoint padrão do OpenRouter se não houver configuração específica
            return 'https://openrouter.ai/api/v1/chat/completions';
        }
        
        // Determinar o endpoint baseado no provedor e nome do modelo
        switch (strtolower($provider)) {
            case 'gemini':
            case 'google gemini':
                return 'https://generativelanguage.googleapis.com/v1/models/' . $modelName . ':processDocument';
                
            case 'openai':
                return 'https://api.openai.com/v1/engines/' . $modelName . '/completions';
                
            case 'anthropic':
                return 'https://api.anthropic.com/v1/messages';
                
            default:
                // Endpoint genérico para outros provedores
                Log::warning('Provedor desconhecido: ' . $provider . '. Usando endpoint genérico.');
                return 'https://api.example.com/v1/document-processing';
        }
    }
    
    /**
     * Processa a resposta da API
     * 
     * @param array $response
     * @return array
     */
    private function processApiResponse(array $response): array
    {
        // Estrutura de dados padrão para retorno
        $processedData = [
            'transactions' => [],
            'metadata' => [
                'provider' => $this->getAIConfig()['provider'] ?? 'unknown',
                'model_used' => $this->getAIConfig()['model_name'] ?? 'unknown',
                'processed_timestamp' => time()
            ]
        ];
        
        // Extrair entidades e transações do documento
        // Lógica de extração depende do formato da resposta da API
        if (isset($response['entities'])) {
            foreach ($response['entities'] as $entity) {
                if ($entity['type'] === 'transaction') {
                    $processedData['transactions'][] = [
                        'date' => $entity['properties']['date'] ?? null,
                        'amount' => $entity['properties']['amount'] ?? null,
                        'description' => $entity['properties']['description'] ?? '',
                        'category' => $entity['properties']['category'] ?? null,
                        'confidence' => $entity['confidence'] ?? 0
                    ];
                }
            }
        }
        
        return $processedData;
    }

    /**
     * Corrige problemas de codificação em textos com caracteres especiais
     * 
     * @param string $texto Texto com problemas de codificação
     * @return string Texto corrigido
     */
    public function corrigirAcentuacao($texto)
    {
        if (empty($texto)) {
            return $texto;
        }
        
        // Detecta a codificação atual
        $encoding = mb_detect_encoding($texto, 'UTF-8, ISO-8859-1, ISO-8859-15', true);
        
        // Se não for UTF-8, converte para UTF-8
        if ($encoding && $encoding !== 'UTF-8') {
            $texto = mb_convert_encoding($texto, 'UTF-8', $encoding);
        }
        
        // Correções específicas para problemas comuns
        $substituicoes = [
            // Vogais acentuadas
            '/Ã©/' => 'é', '/Ã¡/' => 'á', '/Ã³/' => 'ó', '/Ãº/' => 'ú', '/Ã­/' => 'í',
            '/Ãª/' => 'ê', '/Ã¢/' => 'â', '/Ã´/' => 'ô', '/Ã£/' => 'ã', '/Ãµ/' => 'õ',
            '/Ã‰/' => 'É', '/Ã/' => 'Á', '/Ã"/' => 'Ó', '/Ãš/' => 'Ú', '/Ã/' => 'Í',
            '/ÃŠ/' => 'Ê', '/Ã‚/' => 'Â', '/Ã"/' => 'Ô', '/Ãƒ/' => 'Ã', '/Ã•/' => 'Õ',
            
            // Cedilha e outros caracteres especiais
            '/Ã§/' => 'ç', '/Ã‡/' => 'Ç',
            
            // Caracteres usados para ofuscar informações sensíveis
            '/â¢/' => '*', '/â€¢/' => '*'
        ];
        
        foreach ($substituicoes as $padrao => $substituicao) {
            $texto = preg_replace($padrao, $substituicao, $texto);
        }
        
        return $texto;
    }

    /**
     * Aplica correção de acentuação em um array de transações
     * 
     * @param array $transacoes Array de transações
     * @return array Transações com textos corrigidos
     */
    public function corrigirAcentuacaoEmTransacoes($transacoes)
    {
        if (empty($transacoes) || !is_array($transacoes)) {
            return $transacoes;
        }
        
        foreach ($transacoes as $key => $transacao) {
            if (isset($transacao['description'])) {
                $transacoes[$key]['description'] = $this->corrigirAcentuacao($transacao['description']);
            }
            
            if (isset($transacao['notes'])) {
                $transacoes[$key]['notes'] = $this->corrigirAcentuacao($transacao['notes']);
            }
        }
        
        return $transacoes;
    }

    /**
     * Retorna apenas a configuração do Google para implantação dedicada.
     *
     * @return array
     */
    public function getAllAIConfigs(): array
    {
        // Uso exclusivo de Google para implantação
        if (!Config::get('ai.enabled', false)) {
            return [];
        }
        $provider = 'google';
        $apiKey = Config::get("ai.{$provider}.api_key");
        $model  = Config::get("ai.{$provider}.model");
        $prompt = Config::get("ai.{$provider}.system_prompt");

        if (!$provider || !$apiKey) {
            // Nenhuma configuração Google válida
            return [];
        }
        return [[
            'provider'      => $provider,
            'model'         => $model,
            'api_key'       => $apiKey,
            'system_prompt' => $prompt,
        ]];
    }

    /**
     * Retorna o prompt fixo padrão para análise de extratos bancários
     * Este prompt deve ser usado por todas as IAs para garantir consistência na importação
     * 
     * @param array $transactions Transações a serem analisadas
     * @param array $categories Categorias disponíveis no sistema
     * @param array $recurringTransactions Transações recorrentes/fixas já cadastradas (opcional)
     * @return string Prompt padronizado
     * 
     * @protected MODIFICAÇÃO PROTEGIDA - Qualquer alteração neste método requer autorização.
     * @author Equipe de Desenvolvimento
     * @since 2025-05-31
     */
    public function getStandardImportPrompt($transactions, $categories, $recurringTransactions = [])
    {
        // Converter dados para JSON para inclusão no prompt
        $transactionsJson = json_encode($transactions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $categoriesJson = json_encode($categories, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $recurringTransactionsJson = !empty($recurringTransactions) 
            ? json_encode($recurringTransactions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            : "[]";
            
        // Prompt fixo padronizado para todas as IAs
        return <<<EOT
Você é um especialista em análise financeira e categorização de transações bancárias.
Sua tarefa é analisar transações de extratos bancários e prepará-las para importação no sistema financeiro.

## DADOS DE ENTRADA

### TRANSAÇÕES DO EXTRATO:
$transactionsJson

### CATEGORIAS DISPONÍVEIS:
$categoriesJson

### TRANSAÇÕES RECORRENTES EXISTENTES:
$recurringTransactionsJson

## NAMESPACE DOS CAMPOS

1. **Transaction::class**
   - `type`: Tipo da transação ('income' para receita, 'expense' para despesa)
   - `date`: Data da transação no formato Y-m-d
   - `description`: Descrição/título da transação
   - `amount`: Valor em centavos (inteiro, já convertido de decimal)
   - `category_id`: ID da categoria selecionada
   - `account_id`: ID da conta bancária (já definido no sistema)
   - `cliente`: Nome do cliente/pagador (apenas para transações tipo 'income')
   - `fornecedor`: Nome do fornecedor (apenas para transações tipo 'expense')
   - `notes`: Observações adicionais sobre a transação
   - `status`: Status da transação (sempre "paid" para transações importadas)
   - `recurrence_type`: Tipo de recorrência ('none', 'fixed', 'installment')
   - `installment_number`: Número da parcela atual (para recorrências do tipo 'installment')
   - `total_installments`: Total de parcelas (para recorrências do tipo 'installment')
   - `next_date`: Data da próxima ocorrência (para recorrências do tipo 'fixed')

2. **Category::class**
   - `id`: ID da categoria existente
   - `name`: Nome da categoria
   - `type`: Tipo da categoria ('income' ou 'expense')
   - `icon`: Ícone da categoria (opcional)
   - `user_id`: ID do usuário proprietário (já definido no sistema)

## INSTRUÇÕES DE PROCESSAMENTO

1. **CATEGORIZAÇÃO**:
   - Para cada transação, analise a descrição e valor para determinar a categoria mais adequada.
   - Use as categorias existentes sempre que possível, consultando o campo `categoriesJson`.
   - Se não existir uma categoria adequada, sugira uma nova no campo `suggested_category`.
   - Estabeleça a categoria correta baseando-se na descrição da transação (ex: "Tenda Atacado" → categoria "Supermercado").

2. **IDENTIFICAÇÃO DE TRANSAÇÕES RECORRENTES**:
   - Compare as transações do extrato com as transações recorrentes existentes.
   - Se encontrar uma correspondência por valor, data aproximada e descrição similar, marque como pagamento de fatura recorrente.
   - Uma fatura recorrente identificada deve atualizar o status da cobrança futura para "paid".

3. **IDENTIFICAÇÃO DE CLIENTES/FORNECEDORES**:
   - Para receitas: Tente identificar o cliente/pagador na descrição.
   - Para despesas: Tente identificar o fornecedor na descrição.

4. **FORMATAÇÃO DE DESCRIÇÕES**:
   - Mantenha o título original da transação como identificado no extrato.
   - Remova códigos ou números desnecessários que não contribuem para identificação.
   - Exemplo correto: "Tenda Atacado" (não "Compra em Tenda Atacado 29/05").

## FORMATO DE RESPOSTA

Responda APENAS com um array JSON contendo objetos com os seguintes campos:
- `id`: ID da transação (use o mesmo do input)
- `type`: 'income' ou 'expense'
- `date`: Data no formato Y-m-d
- `description`: Descrição formatada da transação
- `amount`: Valor em centavos (inteiro)
- `category_id`: ID da categoria existente ou null
- `suggested_category`: Nome da nova categoria sugerida se category_id for null
- `cliente`: Nome do cliente (apenas para 'income')
- `fornecedor`: Nome do fornecedor (apenas para 'expense')
- `status`: "paid" (sempre para importações)
- `notes`: Observações adicionais (pode incluir detalhes extras identificados)
- `is_recurring_payment`: Boolean indicando se é pagamento de uma fatura recorrente
- `related_recurring_id`: ID da transação recorrente relacionada (se aplicável)

Exemplo de resposta:
```json
[
  {
    "id": 0,
    "type": "expense",
    "date": "2023-05-15",
    "description": "Tenda Atacado",
    "amount": 15075,
    "category_id": 5,
    "suggested_category": null,
    "fornecedor": "Tenda Atacado",
    "cliente": null,
    "status": "paid",
    "notes": "Compra em supermercado",
    "is_recurring_payment": false,
    "related_recurring_id": null
  },
  {
    "id": 1,
    "type": "income",
    "date": "2023-05-10",
    "description": "Salário",
    "amount": 350000,
    "category_id": 1,
    "suggested_category": null,
    "cliente": "Empresa ABC",
    "fornecedor": null,
    "status": "paid",
    "notes": "Salário mensal",
    "is_recurring_payment": true,
    "related_recurring_id": 42
  }
]
```

Retorne APENAS o JSON, sem explicações adicionais ou texto fora do formato JSON.
EOT;
    }

    /**
     * Modifica o prompt padrão para incluir instruções específicas
     * 
     * @param string $standardPrompt Prompt padrão base
     * @param string $additionalInstructions Instruções adicionais específicas 
     * @return string Prompt modificado
     * 
     * @protected MODIFICAÇÃO PROTEGIDA - Qualquer alteração neste método requer autorização.
     * @author Equipe de Desenvolvimento
     * @since 2025-05-31
     */
    public function customizeImportPrompt($standardPrompt, $additionalInstructions)
    {
        // Insere instruções adicionais antes da seção de formato de resposta
        $insertPosition = strpos($standardPrompt, '## FORMATO DE RESPOSTA');
        if ($insertPosition !== false) {
            return substr_replace(
                $standardPrompt, 
                "## INSTRUÇÕES ESPECÍFICAS\n\n" . $additionalInstructions . "\n\n## FORMATO DE RESPOSTA", 
                $insertPosition, 
                strlen('## FORMATO DE RESPOSTA')
            );
        }
        
        // Fallback se não encontrar o ponto de inserção
        return $standardPrompt . "\n\n## INSTRUÇÕES ESPECÍFICAS\n\n" . $additionalInstructions;
    }

    /**
     * Retorna configuração específica para um modelo
     * 
     * @param string $provider Nome do provedor 
     * @param string $model Nome do modelo
     * @return array|null Configuração específica ou null se não encontrada
     */
    public function getModelSpecificConfig($provider, $model)
    {
        // Buscar configuração de modelo específico
        $modelConfig = ModelApiKey::where('provider', $provider)
            ->where('model', $model)
            ->where('is_active', true)
            ->first();
            
        if ($modelConfig) {
            return [
                'provider' => $provider,
                'model' => $model,
                'api_key' => $modelConfig->api_token,
                'model_name' => $model,
                'system_prompt' => $modelConfig->system_prompt,
                'chat_prompt' => $modelConfig->chat_prompt ?? $modelConfig->system_prompt,
                'import_prompt' => $modelConfig->import_prompt ?? '',
                'endpoint' => null,
                'has_api_key' => !empty($modelConfig->api_token)
            ];
        }
        
        return null;
    }
}
