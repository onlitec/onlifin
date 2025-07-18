<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IncreaseTimeoutForAIAnalysis
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar se é uma rota de mapeamento com IA ativada
        if ($request->routeIs('mapping') && $request->get('use_ai') === '1') {
            // Aumentar o timeout do PHP para 5 minutos
            set_time_limit(300);
            
            // Log para debug
            \Log::info('Timeout aumentado para análise com IA', [
                'route' => $request->route()->getName(),
                'use_ai' => $request->get('use_ai'),
                'time_limit' => ini_get('max_execution_time')
            ]);
        }
        
        return $next($request);
    }
} 