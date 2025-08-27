<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

class SecurityServiceProvider extends ServiceProvider
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
        // Configurar limites de taxa para login
        RateLimiter::for('login', function (Request $request) {
            return [
                Limit::perMinute(5)->by($request->ip()),
                Limit::perMinute(30)->by($request->ip())->response(function () {
                    return response()->json(['error' => 'Muitas tentativas de login. Tente novamente mais tarde.'], 429);
                }),
            ];
        });

        // Configurar limites de taxa para registro
        RateLimiter::for('register', function (Request $request) {
            return [
                Limit::perMinute(3)->by($request->ip()),
                Limit::perMinute(10)->by($request->ip())->response(function () {
                    return response()->json(['error' => 'Muitas tentativas de registro. Tente novamente mais tarde.'], 429);
                }),
            ];
        });

        // Configurar limites de taxa para requisições de senha
        RateLimiter::for('password-reset', function (Request $request) {
            return [
                Limit::perMinute(2)->by($request->ip()),
                Limit::perMinute(10)->by($request->ip())->response(function () {
                    return response()->json(['error' => 'Muitas tentativas de recuperação de senha. Tente novamente mais tarde.'], 429);
                }),
            ];
        });
    }
}
