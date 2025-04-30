<?php

namespace App\Http\Controllers;

use App\Models\ReplicateSetting;
use App\Services\AIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\SystemLog;

class ReplicateSettingController extends Controller
{
    /**
     * Mostra o formulário de configuração
     */
    public function index()
    {
        $settings = ReplicateSetting::getActive() ?? new ReplicateSetting([
            'provider' => 'openai',
            'model_version' => 'gpt-3.5-turbo'
        ]);
        
        // Lista de provedores e seus modelos disponíveis
        $providers = [
            'openai' => [
                'name' => 'OpenAI',
                'models' => ['gpt-3.5-turbo', 'gpt-4', 'gpt-4-turbo-preview'],
                'api_token' => env('OPENAI_API_KEY'),
                'api_url' => 'https://platform.openai.com/api-keys'
            ],
            'anthropic' => [
                'name' => 'Anthropic',
                'models' => [
                    'claude-3-opus',
                    'claude-3-sonnet',
                    'claude-3-haiku'
                ],
                'api_token' => env('ANTHROPIC_API_KEY'),
                'api_url' => 'https://console.anthropic.com/account/keys'
            ],
            'gemini' => [
                'name' => 'Google Gemini',
                'models' => [
                    'gemini-1.5-flash',
                    'gemini-1.5-pro',
                    'gemini-pro',
                    'gemini-pro-vision',
                    'gemini-pro-vision-2'
                ],
                'api_token' => env('GOOGLE_API_KEY'),
                'api_url' => 'https://makersuite.google.com/app/apikey'
            ],
            'grok' => [
                'name' => 'Grok',
                'models' => [
                    'mixtral-8x7b-instruct',
                    'mixtral-8x7b',
                    'mixtral-32b-instruct'
                ],
                'api_token' => env('GROK_API_KEY'),
                'api_url' => 'https://grokai.com/api-keys'
            ],
            'copilot' => [
                'name' => 'Copilot',
                'models' => ['copilot'],
                'api_token' => env('COPILOT_API_KEY'),
                'api_url' => 'https://github.com/features/copilot'
            ],
            'tongyi' => [
                'name' => 'Tongyi Qianwen',
                'models' => [
                    'qwen-7b',
                    'qwen-14b',
                    'qwen-70b'
                ],
                'api_token' => env('TONGYI_API_KEY'),
                'api_url' => 'https://dashscope.console.aliyun.com/apikey'
            ],
            'deepseek' => [
                'name' => 'Deepseek',
                'models' => [
                    'deepseek-r1',
                    'deepseek-r1-13b',
                    'deepseek-r2',
                    'deepseek-r2-70b'
                ],
                'api_token' => env('DEEPSEEK_API_KEY'),
                'api_url' => 'https://deepseek.ai/developer'
            ],
            'openrouter' => [
                'name' => 'OpenRouter',
                'models' => [
                    'anthropic/claude-3-opus',
                    'anthropic/claude-3-sonnet',
                    'meta-llama/llama-3-70b-instruct',
                    'meta-llama/llama-3-8b-instruct',
                    'google/gemini-pro',
                    'custom'
                ],
                'api_token' => env('OPENROUTER_API_KEY'),
                'api_url' => 'https://openrouter.ai/keys',
                'endpoint' => 'https://openrouter.ai/api/v1'
            ]
        ];

        return view('settings.replicate', compact('settings', 'providers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'provider' => 'required|string|in:openai,anthropic,gemini,grok,copilot,tongyi,deepseek,openrouter',
            'api_token' => 'required|string',
            'endpoint' => 'nullable|string|url',
            'model_version' => 'required|string',
            'system_prompt' => 'nullable|string',
            'is_active' => 'nullable|boolean'
        ]);

        try {
            // Define o modelo padrão baseado no provedor
            $modelVersion = $request->model_version;
            $provider = $request->provider;

            // Validação específica por provedor
            $validModels = [
                'openai' => ['gpt-3.5-turbo', 'gpt-4', 'gpt-4-turbo-preview'],
                'anthropic' => [
                    'claude-3-opus',
                    'claude-3-sonnet',
                    'claude-3-haiku'
                ],
                'gemini' => [
                    'gemini-pro',
                    'gemini-pro-vision',
                    'gemini-pro-vision-2'
                ],
                'grok' => [
                    'mixtral-8x7b-instruct',
                    'mixtral-8x7b',
                    'mixtral-32b-instruct'
                ],
                'copilot' => ['copilot'],
                'tongyi' => [
                    'qwen-7b',
                    'qwen-14b',
                    'qwen-70b'
                ],
                'deepseek' => [
                    'deepseek-r1',
                    'deepseek-r1-13b',
                    'deepseek-r2',
                    'deepseek-r2-70b'
                ],
                'openrouter' => [
                    'anthropic/claude-3-opus',
                    'anthropic/claude-3-sonnet',
                    'meta-llama/llama-3-70b-instruct',
                    'meta-llama/llama-3-8b-instruct',
                    'google/gemini-pro',
                    'custom'
                ]
            ];

            if (!in_array($modelVersion, $validModels[$provider] ?? [])) {
                $modelVersion = $validModels[$provider][0] ?? 'gpt-3.5-turbo';
            }

            // Cria ou atualiza a configuração
            $settings = ReplicateSetting::where('provider', $request->provider)
                ->first() ?? new ReplicateSetting();
            $settings->fill([
                'provider' => $request->provider,
                'api_token' => $request->api_token,
                'endpoint' => $request->endpoint,
                'model_version' => $modelVersion,
                'system_prompt' => $request->system_prompt,
                'is_active' => $request->boolean('is_active')
            ]);
            $settings->save();

            // Registra o log da ação
            SystemLog::register(
                'update',
                'ai_settings',
                'Configurações de IA atualizadas',
                [
                    'provider' => $request->provider,
                    'model' => $modelVersion,
                    'is_active' => $request->boolean('is_active'),
                    'has_system_prompt' => !empty($request->system_prompt),
                    'has_endpoint' => !empty($request->endpoint)
                ]
            );

            return redirect()->route('settings.replicate.index')
                ->with('success', 'Configurações de IA salvas com sucesso.');
        } catch (\Exception $e) {
            Log::error('Erro ao salvar configurações de IA: ' . $e->getMessage());

            // Registra o log do erro
            SystemLog::register(
                'error',
                'ai_settings',
                'Erro ao salvar configurações de IA: ' . $e->getMessage(),
                [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            );

            return redirect()->back()
                ->withInput()
                ->with('error', 'Erro ao salvar configurações: ' . $e->getMessage());
        }
    }

    /**
     * Testa a conexão com o provedor de IA
     */
    public function test(Request $request)
    {
        try {
            // Verificar se a requisição é JSON e processar adequadamente
            $data = $request->isJson() ? $request->json()->all() : $request->all();
            
            // Validar os dados da requisição
            $validated = Validator::make($data, [
                'provider' => 'required|string|in:openai,anthropic,gemini,grok,copilot,tongyi,deepseek,openrouter',
                'api_token' => 'required|string',
                'endpoint' => 'nullable|string|url',
                'model_version' => 'required|string'
            ]);
            
            if ($validated->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos: ' . $validated->errors()->first()
                ], 422);
            }
            
            // Log dos dados recebidos para debug
            Log::info('Dados recebidos para teste de conexão:', [
                'provider' => $data['provider'],
                'has_token' => !empty($data['api_token']),
                'has_endpoint' => !empty($data['endpoint']),
                'model' => $data['model_version'],
                'is_json' => $request->isJson()
            ]);

            // Criar um objeto temporário de configurações para teste
            $settings = new ReplicateSetting();
            $settings->provider = $data['provider'];
            $settings->api_token = $data['api_token'];
            $settings->endpoint = $data['endpoint'] ?? null;
            $settings->model_version = $data['model_version'];
            
            // Testar a conexão usando o serviço apropriado
            $service = new AIService($settings);
            $service->test();
            
            // Registrar o log do teste bem-sucedido
            Log::info('Teste de conexão com IA bem-sucedido', [
                'provider' => $settings->provider,
                'model' => $settings->model_version,
                'has_endpoint' => !empty($settings->endpoint)
            ]);

            SystemLog::register(
                'test',
                'ai_settings',
                'Teste de conexão com IA realizado com sucesso',
                [
                    'provider' => $settings->provider,
                    'model' => $settings->model_version
                ]
            );

            // Se for uma requisição AJAX ou se explicitamente diz que quer JSON, retorne JSON
            if ($request->ajax() || $request->wantsJson() || $request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => true,
                    'message' => 'Conexão com o provedor de IA estabelecida com sucesso.'
                ]);
            }
            
            // Caso contrário, redirecione com uma mensagem de sucesso
            return redirect()->route('settings.replicate.index')
                ->with('success', 'Conexão com o provedor de IA estabelecida com sucesso.');
        } catch (\Exception $e) {
            // Registrar o log do erro
            Log::error('Erro ao testar conexão com IA: ' . $e->getMessage(), [
                'provider' => $request->provider ?? 'desconhecido',
                'model' => $request->model_version ?? 'desconhecido',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            SystemLog::register(
                'error',
                'ai_settings',
                'Erro ao testar conexão com IA: ' . $e->getMessage(),
                [
                    'provider' => $request->provider ?? 'desconhecido',
                    'model' => $request->model_version ?? 'desconhecido',
                    'error' => $e->getMessage()
                ]
            );
            
            // Se for uma requisição AJAX ou se explicitamente diz que quer JSON, retorne JSON
            if ($request->ajax() || $request->wantsJson() || $request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao testar conexão: ' . $e->getMessage()
                ], 422);
            }
            
            // Caso contrário, redirecione com uma mensagem de erro
            return redirect()->route('settings.replicate.index')
                ->with('error', 'Erro ao testar conexão: ' . $e->getMessage());
        }
    }
    
    /**
     * Busca as configurações salvas de um provedor específico
     */
    public function getSettings($provider)
    {
        try {
            // Validar o provedor
            if (!in_array($provider, ['openai', 'anthropic', 'gemini', 'grok', 'copilot', 'tongyi', 'deepseek', 'openrouter'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Provedor inválido'
                ], 422);
            }
            
            // Buscar as configurações salvas
            $settings = ReplicateSetting::where('provider', $provider)->first();
            
            if (!$settings) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma configuração encontrada para este provedor'
                ]);
            }
            
            return response()->json([
                'success' => true,
                'settings' => [
                    'provider' => $settings->provider,
                    'api_token' => $settings->api_token,
                    'model_version' => $settings->model_version,
                    'system_prompt' => $settings->system_prompt
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar configurações: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar configurações: ' . $e->getMessage()
            ], 500);
        }
    }
}
