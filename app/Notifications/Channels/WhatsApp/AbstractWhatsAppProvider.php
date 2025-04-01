<?php

namespace App\Notifications\Channels\WhatsApp;

use Illuminate\Support\Facades\Log;

abstract class AbstractWhatsAppProvider implements WhatsAppProviderInterface
{
    /**
     * Configurações do provedor
     *
     * @var array
     */
    protected $config;
    
    /**
     * Nome do provedor
     *
     * @var string
     */
    protected $providerName;
    
    /**
     * Construtor
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }
    
    /**
     * Registra informações de log
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logInfo(string $message, array $context = []): void
    {
        Log::info("[WhatsApp:{$this->providerName}] {$message}", $context);
    }
    
    /**
     * Registra erros de log
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logError(string $message, array $context = []): void
    {
        Log::error("[WhatsApp:{$this->providerName}] {$message}", $context);
    }
    
    /**
     * Formata o número de telefone de acordo com o padrão do provedor
     *
     * @param string $phoneNumber
     * @return string
     */
    protected function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove caracteres não numéricos
        $phoneNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);
        
        // Se não começar com +, adiciona o prefixo internacional padrão (Brasil)
        if (substr($phoneNumber, 0, 1) !== '+') {
            $phoneNumber = '+55' . $phoneNumber;
        }
        
        return $phoneNumber;
    }
    
    /**
     * Verifica se o provedor está configurado e ativo
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return $this->config['enabled'] ?? false;
    }
}
