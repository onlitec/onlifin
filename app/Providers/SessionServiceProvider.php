<?php

namespace App\Providers;

use App\Overrides\DatabaseSessionHandler;
use Illuminate\Session\SessionManager;
use Illuminate\Support\ServiceProvider;

class SessionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Extender a SessionManager para usar nossa implementação de DatabaseSessionHandler
        $this->app->extend('session.handler', function ($handler, $app) {
            // Verificar se o driver atual é database
            $manager = $app->make('session');
            $driver = $manager->getDefaultDriver();
            
            if ($driver === 'database') {
                // Obter as configurações de sessão necessárias
                $connection = $app->make('db')->connection(
                    $app['config']['session.connection'] ?? null
                );
                $table = $app['config']['session.table'];
                $minutes = $app['config']['session.lifetime'];
                
                // Retornar nova instância do nosso DatabaseSessionHandler personalizado
                return new DatabaseSessionHandler(
                    $connection, $table, $minutes, $app
                );
            }
            
            // Para outros drivers, retornar o handler original
            return $handler;
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
} 