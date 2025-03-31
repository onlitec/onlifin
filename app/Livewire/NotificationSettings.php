<?php

namespace App\Livewire;

use App\Models\Setting;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use LivewireUI\Modal\ModalComponent;

class NotificationSettings extends ModalComponent
{
    use LivewireAlert;

    // Configurações de Email
    public $email_notifications_enabled = true;
    public $email_notify_new_transactions = true;
    public $email_notify_due_dates = true;
    public $email_notify_low_balance = true;
    public $email_low_balance_threshold;

    // Configurações de WhatsApp
    public $whatsapp_notifications_enabled = false;
    public $whatsapp_number;
    public $whatsapp_notify_new_transactions = true;
    public $whatsapp_notify_due_dates = true;
    public $whatsapp_notify_low_balance = true;
    public $whatsapp_low_balance_threshold;

    public function mount()
    {
        $settings = Setting::where('user_id', auth()->id())->first();
        
        if ($settings) {
            $this->email_notifications_enabled = $settings->email_notifications_enabled;
            $this->email_notify_new_transactions = $settings->email_notify_new_transactions;
            $this->email_notify_due_dates = $settings->email_notify_due_dates;
            $this->email_notify_low_balance = $settings->email_notify_low_balance;
            $this->email_low_balance_threshold = $settings->email_low_balance_threshold;
            
            $this->whatsapp_notifications_enabled = $settings->whatsapp_notifications_enabled;
            $this->whatsapp_number = $settings->whatsapp_number;
            $this->whatsapp_notify_new_transactions = $settings->whatsapp_notify_new_transactions;
            $this->whatsapp_notify_due_dates = $settings->whatsapp_notify_due_dates;
            $this->whatsapp_notify_low_balance = $settings->whatsapp_notify_low_balance;
            $this->whatsapp_low_balance_threshold = $settings->whatsapp_low_balance_threshold;
        }
    }

    public function save()
    {
        Setting::updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'email_notifications_enabled' => $this->email_notifications_enabled,
                'email_notify_new_transactions' => $this->email_notify_new_transactions,
                'email_notify_due_dates' => $this->email_notify_due_dates,
                'email_notify_low_balance' => $this->email_notify_low_balance,
                'email_low_balance_threshold' => $this->email_low_balance_threshold,
                
                'whatsapp_notifications_enabled' => $this->whatsapp_notifications_enabled,
                'whatsapp_number' => $this->whatsapp_number,
                'whatsapp_notify_new_transactions' => $this->whatsapp_notify_new_transactions,
                'whatsapp_notify_due_dates' => $this->whatsapp_notify_due_dates,
                'whatsapp_notify_low_balance' => $this->whatsapp_notify_low_balance,
                'whatsapp_low_balance_threshold' => $this->whatsapp_low_balance_threshold,
            ]
        );

        $this->alert('success', 'Configurações de notificação salvas com sucesso!');
        $this->closeModal();
    }

    public static function modalMaxWidth(): string
    {
        return '2xl';
    }

    public function render()
    {
        return view('livewire.notification-settings');
    }
}
