<?php

namespace App\Notifications;

use App\Models\NotificationTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class DueDateNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $type;
    protected $data;
    protected $channels;
    protected $template;
    protected $daysUntilDue;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        string $type, 
        array $data, 
        int $daysUntilDue,
        array $channels = ['mail', 'database'],
        ?NotificationTemplate $template = null
    ) {
        $this->type = $type; // 'expense' ou 'income'
        $this->data = $data;
        $this->daysUntilDue = $daysUntilDue;
        $this->channels = $channels;
        $this->template = $template;
        
        // Adiciona os dias até o vencimento aos dados
        $this->data['days_until_due'] = $daysUntilDue;
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
        // Adicionar dados do usuário
        $data = array_merge($this->data, [
            'user' => [
                'name' => $notifiable->name,
                'email' => $notifiable->email,
            ]
        ]);
        
        // Se não tiver template, criar um template padrão
        if (!$this->template) {
            $this->template = NotificationTemplate::getDefaultByType(
                $this->type, 
                'due_date'
            );
            
            // Se ainda não tiver template, criar um novo
            if (!$this->template) {
                if ($this->type === 'expense') {
                    $this->template = NotificationTemplate::createDefaultExpenseTemplate();
                } else {
                    $this->template = NotificationTemplate::createDefaultIncomeTemplate();
                }
            }
        }
        
        // Processar o template
        $processed = $this->template->getProcessedEmailTemplate($data);
        
        $mail = (new MailMessage)
            ->subject($processed['subject'])
            ->greeting('Olá ' . $notifiable->name . '!')
            ->line(new HtmlString($processed['content']));
        
        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        // Adicionar dados do usuário
        $data = array_merge($this->data, [
            'user' => [
                'name' => $notifiable->name,
                'email' => $notifiable->email,
            ]
        ]);
        
        // Se não tiver template, criar um template padrão
        if (!$this->template) {
            $this->template = NotificationTemplate::getDefaultByType(
                $this->type, 
                'due_date'
            );
            
            // Se ainda não tiver template, criar um novo
            if (!$this->template) {
                if ($this->type === 'expense') {
                    $this->template = NotificationTemplate::createDefaultExpenseTemplate();
                } else {
                    $this->template = NotificationTemplate::createDefaultIncomeTemplate();
                }
            }
        }
        
        // Processar o template para push
        $processed = $this->template->getProcessedPushTemplate($data);
        
        return [
            'title' => $processed['title'],
            'message' => $processed['content'],
            'image' => $processed['image'],
            'type' => $this->type,
            'days_until_due' => $this->daysUntilDue,
            'data' => $this->data,
            'created_at' => now(),
        ];
    }
    
    /**
     * Get the WhatsApp representation of the notification.
     */
    public function toWhatsApp(object $notifiable): array
    {
        // Adicionar dados do usuário
        $data = array_merge($this->data, [
            'user' => [
                'name' => $notifiable->name,
                'email' => $notifiable->email,
            ]
        ]);
        
        // Se não tiver template, criar um template padrão
        if (!$this->template) {
            $this->template = NotificationTemplate::getDefaultByType(
                $this->type, 
                'due_date'
            );
            
            // Se ainda não tiver template, criar um novo
            if (!$this->template) {
                if ($this->type === 'expense') {
                    $this->template = NotificationTemplate::createDefaultExpenseTemplate();
                } else {
                    $this->template = NotificationTemplate::createDefaultIncomeTemplate();
                }
            }
        }
        
        // Processar o template
        $message = $this->template->getProcessedWhatsAppTemplate($data);
        
        return [
            'to' => $notifiable->phone ?? $notifiable->whatsapp_number,
            'message' => $message,
        ];
    }
}
