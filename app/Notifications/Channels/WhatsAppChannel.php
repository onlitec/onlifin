<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use App\Notifications\Channels\WhatsApp\WhatsAppProviderFactory;

class WhatsAppChannel
{
    /**
     * Provedor de WhatsApp
     *
     * @var \App\Notifications\Channels\WhatsApp\WhatsAppProviderInterface
     */
    protected $provider;
    
    /**
     * Construtor
     *
     * @param string|null $providerName Nome do provedor a ser usado (opcional)
     */
    public function __construct(?string $providerName = null)
    {
        $this->provider = WhatsAppProviderFactory::create($providerName);
    }

    /**
     * Enviar a notificação via WhatsApp.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        if (!method_exists($notification, 'toWhatsApp')) {
            throw new \Exception('Método toWhatsApp não encontrado na notificação.');
        }

        // Verificar se as notificações por WhatsApp estão habilitadas para o usuário
        if (method_exists($notifiable, 'shouldReceiveWhatsApp') && !$notifiable->shouldReceiveWhatsApp()) {
            Log::info('Notificações por WhatsApp desativadas para o usuário', [
                'user_id' => $notifiable->id ?? 'unknown'
            ]);
            return false;
        }

        // Obter o número de telefone do notifiable
        $to = $notifiable->routeNotificationForWhatsapp();
        if (empty($to)) {
            Log::warning('Número de WhatsApp não encontrado para o usuário.', [
                'user_id' => $notifiable->id ?? 'unknown'
            ]);
            return false;
        }

        // Obter os dados da notificação
        $whatsAppData = $notification->toWhatsApp($notifiable);
        
        // Verificar se há uma mensagem
        if (empty($whatsAppData['message'])) {
            Log::warning('Mensagem WhatsApp vazia', [
                'user_id' => $notifiable->id ?? 'unknown',
                'notification' => get_class($notification)
            ]);
            return false;
        }
        
        // Extrair opções adicionais
        $options = [];
        if (!empty($whatsAppData['media_url'])) {
            $options['media_url'] = $whatsAppData['media_url'];
            $options['media_caption'] = $whatsAppData['media_caption'] ?? null;
        }
        
        if (!empty($whatsAppData['template'])) {
            $options['template'] = $whatsAppData['template'];
            $options['template_data'] = $whatsAppData['template_data'] ?? [];
        }
        
        // Enviar a mensagem usando o provedor configurado
        return $this->provider->send($to, $whatsAppData['message'], $options);
    }
}
