<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleUTF8Encoding
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Definir cabeçalhos para garantir codificação UTF-8
        $response = $next($request);
        
        if (method_exists($response, 'header')) {
            $response->header('Content-Type', 'text/html; charset=UTF-8');
        }
        
        return $response;
    }
    
    /**
     * Função para corrigir acentuação em uma string
     */
    public static function corrigirAcentuacao($texto)
    {
        if (empty($texto)) {
            return $texto;
        }
        
        // Se já for UTF-8 válido, retorna sem modificação
        if (mb_check_encoding($texto, 'UTF-8')) {
            return $texto;
        }
        
        // Detecta a codificação atual
        $encoding = mb_detect_encoding($texto, 'UTF-8, ISO-8859-1, ISO-8859-15', true);
        
        // Se não for UTF-8, converte para UTF-8
        if ($encoding && $encoding !== 'UTF-8') {
            $texto = mb_convert_encoding($texto, 'UTF-8', $encoding);
        }
        
        // Substituições diretas para casos comuns
        $substituicoes = [
            // Vogais acentuadas
            'Ã©' => 'é', 'Ã¡' => 'á', 'Ã³' => 'ó', 'Ãº' => 'ú', 'Ã­' => 'í',
            'Ãª' => 'ê', 'Ã¢' => 'â', 'Ã´' => 'ô', 'Ã£' => 'ã', 'Ãµ' => 'õ',
            'Ã‰' => 'É', 'Ã' => 'Á', 'Ã"' => 'Ó', 'Ãš' => 'Ú', 'Ã' => 'Í',
            'ÃŠ' => 'Ê', 'Ã‚' => 'Â', 'Ã"' => 'Ô', 'Ãƒ' => 'Ã', 'Ã•' => 'Õ',
            
            // Cedilha e outros caracteres especiais
            'Ã§' => 'ç', 'Ã‡' => 'Ç',
            
            // Ofuscação
            'â¢' => '*', 'â€¢' => '*'
        ];
        
        return str_replace(array_keys($substituicoes), array_values($substituicoes), $texto);
    }
} 