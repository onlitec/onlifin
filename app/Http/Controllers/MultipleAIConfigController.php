<?php

namespace App\Http\Controllers;

use App\Services\AIProviderConfigService;
use App\Services\AIProviderService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Exception;

class MultipleAIConfigController extends Controller
{
    private $aiProviderConfigService;
    private $aiProviderService;

    public function __construct()
    {
        $this->aiProviderConfigService = new AIProviderConfigService();
        $this->aiProviderService = new AIProviderService();
    }

    /**
     * Lista todos os provedores disponíveis
     *
     * @return JsonResponse
     */
    public function getProviders(): JsonResponse
    {
        try {
            $providers = $this->aiProviderService->getProviders();
            $stats = $this->aiProviderConfigService->getConfigurationStats();

            $result = [];
            foreach ($providers as $key => $provider) {
                $result[$key] = [
                    'key' => $key,
                    'name' => $provider['name'],
                    'models' => $provider['models'],
                    'endpoint' => $provider['endpoint'],
                    'stats' => $stats[$key] ?? [
                        'total_configurations' => 0,
                        'active_configurations' => 0,
                        'inactive_configurations' => 0,
                        'available_models' => count($provider['models'])
                    ]
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar provedores: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lista as configurações de um provedor específico
     *
     * @param string $provider
     * @return JsonResponse
     */
    public function getProviderConfigurations(string $provider): JsonResponse
    {
        try {
            if (!$this->aiProviderService->providerExists($provider)) {
                return response()->json([
                    'success' => false,
                    'message' => "Provedor '{$provider}' não existe"
                ], 404);
            }

            $configurations = $this->aiProviderConfigService->getProviderConfigurations($provider);
            $availableModels = $this->aiProviderService->getModels($provider);

            return response()->json([
                'success' => true,
                'data' => [
                    'provider' => $provider,
                    'provider_name' => $this->aiProviderService->getProvider($provider)['name'],
                    'configurations' => $configurations,
                    'available_models' => $availableModels
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar configurações: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Configura múltiplas IAs para um provedor
     *
     * @param Request $request
     * @param string $provider
     * @return JsonResponse
     */
    public function configureMultipleAIs(Request $request, string $provider): JsonResponse
    {
        try {
            // Validação básica
            $validator = Validator::make($request->all(), [
                'configurations' => 'required|array|min:1',
                'configurations.*.model' => 'required|string',
                'configurations.*.api_token' => 'required|string',
                'configurations.*.system_prompt' => 'nullable|string',
                'configurations.*.chat_prompt' => 'nullable|string',
                'configurations.*.import_prompt' => 'nullable|string',
                'configurations.*.is_active' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $configurations = $request->input('configurations');
            $result = $this->aiProviderConfigService->configureMultipleAIs($provider, $configurations);

            $statusCode = $result['total_errors'] > 0 ? 207 : 200; // 207 Multi-Status se houver erros parciais

            return response()->json([
                'success' => $result['total_configured'] > 0,
                'message' => $this->buildResultMessage($result),
                'data' => $result
            ], $statusCode);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao configurar IAs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ativa/desativa uma configuração específica
     *
     * @param Request $request
     * @param string $provider
     * @param string $model
     * @return JsonResponse
     */
    public function toggleConfiguration(Request $request, string $provider, string $model): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'is_active' => 'required|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $isActive = $request->input('is_active');
            $success = $this->aiProviderConfigService->toggleConfiguration($provider, $model, $isActive);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configuração não encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Configuração ' . ($isActive ? 'ativada' : 'desativada') . ' com sucesso'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao alterar configuração: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove uma configuração específica
     *
     * @param string $provider
     * @param string $model
     * @return JsonResponse
     */
    public function removeConfiguration(string $provider, string $model): JsonResponse
    {
        try {
            $success = $this->aiProviderConfigService->removeConfiguration($provider, $model);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configuração não encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Configuração removida com sucesso'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover configuração: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove todas as configurações de um provedor
     *
     * @param string $provider
     * @return JsonResponse
     */
    public function removeAllProviderConfigurations(string $provider): JsonResponse
    {
        try {
            $count = $this->aiProviderConfigService->removeAllProviderConfigurations($provider);

            return response()->json([
                'success' => true,
                'message' => "Removidas {$count} configurações do provedor {$provider}",
                'data' => ['removed_count' => $count]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover configurações: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Valida uma configuração antes de salvar
     *
     * @param Request $request
     * @param string $provider
     * @return JsonResponse
     */
    public function validateConfiguration(Request $request, string $provider): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'model' => 'required|string',
                'api_token' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $model = $request->input('model');
            $apiToken = $request->input('api_token');

            $result = $this->aiProviderConfigService->validateConfiguration($provider, $model, $apiToken);

            return response()->json([
                'success' => $result['valid'],
                'message' => $result['valid'] ? 'Configuração válida' : 'Configuração inválida',
                'data' => $result
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao validar configuração: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retorna estatísticas das configurações
     *
     * @return JsonResponse
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = $this->aiProviderConfigService->getConfigurationStats();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar estatísticas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Constrói mensagem de resultado baseada nos resultados da operação
     *
     * @param array $result
     * @return string
     */
    private function buildResultMessage(array $result): string
    {
        $messages = [];

        if ($result['total_configured'] > 0) {
            $messages[] = "{$result['total_configured']} configuração(ões) processada(s) com sucesso";
        }

        if ($result['total_errors'] > 0) {
            $messages[] = "{$result['total_errors']} erro(s) encontrado(s)";
        }

        return implode('. ', $messages);
    }
}