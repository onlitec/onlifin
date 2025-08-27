<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar se o usuário está autenticado
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Verificar se o usuário é administrador
        if (!Auth::user()->isAdmin()) {
            // Para requisições AJAX/API, retornar erro 403
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Acesso negado. Apenas administradores podem acessar este recurso.'], 403);
            }
            
            // Para requisições normais, redirecionar com erro
            return redirect()->back()->with('error', 'Acesso negado. Apenas administradores podem acessar este recurso.');
        }

        return $next($request);
    }
} 
