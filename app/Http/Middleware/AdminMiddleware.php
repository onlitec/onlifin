<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            Log::warning('AdminMiddleware: Usuário não autenticado', [
                'path' => $request->path(),
                'method' => $request->method()
            ]);
            return redirect()->route('dashboard')->with('error', 'Você precisa estar autenticado para acessar esta página.');
        }
        
        $user = auth()->user();
        
        Log::info('AdminMiddleware: Verificando admin', [
            'user_id' => $user->id,
            'email' => $user->email,
            'is_admin' => $user->is_admin,
            'path' => $request->path()
        ]);
        
        if (!$user->is_admin) {
            Log::warning('AdminMiddleware: Acesso não autorizado', [
                'user_id' => $user->id,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
                'path' => $request->path()
            ]);
            return redirect()->route('dashboard')->with('error', 'Acesso não autorizado. Você precisa ser administrador.');
        }
        
        Log::info('AdminMiddleware: Acesso permitido', [
            'user_id' => $user->id,
            'path' => $request->path()
        ]);

        return $next($request);
    }
}