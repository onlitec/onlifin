<?php

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
     * @return array
     */
    public function getAIConfig(): array
    {
        $modelKey = $this->getActiveModelKey();
        
        if (!$modelKey) {
            return [
                'provider' => '',
                'model_name' => '',
                'api_key' => '',
                'system_prompt' => '',
                'prompt_template' => '',
                'is_configured' => false
            ];
        }
        
        return [
            'provider' => $modelKey->provider,
            'model_name' => $modelKey->model,
            'api_key' => $modelKey->api_token,
            'system_prompt' => $modelKey->system_prompt,
            'prompt_template' => $modelKey->system_prompt,
            'is_configured' => true
        ];
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
    public function isAIConfigured(): bool
    {
        return ModelApiKey::where('is_active', true)->exists();
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
}
