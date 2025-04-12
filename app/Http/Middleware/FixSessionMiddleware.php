<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixSessionMiddleware
{
    /**
     * Middleware para corrigir problemas de sessão.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Obter o ID da sessão
        $sessionId = $request->session()->getId();
        
        // Verificar se a sessão existe no banco de dados com escape adequado
        $sessionExists = DB::table('sessions')
            ->where('id', $sessionId)
            ->exists();
            
        // Se não existir, crie-a manualmente
        if (!$sessionExists) {
            Log::info('Sessão não encontrada. Recriando sessão: ' . $sessionId);
            
            // Crie uma nova sessão no banco de dados
            DB::table('sessions')->insert([
                'id' => $sessionId,
                'user_id' => $request->user() ? $request->user()->id : null,
                'ip_address' => $request->ip(),
                'user_agent' => substr($request->userAgent(), 0, 500),
                'payload' => base64_encode(serialize([])),
                'last_activity' => time()
            ]);
        }
        
        return $next($request);
    }
} 