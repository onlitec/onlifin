<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class HttpsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Forçar HTTPS em produção e quando APP_URL usar HTTPS
        if ($this->app->environment('production') || 
            str_starts_with(config('app.url'), 'https://')) {
            
            URL::forceScheme('https');
            
            // Configurar proxy headers para HTTPS
            $this->app['request']->server->set('HTTPS', 'on');
            $this->app['request']->server->set('SERVER_PORT', 443);
            $this->app['request']->server->set('HTTP_X_FORWARDED_PROTO', 'https');
        }
    }
}
