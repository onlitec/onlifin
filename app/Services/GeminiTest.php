<?php

namespace App\Services;

use App\Models\ModelApiKey;
use Illuminate\Support\Facades\Log;

/**
 * Classe auxiliar para testar a conexão com o Gemini
 */
class GeminiTest
{
    /**
     * Testa a conexão com o Google Gemini
     * 
     * @param string $apiKey Chave API fornecida
     * @param string $model Nome do modelo Gemini a ser testado
     * @param bool $useModelSpecificKey Se deve verificar primeiro uma chave específica para o modelo
     */
    public static function testConnection($apiKey, $model = 'gemini-2.0-flash', $useModelSpecificKey = true)
    {
        // Se solicitado, verificar se existe uma chave específica para este modelo
        if ($useModelSpecificKey) {
            try {
                $modelKey = ModelApiKey::where('provider', 'gemini')
                    ->where('model', $model)
                    ->where('is_active', true)
                    ->first();
                    
                if ($modelKey && !empty($modelKey->api_token)) {
                    Log::info("Usando chave API específica para o modelo {$model}");
                    $apiKey = $modelKey->api_token;
                }
            } catch (\Exception $e) {
                Log::warning("Erro ao verificar chave específica para o modelo: {$e->getMessage()}");
                // Continua usando a chave fornecida originalmente
            }
        }
        // Garantir que estamos usando o modelo que sabemos que funciona
        $model = empty($model) ? 'gemini-2.0-flash' : $model;
        
        try {
            // Registrar início do teste
            Log::info("Testando conexão com Gemini usando modelo {$model}");
            
            // Preparar a URL e payload exatamente conforme o teste bem-sucedido
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
            $data = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => 'Explain how AI works in one simple sentence']
                        ]
                    ]
                ]
            ];
            
            // Usar cURL para maior controle e compatibilidade
            $ch = curl_init($endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            
            // Executar a requisição
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            // Registrar resposta básica
            Log::info("Resposta da API Gemini: Código HTTP {$httpCode}");
            
            // Verificar resultado
            if ($httpCode >= 200 && $httpCode < 300) {
                $data = json_decode($response, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return true;
                }
            }
            
            // Se chegou aqui, houve algum erro
            $errorData = json_decode($response, true);
            if ($errorData && isset($errorData['error'])) {
                throw new \Exception("Erro na API Gemini: " . ($errorData['error']['message'] ?? 'Erro desconhecido'));
            } else {
                throw new \Exception("Erro ao conectar com Gemini. Código HTTP: {$httpCode}" . ($error ? " Erro cURL: {$error}" : ""));
            }
            
        } catch (\Exception $e) {
            Log::error("Erro ao testar Gemini: " . $e->getMessage());
            throw $e;
        }
    }
}
