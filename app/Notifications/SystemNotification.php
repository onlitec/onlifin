<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class SystemNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $title;
    protected $message;
    protected $actionUrl;
    protected $actionText;
    protected $image;
    protected $channels;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        string $title,
        string $message,
        array $channels = ['mail', 'database'],
        ?string $actionUrl = null,
        ?string $actionText = null,
        ?string $image = null
    ) {
        $this->title = $title;
        $this->message = $message;
        $this->actionUrl = $actionUrl;
        $this->actionText = $actionText;
        $this->image = $image;
        $this->channels = $channels;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title)
            ->greeting('Olá!')
            ->line($this->message);

        if ($this->image) {
            $mail->line(new HtmlString('<img src="' . $this->image . '" alt="Imagem da notificação" style="max-width: 100%;">'));
        }

        if ($this->actionUrl && $this->actionText) {
            $mail->action($this->actionText, $this->actionUrl);
        }

        return $mail->line('Obrigado por usar nosso aplicativo!');
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
            'read' => false,
            'created_at' => now(),
        ];
    }

    /**
     * Get the WhatsApp representation of the notification.
     * Esta função será usada pelo canal personalizado de WhatsApp
     */
    public function toWhatsApp(object $notifiable): array
    {
        $message = "*{$this->title}*\n\n";
        $message .= "{$this->message}\n\n";

        if ($this->actionUrl && $this->actionText) {
            $message .= "{$this->actionText}: {$this->actionUrl}";
        }

        return [
            'to' => $notifiable->whatsapp_number ?? $notifiable->phone,
            'message' => $message,
            'image' => $this->image
        ];
    }
}
