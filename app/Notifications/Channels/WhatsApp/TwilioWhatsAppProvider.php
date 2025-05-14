<?php

namespace App\Notifications\Channels\WhatsApp;

use Illuminate\Support\Facades\Http;
use Exception;

class TwilioWhatsAppProvider extends AbstractWhatsAppProvider
{
    /**
     * Nome do provedor
     *
     * @var string
     */
    protected $providerName = 'Twilio';
    
    /**
     * URL base da API Twilio
     *
     * @var string
     */
    protected $baseUrl = 'https://api.twilio.com/2010-04-01/Accounts/';
    
    /**
     * Envia uma mensagem de WhatsApp via Twilio
     *
     * @param string $to Número de telefone do destinatário
     * @param string $message Conteúdo da mensagem
     * @param array $options Opções adicionais (mídia, botões, etc.)
     * @return bool
     */
    public function send(string $to, string $message, array $options = []): bool
    {
        if (!$this->isConfigured()) {
            $this->logError('Provedor Twilio não está configurado corretamente');
            return false;
        }
        
        $accountSid = $this->config['account_sid'];
        $authToken = $this->config['auth_token'];
        $fromNumber = $this->config['from_number'];
        $whatsappPrefix = $this->config['whatsapp_prefix'] ?? 'whatsapp:';
        
        // Formata os números de telefone para o padrão do Twilio
        $formattedTo = $whatsappPrefix . $this->formatPhoneNumber($to);
        $formattedFrom = $whatsappPrefix . $fromNumber;
        
        try {
            $this->logInfo('Enviando mensagem WhatsApp via Twilio', [
                'to' => $formattedTo,
                'from' => $formattedFrom,
                'message_length' => strlen($message)
            ]);
            
            // Preparar dados da requisição
            $data = [
                'From' => $formattedFrom,
                'To' => $formattedTo,
                'Body' => $message,
            ];
            
            // Adicionar mídia, se fornecida
            if (!empty($options['media_url'])) {
                $data['MediaUrl'] = $options['media_url'];
            }
            
            // Fazer a requisição para a API do Twilio
            $response = Http::withBasicAuth($accountSid, $authToken)
                ->asForm()
                ->post("{$this->baseUrl}{$accountSid}/Messages.json", $data);
            
            // Verificar resposta
            if ($response->successful()) {
                $responseData = $response->json();
                $this->logInfo('Mensagem WhatsApp enviada com sucesso via Twilio', [
                    'message_sid' => $responseData['sid'] ?? 'unknown',
                    'status' => $responseData['status'] ?? 'unknown'
                ]);
                return true;
            } else {
                $this->logError('Falha ao enviar mensagem WhatsApp via Twilio', [
                    'status' => $response->status(),
                    'error' => $response->body()
                ]);
                return false;
            }
        } catch (Exception $e) {
            $this->logError('Exceção ao enviar mensagem WhatsApp via Twilio', [
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
               !empty($this->config['account_sid']) && 
               !empty($this->config['auth_token']) && 
               !empty($this->config['from_number']);
    }
}
