<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

class LivewireServiceProvider extends ServiceProvider
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
        // Configurar Livewire para usar HTTPS quando necessário
        if ($this->shouldForceHttps()) {
            $this->configureLivewireForHttps();
        }

        // Configurar assets do Livewire para usar arquivos locais
        $this->configureLivewireAssets();
    }

    /**
     * Determina se deve forçar HTTPS
     */
    private function shouldForceHttps(): bool
    {
        return env('FORCE_HTTPS', false) ||
               str_starts_with(config('app.url'), 'https://') ||
               config('app.env') === 'production';
    }

    /**
     * Configura o Livewire para usar HTTPS
     */
    private function configureLivewireForHttps(): void
    {
        // Forçar HTTPS para todas as URLs
        URL::forceScheme('https');

        // Configurar URLs base para HTTPS
        $this->app['url']->forceScheme('https');

        // Configurar request para HTTPS
        if ($this->app['request']->server) {
            $this->app['request']->server->set('HTTPS', 'on');
            $this->app['request']->server->set('SERVER_PORT', 443);
            $this->app['request']->server->set('HTTP_X_FORWARDED_PROTO', 'https');
        }
    }

    /**
     * Configura os assets do Livewire
     */
    private function configureLivewireAssets(): void
    {
        // Configurar o Livewire para usar assets publicados
        if (file_exists(public_path('vendor/livewire/livewire.js'))) {
            // Usar assets locais publicados
            Livewire::setScriptRoute(function ($handle) {
                return \Illuminate\Support\Facades\Route::get('/vendor/livewire/livewire.js', function () {
                    return response()->file(public_path('vendor/livewire/livewire.js'), [
                        'Content-Type' => 'application/javascript',
                        'Cache-Control' => 'public, max-age=31536000',
                    ]);
                });
            });
        }
    }
}
