<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;

class ApiResponseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Apenas processar respostas JSON da API
        if (!$request->is('api/*') || !$response instanceof JsonResponse) {
            return $response;
        }

        // Adicionar headers padrão para API
        $response->headers->set('X-API-Version', '1.0');
        $response->headers->set('X-Powered-By', 'Onlifin API');
        $response->headers->set('Content-Type', 'application/json; charset=utf-8');

        // Adicionar timestamp se não existir
        $data = $response->getData(true);
        if (!isset($data['timestamp'])) {
            $data['timestamp'] = now()->toISOString();
            $response->setData($data);
        }

        return $response;
    }
}
