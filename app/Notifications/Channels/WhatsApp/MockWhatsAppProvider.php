<?php

namespace App\Notifications\Channels\WhatsApp;

use Illuminate\Support\Facades\Log;

class MockWhatsAppProvider extends AbstractWhatsAppProvider
{
    /**
     * Nome do provedor
     *
     * @var string
     */
    protected $providerName = 'Mock';
    
    /**
     * Simula o envio de uma mensagem de WhatsApp (para ambiente de desenvolvimento)
     *
     * @param string $to Número de telefone do destinatário
     * @param string $message Conteúdo da mensagem
     * @param array $options Opções adicionais (mídia, botões, etc.)
     * @return bool
     */
    public function send(string $to, string $message, array $options = []): bool
    {
        $formattedTo = $this->formatPhoneNumber($to);
        
        $logMessage = $this->config['log_messages'] ?? true;
        
        if ($logMessage) {
            $this->logInfo('SIMULAÇÃO: Enviando mensagem WhatsApp', [
                'to' => $formattedTo,
                'message' => $message,
                'options' => $options
            ]);
        }
        
        // Simular um pequeno atraso para tornar a simulação mais realista
        if (app()->environment() !== 'testing') {
            usleep(rand(100000, 500000)); // 100-500ms
        }
        
        // Simular uma mensagem de sucesso
        $this->logInfo('SIMULAÇÃO: Mensagem WhatsApp enviada com sucesso', [
            'message_id' => 'mock_' . uniqid(),
            'status' => 'delivered',
            'timestamp' => now()->toIso8601String()
        ]);
        
        return true;
    }
    
    /**
     * Verifica se o provedor está configurado e ativo
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return $this->config['enabled'] ?? true;
    }
}
