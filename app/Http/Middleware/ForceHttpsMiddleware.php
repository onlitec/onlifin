<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceHttpsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar se deve forçar HTTPS
        $shouldForceHttps = config('app.env') === 'production' || 
                           str_starts_with(config('app.url'), 'https://') ||
                           env('FORCE_HTTPS', false);

        if ($shouldForceHttps && !$request->secure()) {
            // Redirecionar para HTTPS se não estiver usando
            return redirect()->secure($request->getRequestUri(), 301);
        }

        // Configurar headers para HTTPS
        if ($shouldForceHttps) {
            $request->server->set('HTTPS', 'on');
            $request->server->set('SERVER_PORT', 443);
            $request->server->set('HTTP_X_FORWARDED_PROTO', 'https');
        }

        return $next($request);
    }
}
