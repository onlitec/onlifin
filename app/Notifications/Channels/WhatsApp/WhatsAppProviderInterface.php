<?php

namespace App\Notifications\Channels\WhatsApp;

interface WhatsAppProviderInterface
{
    /**
     * Envia uma mensagem de WhatsApp
     *
     * @param string $to Número de telefone do destinatário
     * @param string $message Conteúdo da mensagem
     * @param array $options Opções adicionais (mídia, botões, etc.)
     * @return bool
     */
    public function send(string $to, string $message, array $options = []): bool;
    
    /**
     * Verifica se o provedor está configurado e ativo
     *
     * @return bool
     */
    public function isConfigured(): bool;
}
