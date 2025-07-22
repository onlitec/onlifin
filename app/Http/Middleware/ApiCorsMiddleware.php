<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiCorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Lista de origens permitidas para o app Android
        $allowedOrigins = [
            'http://localhost',
            'http://127.0.0.1',
            'http://172.20.120.180:8080',
            'https://onlifin.onlitec.com.br',
            // Adicionar outras origens conforme necessário
        ];

        $origin = $request->header('Origin');

        // Para requisições OPTIONS (preflight)
        if ($request->getMethod() === 'OPTIONS') {
            $response = response('', 200);
        } else {
            $response = $next($request);
        }

        // Configurar headers CORS
        if (in_array($origin, $allowedOrigins) || $this->isAndroidApp($request)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin ?: '*');
        }

        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 
            'Content-Type, Authorization, X-Requested-With, Accept, Origin, Cache-Control, X-File-Name'
        );
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Max-Age', '86400'); // 24 horas

        return $response;
    }

    /**
     * Verificar se a requisição vem do app Android
     */
    private function isAndroidApp(Request $request): bool
    {
        $userAgent = $request->header('User-Agent', '');
        
        // Verificar se contém identificadores do app Android
        return str_contains($userAgent, 'OnlifinAndroid') || 
               str_contains($userAgent, 'okhttp') ||
               str_contains($userAgent, 'Android');
    }
}
