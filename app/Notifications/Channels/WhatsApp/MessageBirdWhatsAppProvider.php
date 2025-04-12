<?php

namespace App\Notifications\Channels\WhatsApp;

use Illuminate\Support\Facades\Http;
use Exception;

class MessageBirdWhatsAppProvider extends AbstractWhatsAppProvider
{
    /**
     * Nome do provedor
     *
     * @var string
     */
    protected $providerName = 'MessageBird';
    
    /**
     * URL base da API MessageBird
     *
     * @var string
     */
    protected $baseUrl = 'https://conversations.messagebird.com/v1/send';
    
    /**
     * Envia uma mensagem de WhatsApp via MessageBird
     *
     * @param string $to Número de telefone do destinatário
     * @param string $message Conteúdo da mensagem
     * @param array $options Opções adicionais (mídia, botões, etc.)
     * @return bool
     */
    public function send(string $to, string $message, array $options = []): bool
    {
        if (!$this->isConfigured()) {
            $this->logError('Provedor MessageBird não está configurado corretamente');
            return false;
        }
        
        $accessKey = $this->config['access_key'];
        $channelId = $this->config['channel_id'];
        $namespace = $this->config['namespace'] ?? null;
        
        // Formata o número de telefone
        $formattedTo = $this->formatPhoneNumber($to);
        
        try {
            $this->logInfo('Enviando mensagem WhatsApp via MessageBird', [
                'to' => $formattedTo,
                'channel_id' => $channelId,
                'message_length' => strlen($message)
            ]);
            
            // Preparar dados da requisição
            $data = [
                'to' => $formattedTo,
                'from' => $channelId,
                'type' => 'text',
                'content' => [
                    'text' => $message
                ]
            ];
            
            // Adicionar namespace se fornecido
            if ($namespace) {
                $data['namespace'] = $namespace;
            }
            
            // Adicionar mídia, se fornecida
            if (!empty($options['media_url'])) {
                $data['type'] = 'image';
                $data['content'] = [
                    'image' => [
                        'url' => $options['media_url'],
                        'caption' => $options['media_caption'] ?? ''
                    ]
                ];
            }
            
            // Fazer a requisição para a API do MessageBird
            $response = Http::withHeaders([
                'Authorization' => 'AccessKey ' . $accessKey,
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl, $data);
            
            // Verificar resposta
            if ($response->successful()) {
                $responseData = $response->json();
                $this->logInfo('Mensagem WhatsApp enviada com sucesso via MessageBird', [
                    'message_id' => $responseData['id'] ?? 'unknown',
                    'status' => $responseData['status'] ?? 'unknown'
                ]);
                return true;
            } else {
                $this->logError('Falha ao enviar mensagem WhatsApp via MessageBird', [
                    'status' => $response->status(),
                    'error' => $response->body()
                ]);
                return false;
            }
        } catch (Exception $e) {
            $this->logError('Exceção ao enviar mensagem WhatsApp via MessageBird', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
    
    /**
     * Verifica se o provedor está configurado e ativo
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return parent::isConfigured() && 
               !empty($this->config['access_key']) && 
               !empty($this->config['channel_id']);
    }
}
