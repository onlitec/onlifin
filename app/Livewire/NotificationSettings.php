<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;
use App\Models\NotificationSetting;
use Illuminate\Support\Facades\Auth;
use App\Notifications\Channels\WhatsApp\WhatsAppProviderFactory;

class NotificationSettings extends Component
{
    public $user;
    public $emailNotifications;
    public $pushNotifications;
    public $dueDateNotifications;
    public $whatsappNotifications;
    public $webNotifications;
    public $phone;
    public $testNotificationSent = false;
    public $whatsappProvider;
    public $availableWhatsappProviders = [];
    public $showAdvancedSettings = false;

    public function mount()
    {
        $this->user = Auth::user();
        $this->loadSettings();
        $this->loadWhatsAppProviders();
    }

    protected function loadSettings()
    {
        // Carregar configurações básicas do usuário
        $this->emailNotifications = $this->user->email_notifications ?? true;
        $this->pushNotifications = $this->user->push_notifications ?? false;
        $this->dueDateNotifications = $this->user->due_date_notifications ?? true;
        
        // Carregar configurações avançadas
        $settings = NotificationSetting::getOrCreate($this->user->id);
        $this->whatsappNotifications = $settings->whatsapp_enabled ?? false;
        $this->webNotifications = $settings->push_enabled ?? false;
        $this->phone = $this->user->phone;
        $this->whatsappProvider = $settings->whatsapp_provider ?? config('notification-channels.whatsapp.default', 'twilio');
    }
    
    protected function loadWhatsAppProviders()
    {
        // Carregar provedores disponíveis
        $this->availableWhatsappProviders = WhatsAppProviderFactory::getAvailableProviders();
        
        // Filtrar provedores que não devem ser exibidos para o usuário
        if (in_array('mock', $this->availableWhatsappProviders) && !app()->environment('local', 'development')) {
            $this->availableWhatsappProviders = array_filter($this->availableWhatsappProviders, function($provider) {
                return $provider !== 'mock';
            });
        }
    }

    public function closeModal()
    {
        $this->dispatch('closeModal');
    }

    public function saveSettings()
    {
        $this->validate([
            'emailNotifications' => 'boolean',
            'pushNotifications' => 'boolean',
            'dueDateNotifications' => 'boolean',
            'whatsappNotifications' => 'boolean',
            'webNotifications' => 'boolean',
            'phone' => 'nullable|string|max:20',
            'whatsappProvider' => 'nullable|string|in:' . implode(',', $this->availableWhatsappProviders),
        ]);

        // Atualizar configurações básicas
        $this->user->update([
            'email_notifications' => $this->emailNotifications,
            'push_notifications' => $this->pushNotifications,
            'due_date_notifications' => $this->dueDateNotifications,
            'phone' => $this->phone,
        ]);

        // Atualizar configurações avançadas
        NotificationSetting::updateOrCreate(
            ['user_id' => $this->user->id],
            [
                'email_enabled' => $this->emailNotifications,
                'database_enabled' => true,
                'whatsapp_enabled' => $this->whatsappNotifications,
                'push_enabled' => $this->webNotifications,
                'whatsapp_provider' => $this->whatsappProvider,
            ]
        );

        // Se as notificações por WhatsApp estão habilitadas mas não há telefone, mostrar aviso
        if ($this->whatsappNotifications && empty($this->phone)) {
            $this->dispatch('notify', [
                'message' => 'Atenção: Você habilitou notificações por WhatsApp, mas não forneceu um número de telefone.',
                'type' => 'warning'
            ]);
        } else {
            $this->dispatch('notify', [
                'message' => 'Configurações de notificação salvas com sucesso!',
                'type' => 'success'
            ]);
        }
        
        $this->closeModal();
    }

    public function requestWebNotificationPermission()
    {
        $this->dispatch('requestNotificationPermission');
    }

    public function sendTestNotification()
    {
        $channels = [];
        
        if ($this->emailNotifications) {
            $channels[] = 'mail';
        }
        
        if ($this->whatsappNotifications && $this->phone) {
            $channels[] = 'whatsapp';
        }
        
        if ($this->webNotifications) {
            $channels[] = 'database';
            $this->dispatch('showWebNotification', [
                'title' => 'Teste de Notificação',
                'body' => 'Esta é uma notificação de teste do Onlifin.',
                'icon' => '/images/logo.png'
            ]);
        }
        
        if (!empty($channels)) {
            $this->user->notify(new \App\Notifications\TestNotification(
                $channels,
                'Teste de Notificação',
                'Esta é uma notificação de teste enviada em ' . now()->format('d/m/Y H:i:s')
            ));
            
            $this->testNotificationSent = true;
            $this->dispatch('notify', [
                'message' => 'Notificação de teste enviada com sucesso!',
                'type' => 'success'
            ]);
        } else {
            $this->dispatch('notify', [
                'message' => 'Ative pelo menos um canal de notificação para enviar o teste.',
                'type' => 'warning'
            ]);
        }
    }

    public function render()
    {
        return view('livewire.notification-settings');
    }
}
