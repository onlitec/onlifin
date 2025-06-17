<?php

namespace App\Services;

use App\Models\ModelApiKey;
use App\Services\AIProviderService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Exception;

class AIProviderConfigService
{
    private $aiProviderService;

    public function __construct()
    {
        $this->aiProviderService = new AIProviderService();
    }

    /**
     * Configura múltiplas IAs para um provedor específico
     *
     * @param string $provider Nome do provedor (gemini, openai, anthropic, etc.)
     * @param array $configurations Array de configurações no formato:
     * [
     *   [
     *     'model' => 'nome-do-modelo',
     *     'api_token' => 'chave-api',
     *     'system_prompt' => 'prompt do sistema (opcional)',
     *     'chat_prompt' => 'prompt para chat (opcional)',
     *     'import_prompt' => 'prompt para importação (opcional)',
     *     'is_active' => true/false (opcional, padrão: true)
     *   ],
     *   ...
     * ]
     * @return array Resultado da operação com sucessos e erros
     */
    public function configureMultipleAIs(string $provider, array $configurations): array
    {
        $results = [
            'success' => [],
            'errors' => [],
            'provider' => $provider,
            'total_configured' => 0,
            'total_errors' => 0
        ];

        // Validar se o provedor existe
        if (!$this->aiProviderService->providerExists($provider)) {
            $results['errors'][] = "Provedor '{$provider}' não existe";
            $results['total_errors'] = 1;
            return $results;
        }

        $availableModels = $this->aiProviderService->getModels($provider);

        foreach ($configurations as $index => $config) {
            try {
                $result = $this->configureSingleAI($provider, $config, $availableModels, $index);
                
                if ($result['success']) {
                    $results['success'][] = $result;
                    $results['total_configured']++;
                } else {
                    $results['errors'][] = $result;
                    $results['total_errors']++;
                }
            } catch (Exception $e) {
                $results['errors'][] = [
                    'index' => $index,
                    'error' => $e->getMessage(),
                    'config' => $config
                ];
                $results['total_errors']++;
            }
        }

        Log::info('Configuração múltipla de IAs concluída', [
            'provider' => $provider,
            'total_configured' => $results['total_configured'],
            'total_errors' => $results['total_errors']
        ]);

        return $results;
    }

    /**
     * Configura uma única IA
     *
     * @param string $provider
     * @param array $config
     * @param array $availableModels
     * @param int $index
     * @return array
     */
    private function configureSingleAI(string $provider, array $config, array $availableModels, int $index): array
    {
        // Validações básicas
        if (empty($config['model'])) {
            return [
                'success' => false,
                'index' => $index,
                'error' => 'Campo model é obrigatório',
                'config' => $config
            ];
        }

        if (empty($config['api_token'])) {
            return [
                'success' => false,
                'index' => $index,
                'error' => 'Campo api_token é obrigatório',
                'config' => $config
            ];
        }

        $model = $config['model'];

        // Validar se o modelo existe para o provedor
        if (!isset($availableModels[$model])) {
            return [
                'success' => false,
                'index' => $index,
                'error' => "Modelo '{$model}' não existe para o provedor '{$provider}'",
                'config' => $config,
                'available_models' => array_keys($availableModels)
            ];
        }

        // Verificar se já existe uma configuração para este provedor/modelo
        $existingConfig = ModelApiKey::where('provider', $provider)
            ->where('model', $model)
            ->first();

        $data = [
            'provider' => $provider,
            'model' => $model,
            'api_token' => $config['api_token'],
            'system_prompt' => $config['system_prompt'] ?? null,
            'chat_prompt' => $config['chat_prompt'] ?? $config['system_prompt'] ?? null,
            'import_prompt' => $config['import_prompt'] ?? null,
            'is_active' => $config['is_active'] ?? true
        ];

        if ($existingConfig) {
            // Atualizar configuração existente
            $existingConfig->update($data);
            $action = 'updated';
            $modelApiKey = $existingConfig;
        } else {
            // Criar nova configuração
            $modelApiKey = ModelApiKey::create($data);
            $action = 'created';
        }

        return [
            'success' => true,
            'index' => $index,
            'action' => $action,
            'provider' => $provider,
            'model' => $model,
            'model_name' => $availableModels[$model],
            'id' => $modelApiKey->id,
            'is_active' => $modelApiKey->is_active
        ];
    }

    /**
     * Lista todas as configurações de um provedor
     *
     * @param string $provider
     * @return Collection
     */
    public function getProviderConfigurations(string $provider): Collection
    {
        return ModelApiKey::where('provider', $provider)
            ->orderBy('model')
            ->get();
    }

    /**
     * Lista todas as configurações ativas de um provedor
     *
     * @param string $provider
     * @return Collection
     */
    public function getActiveProviderConfigurations(string $provider): Collection
    {
        return ModelApiKey::where('provider', $provider)
            ->where('is_active', true)
            ->orderBy('model')
            ->get();
    }

    /**
     * Ativa/desativa uma configuração específica
     *
     * @param string $provider
     * @param string $model
     * @param bool $isActive
     * @return bool
     */
    public function toggleConfiguration(string $provider, string $model, bool $isActive): bool
    {
        $config = ModelApiKey::where('provider', $provider)
            ->where('model', $model)
            ->first();

        if (!$config) {
            return false;
        }

        $config->update(['is_active' => $isActive]);
        
        Log::info('Configuração de IA alterada', [
            'provider' => $provider,
            'model' => $model,
            'is_active' => $isActive
        ]);

        return true;
    }

    /**
     * Remove uma configuração específica
     *
     * @param string $provider
     * @param string $model
     * @return bool
     */
    public function removeConfiguration(string $provider, string $model): bool
    {
        $config = ModelApiKey::where('provider', $provider)
            ->where('model', $model)
            ->first();

        if (!$config) {
            return false;
        }

        $config->delete();
        
        Log::info('Configuração de IA removida', [
            'provider' => $provider,
            'model' => $model
        ]);

        return true;
    }

    /**
     * Remove todas as configurações de um provedor
     *
     * @param string $provider
     * @return int Número de configurações removidas
     */
    public function removeAllProviderConfigurations(string $provider): int
    {
        $count = ModelApiKey::where('provider', $provider)->count();
        ModelApiKey::where('provider', $provider)->delete();
        
        Log::info('Todas as configurações do provedor removidas', [
            'provider' => $provider,
            'count' => $count
        ]);

        return $count;
    }

    /**
     * Valida uma configuração antes de salvar
     *
     * @param string $provider
     * @param string $model
     * @param string $apiToken
     * @return array
     */
    public function validateConfiguration(string $provider, string $model, string $apiToken): array
    {
        $result = [
            'valid' => false,
            'errors' => []
        ];

        // Validar provedor
        if (!$this->aiProviderService->providerExists($provider)) {
            $result['errors'][] = "Provedor '{$provider}' não existe";
        }

        // Validar modelo
        if (!$this->aiProviderService->modelExists($provider, $model)) {
            $result['errors'][] = "Modelo '{$model}' não existe para o provedor '{$provider}'";
        }

        // Validar API token
        if (empty($apiToken)) {
            $result['errors'][] = 'API token é obrigatório';
        }

        $result['valid'] = empty($result['errors']);
        return $result;
    }

    /**
     * Obtém estatísticas das configurações de IA
     *
     * @return array
     */
    public function getConfigurationStats(): array
    {
        $stats = [];
        $aiProviderService = new AIProviderService();
        $providers = $aiProviderService->getProviders();
        
        foreach ($providers as $providerKey => $provider) {
            $stats[$providerKey] = [
                'available_models' => count($provider['models']),
                'total_configurations' => 0,
                'active_configurations' => 0,
                'inactive_configurations' => 0,
                'configured_models' => []
            ];
        }
        
        // Buscar todas as configurações e agrupá-las por provedor
        $allConfigs = ModelApiKey::all();
        
        foreach ($allConfigs as $config) {
            $provider = $config->provider;
            
            if (isset($stats[$provider])) {
                $stats[$provider]['total_configurations']++;
                
                if ($config->is_active) {
                    $stats[$provider]['active_configurations']++;
                } else {
                    $stats[$provider]['inactive_configurations']++;
                }
                
                // Registrar modelo configurado
                if (!in_array($config->model, $stats[$provider]['configured_models'])) {
                    $stats[$provider]['configured_models'][] = $config->model;
                }
                
                // Se for um modelo tipo openrouter/anthropic/claude-3-opus, o modelo completo está no registro
                if (strpos($config->model, '/') !== false) {
                    list($modelProvider, $modelName) = explode('/', $config->model, 2);
                    // Adicionar um marcador para mostrar que este provedor tem modelos de outros provedores
                    $stats[$provider]['has_external_models'] = true;
                    $stats[$provider]['external_models'][$modelProvider][] = $modelName;
                }
            }
        }
        
        return $stats;
    }
}