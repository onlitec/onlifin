<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $channels;
    protected $title;
    protected $message;
    protected $actionUrl;
    protected $actionText;
    protected $image;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        array $channels = ['mail', 'database'],
        ?string $title = null,
        ?string $message = null,
        ?string $actionUrl = null,
        ?string $actionText = null,
        ?string $image = null
    ) {
        $this->channels = $channels;
        $this->title = $title ?? 'Notificação de Teste';
        $this->message = $message ?? 'Esta é uma notificação de teste enviada em ' . now()->format('d/m/Y H:i:s');
        $this->actionUrl = $actionUrl;
        $this->actionText = $actionText;
        $this->image = $image;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Verificar se o usuário tem canais específicos configurados
        if (method_exists($notifiable, 'shouldReceiveNotification')) {
            return array_filter($this->channels, function ($channel) use ($notifiable) {
                return $notifiable->shouldReceiveNotification($channel);
            });
        }
        
        return $this->channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title)
            ->greeting('Olá ' . $notifiable->name . '!')
            ->line($this->message);
            
        if ($this->actionUrl && $this->actionText) {
            $mail->action($this->actionText, $this->actionUrl);
        }
        
        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'action_url' => $this->actionUrl,
            'action_text' => $this->actionText,
            'image' => $this->image,
            'created_at' => now(),
        ];
    }
    
    /**
     * Get the WhatsApp representation of the notification.
     */
    public function toWhatsApp(object $notifiable): array
    {
        $message = "*{$this->title}*\n\n{$this->message}";
        
        if ($this->actionUrl) {
            $message .= "\n\nClique aqui: {$this->actionUrl}";
        }
        
        $data = [
            'message' => $message,
        ];
        
        // Adicionar imagem se disponível
        if ($this->image) {
            $data['media_url'] = $this->image;
            $data['media_caption'] = $this->title;
        }
        
        // Adicionar template se necessário (para provedores que suportam templates)
        if (config('notification-channels.whatsapp.use_templates', false)) {
            $data['template'] = 'notification_template';
            $data['template_data'] = [
                'title' => $this->title,
                'body' => $this->message,
                'action_url' => $this->actionUrl,
                'action_text' => $this->actionText,
            ];
        }
        
        return $data;
    }
} 