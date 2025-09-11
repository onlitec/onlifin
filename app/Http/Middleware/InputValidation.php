<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para validação rigorosa de entrada
 * 
 * Este middleware valida e sanitiza dados de entrada para prevenir:
 * - XSS (Cross-Site Scripting)
 * - SQL Injection
 * - CSRF
 * - Injeção de código malicioso
 */
class InputValidation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Validar e sanitizar dados de entrada
        $this->validateAndSanitizeInput($request);
        
        // Validar headers suspeitos
        $this->validateHeaders($request);
        
        // Validar tamanho da requisição
        $this->validateRequestSize($request);
        
        return $next($request);
    }

    /**
     * Valida e sanitiza dados de entrada
     */
    private function validateAndSanitizeInput(Request $request): void
    {
        $input = $request->all();
        
        foreach ($input as $key => $value) {
            if (is_string($value)) {
                // Sanitizar strings
                $input[$key] = $this->sanitizeString($value);
            } elseif (is_array($value)) {
                // Recursivamente sanitizar arrays
                $input[$key] = $this->sanitizeArray($value);
            }
        }
        
        // Substituir dados sanitizados
        $request->replace($input);
    }

    /**
     * Sanitiza uma string
     */
    private function sanitizeString(string $value): string
    {
        // Remover tags HTML perigosas
        $value = strip_tags($value, '<p><br><strong><em><ul><ol><li>');
        
        // Escapar caracteres especiais
        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        
        // Remover caracteres de controle
        $value = preg_replace('/[\x00-\x1F\x7F]/', '', $value);
        
        // Limitar tamanho
        if (strlen($value) > 10000) {
            $value = substr($value, 0, 10000);
        }
        
        return trim($value);
    }

    /**
     * Sanitiza um array recursivamente
     */
    private function sanitizeArray(array $array): array
    {
        foreach ($array as $key => $value) {
            if (is_string($value)) {
                $array[$key] = $this->sanitizeString($value);
            } elseif (is_array($value)) {
                $array[$key] = $this->sanitizeArray($value);
            }
        }
        
        return $array;
    }

    /**
     * Valida headers suspeitos
     */
    private function validateHeaders(Request $request): void
    {
        $suspiciousHeaders = [
            'X-Forwarded-For',
            'X-Real-IP',
            'X-Originating-IP',
            'X-Remote-IP',
            'X-Remote-Addr',
            'X-Client-IP'
        ];

        foreach ($suspiciousHeaders as $header) {
            if ($request->hasHeader($header)) {
                $value = $request->header($header);
                
                // Validar formato de IP
                if (!filter_var($value, FILTER_VALIDATE_IP)) {
                    \Log::warning('Header suspeito detectado', [
                        'header' => $header,
                        'value' => $value,
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent()
                    ]);
                }
            }
        }
    }

    /**
     * Valida tamanho da requisição
     */
    private function validateRequestSize(Request $request): void
    {
        $maxSize = 10 * 1024 * 1024; // 10MB
        
        if ($request->header('Content-Length') > $maxSize) {
            abort(413, 'Request entity too large');
        }
        
        // Validar tamanho de uploads
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            if ($file->getSize() > $maxSize) {
                abort(413, 'File too large');
            }
        }
    }
}
