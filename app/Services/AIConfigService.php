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
    public function getAIConfig()
    {
        $config = [
            'is_configured' => false,
            'provider' => null,
            'model' => null,
            'has_api_key' => false
        ];
        
        // Verificar ReplicateSetting
        if (class_exists('\App\Models\ReplicateSetting')) {
            $settings = \App\Models\ReplicateSetting::getActive();
            if ($settings && $settings->isConfigured()) {
                $config['is_configured'] = true;
                $config['provider'] = $settings->provider;
                $config['model'] = $settings->model_version;
                $config['api_key'] = $settings->api_token; // Adicionado a chave da API
                $config['model_name'] = $settings->model_version; // Adicionado nome do modelo
                $config['system_prompt'] = $settings->system_prompt; // Adicionado o prompt do sistema
                $config['has_api_key'] = !empty($settings->api_token);
                
                Log::info('Configuração da IA encontrada no ReplicateSetting', [
                    'provider' => $settings->provider,
                    'model' => $settings->model_version,
                    'is_active' => $settings->is_active,
                    'has_api_token' => !empty($settings->api_token)
                ]);
            } else {
                Log::warning('Configuração da IA não encontrada no ReplicateSetting', [
                    'has_settings' => !empty($settings),
                    'is_active' => $settings ? $settings->is_active : false,
                    'has_api_token' => $settings ? !empty($settings->api_token) : false,
                    'has_model' => $settings ? !empty($settings->model_version) : false,
                    'has_provider' => $settings ? !empty($settings->provider) : false
                ]);
            }
        }
        
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
        if (class_exists('\App\Models\ReplicateSetting')) {
            $settings = \App\Models\ReplicateSetting::getActive();
            $isConfigured = $settings && $settings->isConfigured();
            
            Log::info('Verificação de configuração da IA', [
                'has_settings' => !empty($settings),
                'is_active' => $settings ? $settings->is_active : false,
                'has_api_token' => $settings ? !empty($settings->api_token) : false,
                'has_model' => $settings ? !empty($settings->model_version) : false,
                'has_provider' => $settings ? !empty($settings->provider) : false,
                'is_configured' => $isConfigured
            ]);
            
            return $isConfigured;
        }
        
        return false;
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
}
