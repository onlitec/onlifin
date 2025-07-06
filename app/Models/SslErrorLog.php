<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SslErrorLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'action',
        'domain',
        'error_type',
        'error_message',
        'error_detail',
        'ip_address',
        'friendly_message',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Traduz mensagens de erro do Let's Encrypt para linguagem amigável
     */
    public static function translateError($errorMessage, $errorDetail = null): array
    {
        $type = 'unknown';
        $friendlyMessage = 'Ocorreu um erro ao processar o certificado SSL.';
        $ip = null;

        // Extrai IP do erro se presente
        if (preg_match('/(\d+\.\d+\.\d+\.\d+)/', $errorMessage, $matches)) {
            $ip = $matches[1];
        }

        // Detecta tipo de erro e traduz
        if (strpos($errorMessage, 'Invalid response') !== false && strpos($errorMessage, ': 500') !== false) {
            $type = 'server_error';
            $friendlyMessage = 'O servidor está retornando um erro interno (500) ao tentar validar o domínio. Isso geralmente ocorre quando:
• O diretório .well-known/acme-challenge não tem permissões adequadas
• O Laravel está interceptando a rota de validação
• Há um erro no código da aplicação';
        } elseif (strpos($errorMessage, 'unauthorized') !== false || strpos($errorMessage, ': 403') !== false) {
            $type = 'unauthorized';
            $friendlyMessage = 'Acesso negado ao tentar validar o domínio. Verifique:
• Se o domínio aponta corretamente para este servidor
• Se o firewall não está bloqueando o acesso
• Se as permissões dos arquivos estão corretas';
        } elseif (strpos($errorMessage, 'DNS problem') !== false) {
            $type = 'dns';
            $friendlyMessage = 'Problema com DNS. O domínio não está apontando para este servidor ou as configurações DNS ainda não propagaram.';
        } elseif (strpos($errorMessage, 'Connection refused') !== false) {
            $type = 'connection';
            $friendlyMessage = 'Não foi possível conectar ao servidor. Verifique se o servidor web está rodando e acessível na porta 80.';
        } elseif (strpos($errorMessage, 'password is required') !== false || strpos($errorMessage, 'terminal is required') !== false) {
            $type = 'permission';
            $friendlyMessage = 'Permissão negada. O sistema precisa executar o Certbot sem senha. Configure o arquivo /etc/sudoers.d/certbot conforme a documentação.';
        } elseif (strpos($errorMessage, 'Certificate not found') !== false) {
            $type = 'not_found';
            $friendlyMessage = 'Certificado não encontrado. Você precisa gerar um certificado primeiro antes de validar ou renovar.';
        } elseif (strpos($errorMessage, 'rate limit') !== false) {
            $type = 'rate_limit';
            $friendlyMessage = 'Limite de tentativas excedido. O Let\'s Encrypt permite apenas 5 tentativas por hora. Aguarde antes de tentar novamente.';
        }

        return [
            'type' => $type,
            'friendly_message' => $friendlyMessage,
            'ip' => $ip
        ];
    }

    /**
     * Cria log de erro com tradução automática
     */
    public static function logError($action, $domain, $errorMessage, $errorDetail = null, $metadata = [])
    {
        $translation = self::translateError($errorMessage, $errorDetail);
        
        return self::create([
            'action' => $action,
            'domain' => $domain,
            'error_type' => $translation['type'],
            'error_message' => $errorMessage,
            'error_detail' => $errorDetail,
            'ip_address' => $translation['ip'],
            'friendly_message' => $translation['friendly_message'],
            'metadata' => $metadata
        ]);
    }

    /**
     * Obtém últimos erros para um domínio
     */
    public static function getRecentErrors($domain, $limit = 5)
    {
        return self::where('domain', $domain)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Verifica se há erros recentes de rate limit
     */
    public static function hasRateLimitError($domain, $hoursAgo = 1)
    {
        return self::where('domain', $domain)
            ->where('error_type', 'rate_limit')
            ->where('created_at', '>=', now()->subHours($hoursAgo))
            ->exists();
    }
} 