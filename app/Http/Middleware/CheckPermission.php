<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
        if (!Auth::check()) {
            Log::warning('CheckPermission: Usuário não autenticado tentando acessar recurso protegido', [
                'permission' => $permission,
                'path' => $request->path()
            ]);
            
            return redirect()->route('login')
                ->with('error', 'Você precisa estar autenticado para acessar este recurso.');
        }

        $user = Auth::user();
        
        // Administradores têm acesso total
        if ($user->is_admin) {
            Log::info('CheckPermission: Administrador acessando recurso protegido', [
                'user_id' => $user->id,
                'permission' => $permission,
                'path' => $request->path()
            ]);
            
            return $next($request);
        }
        
        // Verificar se o usuário tem a permissão
        if ($user->hasPermission($permission)) {
            Log::info('CheckPermission: Acesso permitido', [
                'user_id' => $user->id,
                'permission' => $permission,
                'path' => $request->path()
            ]);
            
            return $next($request);
        }
        
        Log::warning('CheckPermission: Acesso negado', [
            'user_id' => $user->id,
            'permission' => $permission,
            'path' => $request->path()
        ]);
        
        return redirect()->back()
            ->with('error', 'Você não tem permissão para acessar este recurso.');
    }
} 