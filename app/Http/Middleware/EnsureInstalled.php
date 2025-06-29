<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\File;

class EnsureInstalled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Permite acesso à rota de instalação
        if ($request->routeIs('install')) {
            return $next($request);
        }

        // Verifica se o sistema já foi instalado
        if (!File::exists(storage_path('installed.flag'))) {
            return redirect()->route('install');
        }

        return $next($request);
    }
} 