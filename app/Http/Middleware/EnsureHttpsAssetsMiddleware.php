<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class EnsureHttpsAssetsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar se deve forçar HTTPS
        $shouldForceHttps = env('FORCE_HTTPS', false) ||
                           str_starts_with(config('app.url'), 'https://') ||
                           config('app.env') === 'production';

        if ($shouldForceHttps) {
            // Forçar HTTPS para todas as URLs
            URL::forceScheme('https');
            
            // Configurar headers para HTTPS
            $request->server->set('HTTPS', 'on');
            $request->server->set('SERVER_PORT', 443);
            $request->server->set('HTTP_X_FORWARDED_PROTO', 'https');
            $request->server->set('HTTP_X_FORWARDED_SSL', 'on');
        }

        $response = $next($request);

        // Se a resposta contém HTML, substituir URLs HTTP por HTTPS
        if ($shouldForceHttps && $response instanceof \Illuminate\Http\Response) {
            $content = $response->getContent();
            
            if (is_string($content) && str_contains($content, 'text/html')) {
                // Substituir URLs HTTP do Livewire por HTTPS
                $baseUrl = config('app.url');
                $httpUrl = str_replace('https://', 'http://', $baseUrl);
                
                $content = str_replace($httpUrl, $baseUrl, $content);
                $response->setContent($content);
            }
        }

        return $response;
    }
}
