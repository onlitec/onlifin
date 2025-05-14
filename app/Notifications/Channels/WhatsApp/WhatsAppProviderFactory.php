<?php

namespace App\Notifications\Channels\WhatsApp;

use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class WhatsAppProviderFactory
{
    /**
     * Mapeamento de provedores disponíveis
     *
     * @var array
     */
    protected static $providers = [
        'twilio' => TwilioWhatsAppProvider::class,
        'messagebird' => MessageBirdWhatsAppProvider::class,
        'mock' => MockWhatsAppProvider::class,
    ];
    
    /**
     * Cria uma instância do provedor WhatsApp com base na configuração
     *
     * @param string|null $provider Nome do provedor (se null, usa o padrão da configuração)
     * @return WhatsAppProviderInterface
     * @throws InvalidArgumentException
     */
    public static function create(?string $provider = null): WhatsAppProviderInterface
    {
        $config = config('notification-channels.whatsapp');
        
        // Se não for especificado um provedor, usa o padrão da configuração
        $providerName = $provider ?? $config['default'] ?? 'mock';
        
        // Verificar se o provedor existe
        if (!isset(self::$providers[$providerName])) {
            Log::warning("Provedor WhatsApp '{$providerName}' não encontrado. Usando o provedor Mock.");
            $providerName = 'mock';
        }
        
        $providerClass = self::$providers[$providerName];
        $providerConfig = $config['providers'][$providerName] ?? [];
        
        // Criar instância do provedor
        $provider = new $providerClass($providerConfig);
        
        // Verificar se o provedor está configurado
        if (!$provider->isConfigured()) {
            Log::warning("Provedor WhatsApp '{$providerName}' não está configurado corretamente. Usando o provedor Mock.");
            return new MockWhatsAppProvider($config['providers']['mock'] ?? ['enabled' => true]);
        }
        
        return $provider;
    }
    
    /**
     * Registra um novo provedor WhatsApp
     *
     * @param string $name Nome do provedor
     * @param string $class Nome completo da classe do provedor
     * @return void
     */
    public static function register(string $name, string $class): void
    {
        if (!class_exists($class) || !is_subclass_of($class, WhatsAppProviderInterface::class)) {
            throw new InvalidArgumentException("A classe '{$class}' não é uma implementação válida de WhatsAppProviderInterface");
        }
        
        self::$providers[$name] = $class;
    }
    
    /**
     * Retorna a lista de provedores disponíveis
     *
     * @return array
     */
    public static function getAvailableProviders(): array
    {
        return array_keys(self::$providers);
    }
}
