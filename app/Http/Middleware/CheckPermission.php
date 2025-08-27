<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // Verificar se o usuário está autenticado
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Verificar se o usuário tem a permissão necessária
        if (!Auth::user()->hasPermissionTo($permission)) {
            // Para requisições AJAX/API, retornar erro 403
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Acesso negado. Permissão insuficiente.'], 403);
            }
            
            // Para requisições normais, redirecionar com erro
            return redirect()->back()->with('error', 'Acesso negado. Você não tem permissão para realizar esta ação.');
        }

        return $next($request);
    }
} 
