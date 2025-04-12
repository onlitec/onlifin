<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppChannel
{
    /**
     * Enviar a notificação WhatsApp.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        // Pular se o usuário não deve receber notificações por WhatsApp
        if (method_exists($notifiable, 'shouldReceiveNotification') && 
            !$notifiable->shouldReceiveNotification('whatsapp')) {
            return;
        }

        if (!method_exists($notification, 'toWhatsApp')) {
            throw new \Exception('Método toWhatsApp não encontrado na notificação.');
        }

        $message = $notification->toWhatsApp($notifiable);

        // Obter o número de telefone
        $to = $message['to'] ?? null;

        if (empty($to)) {
            Log::warning('Número de WhatsApp não encontrado para o usuário', [
                'notifiable_id' => $notifiable->id ?? 'unknown',
                'notification' => get_class($notification)
            ]);
            return;
        }

        $this->sendWhatsAppMessage($to, $message['message'], $message['image'] ?? null);
    }

    /**
     * Enviar mensagem via API WhatsApp
     * 
     * Esta é uma implementação fictícia - você precisa substituir pela integração real
     * com um serviço como Twilio, UltraMsg, Chat-API, etc.
     */
    private function sendWhatsAppMessage($to, $messageText, $imageUrl = null)
    {
        try {
            // NOTA: Esta é apenas uma implementação de exemplo 
            // Em produção, você deve usar uma API real como Twilio, UltraMsg, etc.
            
            Log::info('Enviando mensagem WhatsApp', [
                'to' => $to,
                'message' => $messageText,
                'has_image' => !empty($imageUrl)
            ]);

            /*
            // Exemplo de integração com API externa (fictício)
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.whatsapp.api_key'),
            ])->post(config('services.whatsapp.base_url') . '/send', [
                'phone' => $to,
                'message' => $messageText,
                'image' => $imageUrl
            ]);

            if (!$response->successful()) {
                Log::error('Erro ao enviar mensagem WhatsApp', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
            */
            
            // Simulando envio para desenvolvimento
            Log::info('Simulação: Mensagem WhatsApp enviada com sucesso', [
                'to' => $to
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Erro ao enviar mensagem WhatsApp', [
                'exception' => $e->getMessage(),
                'to' => $to
            ]);
            
            return false;
        }
    }
} 