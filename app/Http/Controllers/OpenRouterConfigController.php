<?php

namespace App\Http\Controllers;

use App\Models\OpenRouterConfig;
use App\Services\AIProviderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class OpenRouterConfigController extends Controller
{
    protected $aiProviderService;

    public function __construct(AIProviderService $aiProviderService)
    {
        $this->aiProviderService = $aiProviderService;
    }

    public function index()
    {
        $configs = OpenRouterConfig::all();
        return view('open-router-configs.index', compact('configs'));
    }

    public function create()
    {
        $providers = $this->aiProviderService->getProvidersForSelect();
        $models = $this->aiProviderService->getProviders();

        return view('open-router-configs.form', compact('providers', 'models'));
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

        // Se endpoint não foi fornecido, usar o padrão do provedor
        if (empty($validated['endpoint'])) {
            $validated['endpoint'] = $this->aiProviderService->getEndpoint($validated['provider']);
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
        $openRouterConfig = OpenRouterConfig::findOrFail($id);
        $providers = $this->aiProviderService->getProvidersForSelect();
        $models = $this->aiProviderService->getProviders();

        return view('open-router-configs.form', compact('openRouterConfig', 'providers', 'models'));
    }

    public function update(Request $request, $id)
    {
        $openRouterConfig = OpenRouterConfig::findOrFail($id);
        
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

        // Se endpoint não foi fornecido, usar o padrão do provedor
        if (empty($validated['endpoint'])) {
            $validated['endpoint'] = $this->aiProviderService->getEndpoint($validated['provider']);
        }

        // Se system_prompt estiver vazio, copiar chat_prompt para ele (compatibilidade)
        if (empty($validated['system_prompt']) && !empty($validated['chat_prompt'])) {
            $validated['system_prompt'] = $validated['chat_prompt'];
        }

        $openRouterConfig->update($validated);

        return redirect()->route('iaprovider-config.index')
            ->with('success', 'Configuração de IA atualizada com sucesso!');
    }

    public function destroy(OpenRouterConfig $openRouterConfig)
    {
        $openRouterConfig->delete();

        return redirect()->route('iaprovider-config.index')
            ->with('success', 'Configuração excluída com sucesso.');
    }

    public function testConnection(Request $request)
    {
        $validator = Validator::make($request->all(), [
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

        try {
            switch (strtolower($request->provider)) {
                case 'openrouter':
                    // Teste original do OpenRouter
                    $endpoint = $request->endpoint ?: 'https://openrouter.ai/api/v1';
                    $model = $request->model;
                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $request->api_key,
                        'Content-Type' => 'application/json',
                        'HTTP-Referer' => config('app.url')
                    ])->post($endpoint . '/chat/completions', [
                        'model' => $model,
                        'messages' => [
                            ['role' => 'system', 'content' => 'Você é um assistente útil.'],
                            ['role' => 'user', 'content' => 'Olá! Este é um teste de conexão.']
                        ],
                        'max_tokens' => 10
                    ]);

                    if ($response->successful()) {
                        Log::info('Teste de conexão com provedor de IA bem-sucedido', [
                            'provider' => $request->provider,
                            'model' => $model
                        ]);
                        return response()->json([
                            'success' => true,
                            'message' => 'Conexão estabelecida com sucesso!'
                        ]);
                    }
                    $errorMessage = $response->json('error.message') ?? $response->body();
                    Log::error('Erro ao testar conexão com provedor de IA', [
                        'status' => $response->status(),
                        'error' => $errorMessage
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Erro na resposta da API: ' . $errorMessage
                    ], 422);

                default:
                    // Teste genérico para outros provedores via AIService
                    $normalizedProvider = $this->aiProviderService->normalizeProvider($request->provider);
                    $aiService = new AIService(
                        $normalizedProvider,
                        $request->model,
                        $request->api_key,
                        $request->endpoint ?? null,
                        null, // systemPrompt
                        null, // chatPrompt
                        null  // importPrompt
                    );
                    try {
                        $testResult = $aiService->test();
                    } catch (\Exception $e) {
                        Log::error("Erro ao testar conexão com {$request->provider}", ['error' => $e->getMessage()]);
                        return response()->json([
                            'success' => false,
                            'message' => 'Erro ao testar conexão: ' . $e->getMessage()
                        ], 500);
                    }
                    if ($testResult === true) {
                        Log::info("Teste de conexão com {$request->provider} bem-sucedido");
                        return response()->json([
                            'success' => true,
                            'message' => 'Conexão estabelecida com sucesso!'
                        ]);
                    }
                    if (is_array($testResult) && isset($testResult['message'])) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Erro ao testar conexão: ' . $testResult['message']
                        ], 422);
                    }
                    return response()->json([
                        'success' => false,
                        'message' => 'Falha no teste de conexão.'
                    ], 500);
            }
        } catch (\Exception $e) {
            Log::error("Exceção ao testar conexão com {$request->provider}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao testar conexão: ' . $e->getMessage()
            ], 500);
        }
    }
}
