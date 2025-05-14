<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB; // Para salvar em banco de dados, assumindo uma tabela 'config'
use Illuminate\Support\Facades\Log;
use App\Models\ReplicateSetting;
use App\Services\AIService;
use App\Models\OpenRouterConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class OpenRouterConfigController extends Controller
{
    protected $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    // Middleware auth está definido nas rotas

    public function index()
    {
        $configs = OpenRouterConfig::all();
        $providers = [
            'openrouter' => [
                'name' => 'OpenRouter',
                'models' => [
                    'openai/gpt-4-turbo-preview' => 'GPT-4 Turbo',
                    'openai/gpt-4' => 'GPT-4',
                    'openai/gpt-3.5-turbo' => 'GPT-3.5 Turbo',
                    'anthropic/claude-3-opus' => 'Claude 3 Opus',
                    'anthropic/claude-3-sonnet' => 'Claude 3 Sonnet',
                    'anthropic/claude-2' => 'Claude 2',
                    'google/gemini-pro' => 'Gemini Pro',
                    'meta-llama/llama-2-70b-chat' => 'Llama 2 70B',
                ]
            ],
            'openai' => [
                'name' => 'OpenAI',
                'models' => [
                    'gpt-4-turbo-preview' => 'GPT-4 Turbo',
                    'gpt-4' => 'GPT-4',
                    'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
                ]
            ],
            'anthropic' => [
                'name' => 'Anthropic',
                'models' => [
                    'claude-3-opus' => 'Claude 3 Opus',
                    'claude-3-sonnet' => 'Claude 3 Sonnet',
                    'claude-2' => 'Claude 2',
                ]
            ],
            'google' => [
                'name' => 'Google',
                'models' => [
                    'gemini-pro' => 'Gemini Pro',
                ]
            ],
            'meta' => [
                'name' => 'Meta',
                'models' => [
                    'llama-2-70b-chat' => 'Llama 2 70B',
                ]
            ]
        ];

        return view('settings.model-keys', compact('configs', 'providers'));
    }

    public function create()
    {
        $providers = [
            'openai' => [
                'name' => 'OpenAI',
                'models' => []
            ],
            'anthropic' => [
                'name' => 'Anthropic',
                'models' => []
            ],
            'google' => [
                'name' => 'Google',
                'models' => []
            ],
            'openrouter' => [
                'name' => 'OpenRouter',
                'models' => []
            ]
        ];

        return view('open-router-configs.form', compact('providers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'provider' => 'required|string',
            'model' => 'required|string',
            'api_key' => 'required|string',
            'endpoint' => 'nullable|string|url',
            'system_prompt' => 'nullable|string',
        ]);

        OpenRouterConfig::create($validated);

        return redirect()->route('openrouter-config.index')
            ->with('success', 'Configuração criada com sucesso.');
    }

    public function edit($id)
    {
        $openRouterConfig = OpenRouterConfig::findOrFail($id);
        
        $providers = [
            'openai' => [
                'name' => 'OpenAI',
                'models' => []
            ],
            'anthropic' => [
                'name' => 'Anthropic',
                'models' => []
            ],
            'google' => [
                'name' => 'Google',
                'models' => []
            ],
            'openrouter' => [
                'name' => 'OpenRouter',
                'models' => []
            ]
        ];

        return view('open-router-configs.form', [
            'config' => $openRouterConfig,
            'providers' => $providers
        ]);
    }

    public function update(Request $request, $id)
    {
        $openRouterConfig = OpenRouterConfig::findOrFail($id);
        
        $validated = $request->validate([
            'provider' => 'required|string',
            'model' => 'required|string',
            'api_key' => 'required|string',
            'endpoint' => 'nullable|string|url',
            'system_prompt' => 'nullable|string',
        ]);

        $openRouterConfig->update($validated);

        return redirect()->route('openrouter-config.index')
            ->with('success', 'Configuração atualizada com sucesso.');
    }

    public function destroy(OpenRouterConfig $openRouterConfig)
    {
        $openRouterConfig->delete();

        return redirect()->route('openrouter-config.index')
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
            // Definir o endpoint correto
            $endpoint = $request->endpoint ?: 'https://openrouter.ai/api/v1';
            
            // Usar o modelo diretamente da requisição
            $model = $request->model;
            
            // Realizar um teste simples usando a API do OpenRouter
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
                Log::info('Teste de conexão com OpenRouter bem-sucedido', [
                    'provider' => $request->provider,
                    'model' => $model
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Conexão estabelecida com sucesso!'
                ]);
            } else {
                $errorMessage = $response->json('error.message') ?? $response->body();
                Log::error('Erro ao testar conexão com OpenRouter', [
                    'status' => $response->status(),
                    'error' => $errorMessage
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Erro na resposta da API: ' . $errorMessage
                ], 422);
            }
        } catch (\Exception $e) {
            Log::error('Exceção ao testar conexão com OpenRouter', [
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
