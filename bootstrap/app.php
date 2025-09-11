<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Middleware global para segurança
        $middleware->web(prepend: [
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\InputValidation::class,
            \App\Http\Middleware\ForceHttpsMiddleware::class,
            \App\Http\Middleware\EnsureHttpsAssetsMiddleware::class,
        ]);

        $middleware->api(prepend: [
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\InputValidation::class,
            \App\Http\Middleware\ForceHttpsMiddleware::class,
            \App\Http\Middleware\ApiCorsMiddleware::class,
            \App\Http\Middleware\ApiResponseMiddleware::class,
        ]);
    })
    ->withProviders([
        \App\Providers\HttpsServiceProvider::class,
    ])
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
