<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Notification;
use App\Notifications\Channels\WhatsAppChannel;
use App\Notifications\Channels\WhatsApp\WhatsAppProviderFactory;

class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Registrar o singleton da fábrica de provedores WhatsApp
        $this->app->singleton('whatsapp.provider', function ($app) {
            $providerName = config('notification-channels.whatsapp.default');
            return WhatsAppProviderFactory::create($providerName);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Registrar o canal de notificação WhatsApp
        Notification::extend('whatsapp', function ($app) {
            $providerName = config('notification-channels.whatsapp.default');
            return new WhatsAppChannel($providerName);
        });
    }
}
