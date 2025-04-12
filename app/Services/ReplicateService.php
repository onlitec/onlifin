<?php

namespace App\Services;

use App\Models\ReplicateSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReplicateService
{
    private $settings;
    private $apiToken;
    private $apiUrl = 'https://api.replicate.com/v1';

    public function __construct()
    {
        $this->settings = ReplicateSetting::getActive();
        
        // Limpar e armazenar o token de API
        if ($this->settings && $this->settings->api_token) {
            $this->apiToken = $this->cleanApiToken($this->settings->api_token);
        }
    }
    
    /**
     * Limpa o token de API removendo prefixos comuns
     */
    private function cleanApiToken($token)
    {
        // Remover prefixo 'export REPLICATE_API_TOKEN='
        if (strpos($token, 'export REPLICATE_API_TOKEN=') === 0) {
            $token = substr($token, strlen('export REPLICATE_API_TOKEN='));
        }
        
        // Remover aspas se presentes
        $token = trim($token, '"\'');
        
        return $token;
    }

    /**
     * Analisa um extrato bancário usando o Claude-3
     */
    public function analyzeStatement($content)
    {
        if (!$this->settings) {
            throw new \Exception('Replicate não está configurado.');
        }
        
        if (!$this->apiToken) {
            throw new \Exception('Token de API do Replicate não está configurado ou é inválido.');
        }

        try {
            Log::info('Enviando requisição para Replicate API', [
                'model_version' => $this->settings->model_version,
                'content_length' => strlen($content)
            ]);
            
            $requestData = [
                'version' => $this->settings->model_version,
                'input' => [
                    'system' => $this->settings->system_prompt ?? 'Você é um assistente especializado em análise de extratos bancários.',
                    'prompt' => $content
                ]
            ];
            
            Log::debug('Dados da requisição para Replicate API', [
                'request_data' => $requestData
            ]);
            
            $response = Http::withHeaders([
                'Authorization' => 'Token ' . $this->apiToken,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '/predictions', $requestData);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('Resposta bem-sucedida da Replicate API', [
                    'status' => $response->status(),
                    'response' => $responseData
                ]);
                return $responseData;
            }

            $responseData = $response->json();
            Log::error('Erro ao chamar Replicate API', [
                'status' => $response->status(),
                'response' => $responseData
            ]);

            // Extrair mensagem de erro de diferentes formatos possíveis
            $errorMessage = 'Erro desconhecido';
            
            if (isset($responseData['error'])) {
                if (is_string($responseData['error'])) {
                    $errorMessage = $responseData['error'];
                } elseif (is_array($responseData['error']) && isset($responseData['error']['message'])) {
                    $errorMessage = $responseData['error']['message'];
                }
            } elseif (isset($responseData['detail'])) {
                $errorMessage = $responseData['detail'];
            }
            
            throw new \Exception('Erro ao processar extrato com IA: ' . $errorMessage);
        } catch (\Exception $e) {
            Log::error('Exceção ao chamar Replicate API: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw $e;
        }
    }

    /**
     * Verifica o status de uma predição
     */
    public function checkPrediction($predictionId)
    {
        if (!$this->settings) {
            throw new \Exception('Replicate não está configurado.');
        }
        
        if (!$this->apiToken) {
            throw new \Exception('Token de API do Replicate não está configurado ou é inválido.');
        }
        
        try {
            Log::info('Verificando status da predição', [
                'prediction_id' => $predictionId
            ]);
            
            $response = Http::withHeaders([
                'Authorization' => 'Token ' . $this->apiToken,
            ])->get($this->apiUrl . '/predictions/' . $predictionId);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('Status da predição obtido com sucesso', [
                    'status' => $response->status(),
                    'prediction_id' => $predictionId,
                    'response' => $responseData
                ]);
                return $responseData;
            }

            $responseData = $response->json();
            Log::error('Erro ao verificar status da predição', [
                'status' => $response->status(),
                'response' => $responseData,
                'prediction_id' => $predictionId
            ]);

            // Extrair mensagem de erro de diferentes formatos possíveis
            $errorMessage = 'Erro desconhecido';
            
            if (isset($responseData['error'])) {
                if (is_string($responseData['error'])) {
                    $errorMessage = $responseData['error'];
                } elseif (is_array($responseData['error']) && isset($responseData['error']['message'])) {
                    $errorMessage = $responseData['error']['message'];
                }
            } elseif (isset($responseData['detail'])) {
                $errorMessage = $responseData['detail'];
            }
            
            throw new \Exception('Erro ao verificar status: ' . $errorMessage);
        } catch (\Exception $e) {
            Log::error('Erro ao verificar status da predição: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'prediction_id' => $predictionId
            ]);
            throw $e;
        }
    }
} 