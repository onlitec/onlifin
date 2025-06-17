<?php

/*
 * ========================================================================
 * ARQUIVO PROTEGIDO - MODIFICAÇÕES REQUEREM AUTORIZAÇÃO EXPLÍCITA
 * ========================================================================
 * 
 * ATENÇÃO: Este arquivo contém código crítico para o funcionamento do sistema.
 * Qualquer modificação deve ser previamente autorizada e documentada.
 * 
 * Responsável: Equipe de Desenvolvimento
 * Última modificação autorizada: 2025-05-28
 * 
 * Para solicitar modificações, entre em contato com a equipe responsável.
 * ========================================================================
 */

namespace App\Http\Controllers;

use App\Models\OpenRouterConfig;
use App\Models\ModelApiKey;
use App\Services\AIProviderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AIProviderConfigController extends Controller
{
    protected $aiProviderService;

    public function __construct(AIProviderService $aiProviderService)
    {
        $this->aiProviderService = $aiProviderService;
    }

    public function index()
    {
        // Buscar configurações de ambas as tabelas e combinar os resultados
        $openRouterConfigs = OpenRouterConfig::all();
        $modelApiKeys = ModelApiKey::all();
        
        // Converter ModelApiKey para o formato compatível com OpenRouterConfig
        $convertedModelApiKeys = $modelApiKeys->map(function ($item) {
            return (object)[
                'id' => $item->id,
                'provider' => $item->provider,
                'model' => $item->model,
                'api_key' => $item->api_token,
                'endpoint' => null, // ModelApiKey não tem endpoint
                'system_prompt' => $item->system_prompt,
                'chat_prompt' => $item->chat_prompt,
                'import_prompt' => $item->import_prompt,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at
            ];
        });
        
        // Combinar as coleções
        $configs = $openRouterConfigs->concat($convertedModelApiKeys);
        $providers = $this->aiProviderService->getProvidersForSelect();
        
        return view('ai-provider-configs.index', compact('configs', 'providers'));
    }

    public function create()
    {
        $providers = $this->aiProviderService->getProvidersForSelect();
        $models = $this->aiProviderService->getProviders();

        $totalConfigs = OpenRouterConfig::count();
        $activeProviders = OpenRouterConfig::distinct('provider')->count();
        $uniqueModels = OpenRouterConfig::distinct('model')->count();
        
        $mostUsedProvider = OpenRouterConfig::select('provider')
            ->groupBy('provider')
            ->orderByRaw('COUNT(*) DESC')
            ->first();

        $mostUsedProviderName = $mostUsedProvider ? $mostUsedProvider->provider : 'N/A';

        return view('ai-provider-configs.form', compact('providers', 'models', 'totalConfigs', 'activeProviders', 'uniqueModels', 'mostUsedProviderName'));
    }

    public function store(Request $request)
    {

        $validated = $request->validate([
            'provider' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'api_key' => 'required|string|max:255',
            'endpoint' => 'nullable|string|max:255',
            'system_prompt' => 'nullable|string',
            'chat_prompt' => 'nullable|string',
            'import_prompt' => 'nullable|string',
        ]);

        // Normalizar o provedor
        $validated['provider'] = $this->aiProviderService->normalizeProvider($validated['provider']);

        // Verificar se o provedor existe
        if (!$this->aiProviderService->providerExists($validated['provider'])) {
            return back()->withErrors(['provider' => 'Provedor não suportado.'])->withInput();
        }

        // Verificar se o modelo existe para o provedor (exceto OpenRouter)
        if ($validated['provider'] !== 'openrouter' && !$this->aiProviderService->modelExists($validated['provider'], $validated['model'])) {
            return back()->withErrors(['model' => 'Modelo não suportado para este provedor.'])->withInput();
        }

        // Se system_prompt estiver vazio, copiar chat_prompt para ele (compatibilidade)
        if (empty($validated['system_prompt']) && !empty($validated['chat_prompt'])) {
            $validated['system_prompt'] = $validated['chat_prompt'];
        }

        OpenRouterConfig::create($validated);

        return redirect()->route('iaprovider-config.index')
            ->with('success', 'Configuração de IA criada com sucesso!');
    }

    public function edit($id)
    {
        $config = OpenRouterConfig::findOrFail($id);
        $providers = $this->aiProviderService->getProvidersForSelect();
        $models = $this->aiProviderService->getProviders();

        $totalConfigs = OpenRouterConfig::count();
        $activeProviders = OpenRouterConfig::distinct('provider')->count();
        $uniqueModels = OpenRouterConfig::distinct('model')->count();
        
        $mostUsedProvider = OpenRouterConfig::select('provider')
            ->groupBy('provider')
            ->orderByRaw('COUNT(*) DESC')
            ->first();

        $mostUsedProviderName = $mostUsedProvider ? $mostUsedProvider->provider : 'N/A';

        return view('ai-provider-configs.form', compact('config', 'providers', 'models', 'totalConfigs', 'activeProviders', 'uniqueModels', 'mostUsedProviderName'));
    }

    public function update(Request $request, OpenRouterConfig $config)
    {

        

        $validated = $request->validate([
            'provider' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'api_key' => 'required|string|max:255',
            'endpoint' => 'nullable|string|max:255',
            'system_prompt' => 'nullable|string',
            'chat_prompt' => 'nullable|string',
            'import_prompt' => 'nullable|string',
        ]);

        // Normalizar o provedor
        $validated['provider'] = $this->aiProviderService->normalizeProvider($validated['provider']);

        // Verificar se o provedor existe
        if (!$this->aiProviderService->providerExists($validated['provider'])) {
            return back()->withErrors(['provider' => 'Provedor não suportado.'])->withInput();
        }

        // Verificar se o modelo existe para o provedor (exceto OpenRouter)
        if ($validated['provider'] !== 'openrouter' && !$this->aiProviderService->modelExists($validated['provider'], $validated['model'])) {
            return back()->withErrors(['model' => 'Modelo não suportado para este provedor.'])->withInput();
        }




        // Se system_prompt estiver vazio, copiar chat_prompt para ele (compatibilidade)
        if (empty($validated['system_prompt']) && !empty($validated['chat_prompt'])) {
            $validated['system_prompt'] = $validated['chat_prompt'];
        }

        $config->update($validated);

        return redirect()->route('iaprovider-config.index')
            ->with('success', 'Configuração de IA atualizada com sucesso!');
    }

    public function destroy($id)
    {
        try {
            // Verificar se existe uma configuração do tipo OpenRouterConfig
            $config = OpenRouterConfig::find($id);
            
            if ($config) {
                $config->delete();
                return redirect()->route('iaprovider-config.index')
                    ->with('success', 'Configuração excluída com sucesso.');
            }
            
            // Se não encontrou OpenRouterConfig, verificar se é ModelApiKey
            $modelApiKey = ModelApiKey::find($id);
            if ($modelApiKey) {
                $modelApiKey->delete();
                return redirect()->route('iaprovider-config.index')
                    ->with('success', 'Configuração excluída com sucesso.');
            }
            
            // Se não encontrou nenhum dos dois, retornar erro
            return redirect()->route('iaprovider-config.index')
                ->with('error', 'Configuração não encontrada.');
        } catch (\Exception $e) {
            Log::error('Erro ao excluir configuração de IA', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('iaprovider-config.index')
                ->with('error', 'Erro ao excluir configuração: ' . $e->getMessage());
        }
    }

    public function testConnection(Request $request)
    {
        $data = $request->all();
        $configId = $request->input('config_id');

        if ($configId) {
            $config = OpenRouterConfig::find($configId);
            if (!$config) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configuração não encontrada.'
                ], 404);
            }
            $provider = $config->provider;
            $model = $config->model;
            $apiKey = $config->api_key;
            $endpoint = $config->endpoint;
        } else {
            // Limpar endpoint vazio para evitar erro de validação
            if (isset($data['endpoint']) && empty(trim($data['endpoint']))) {
                $data['endpoint'] = null;
            }
            
            $validator = Validator::make($data, [
                'provider' => 'required|string',
                'model' => 'required|string',
                'api_key' => 'required|string',
                'endpoint' => 'nullable|url',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos: ' . $validator->errors()->first()
                ], 400);
            }

            $provider = $request->input('provider');
            $model = $request->input('model');
            $apiKey = $request->input('api_key');
            $endpoint = $request->input('endpoint');
        }

        // Normalizar o provedor
        $provider = $this->aiProviderService->normalizeProvider($provider);

        // Se endpoint não foi fornecido, usar o padrão do provedor
        if (empty($endpoint)) {
            $endpoint = $this->aiProviderService->getEndpoint($provider);
        }

        try {
            $success = $this->testProviderConnection($provider, $model, $apiKey, $endpoint);
            
            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Conexão estabelecida com sucesso!'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Falha na conexão com o provedor.'
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Erro ao testar conexão com provedor de IA', [
                'provider' => $provider,
                'model' => $model,
                'endpoint' => $endpoint,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao testar conexão: ' . $e->getMessage()
            ], 500);
        }
    }

    private function testProviderConnection($provider, $model, $apiKey, $endpoint)
    {
        $testMessage = 'Teste de conexão. Responda apenas "OK" se receber esta mensagem.';
        
        switch ($provider) {
            case 'openai':
                return $this->testOpenAI($model, $apiKey, $endpoint, $testMessage);
            case 'anthropic':
                return $this->testAnthropic($model, $apiKey, $endpoint, $testMessage);
            case 'gemini':
                return $this->testGemini($model, $apiKey, $endpoint, $testMessage);
            case 'deepseek':
                return $this->testDeepSeek($model, $apiKey, $endpoint, $testMessage);
            case 'qwen':
                return $this->testQwen($model, $apiKey, $endpoint, $testMessage);
            case 'openrouter':
                return $this->testOpenRouter($model, $apiKey, $endpoint, $testMessage);
            default:
                throw new \Exception('Provedor não suportado para teste: ' . $provider);
        }
    }

    private function testOpenAI($model, $apiKey, $endpoint, $message)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post($endpoint, [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $message]
            ],
            'max_tokens' => 10
        ]);

        return $response->successful();
    }

    private function testAnthropic($model, $apiKey, $endpoint, $message)
    {
        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'Content-Type' => 'application/json',
            'anthropic-version' => '2023-06-01'
        ])->post($endpoint, [
            'model' => $model,
            'max_tokens' => 10,
            'messages' => [
                ['role' => 'user', 'content' => $message]
            ]
        ]);

        return $response->successful();
    }

    private function testGemini($model, $apiKey, $endpoint, $message)
    {
        $url = str_replace('{model}', $model, $endpoint) . '?key=' . $apiKey;
        
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $message]
                    ]
                ]
            ]
        ]);

        return $response->successful();
    }

    private function testDeepSeek($model, $apiKey, $endpoint, $message)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post($endpoint, [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $message]
            ],
            'max_tokens' => 10
        ]);

        return $response->successful();
    }

    private function testQwen($model, $apiKey, $endpoint, $message)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post($endpoint, [
            'model' => $model,
            'input' => [
                'messages' => [
                    ['role' => 'user', 'content' => $message]
                ]
            ],
            'parameters' => [
                'max_tokens' => 10
            ]
        ]);

        return $response->successful();
    }

    private function testOpenRouter($model, $apiKey, $endpoint, $message)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post($endpoint, [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $message]
            ],
            'max_tokens' => 10
        ]);

        return $response->successful();
    }
}