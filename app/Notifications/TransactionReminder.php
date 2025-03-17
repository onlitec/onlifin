<?php

namespace App\Notifications;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;

class TransactionReminder extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * A transação que precisa ser notificada
     *
     * @var \App\Models\Transaction
     */
    protected $transaction;

    /**
     * Dias restantes para o vencimento
     *
     * @var int
     */
    protected $daysRemaining;

    /**
     * Create a new notification instance.
     */
    public function __construct(Transaction $transaction, int $daysRemaining)
    {
        $this->transaction = $transaction;
        $this->daysRemaining = $daysRemaining;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['mail', 'database'];
        
        if (config('services.twilio.enabled') && $notifiable->phone) {
            $channels[] = TwilioChannel::class;
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $transactionType = $this->transaction->type === 'income' ? 'receber' : 'pagar';
        $subject = "Lembrete: Você tem uma transação a $transactionType em {$this->daysRemaining} dias";
        
        return (new MailMessage)
            ->subject($subject)
            ->greeting("Olá {$notifiable->name},")
            ->line("Este é um lembrete de que você tem uma transação pendente a $transactionType:")
            ->line("Descrição: {$this->transaction->description}")
            ->line("Valor: {$this->transaction->formatted_amount}")
            ->line("Data: " . $this->transaction->date->format('d/m/Y'))
            ->line("Dias restantes: {$this->daysRemaining}")
            ->action('Ver detalhes', url('/transactions/' . $this->transaction->id))
            ->line('Obrigado por usar nosso aplicativo!');
    }

    /**
     * Get the Twilio representation of the notification.
     */
    public function toTwilio(object $notifiable)
    {
        $transactionType = $this->transaction->type === 'income' ? 'receber' : 'pagar';
        
        $message = "Olá {$notifiable->name}, você tem uma transação a $transactionType em {$this->daysRemaining} dias.\n";
        $message .= "Descrição: {$this->transaction->description}\n";
        $message .= "Valor: {$this->transaction->formatted_amount}\n";
        $message .= "Data: " . $this->transaction->date->format('d/m/Y');
        
        return (new TwilioSmsMessage())
            ->content($message);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'transaction_id' => $this->transaction->id,
            'description' => $this->transaction->description,
            'amount' => $this->transaction->amount,
            'date' => $this->transaction->date->format('Y-m-d'),
            'days_remaining' => $this->daysRemaining,
            'type' => $this->transaction->type,
        ];
    }
}
