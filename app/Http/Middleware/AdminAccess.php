<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Define uma variável global para os controladores saberem se o usuário é admin
        if (auth()->check() && auth()->user()->is_admin) {
            app()->instance('admin_override', true);
        } else {
            app()->instance('admin_override', false);
        }
        
        return $next($request);
    }
} 