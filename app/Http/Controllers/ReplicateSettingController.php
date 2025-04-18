<?php

namespace App\Http\Controllers;

use App\Models\ReplicateSetting;
use App\Services\AIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
                'api_token' => env('OPENAI_API_KEY')
            ],
            'anthropic' => [
                'name' => 'Anthropic',
                'models' => [
                    'claude-3-opus-20240229',
                    'claude-3-sonnet-20240229',
                    'claude-3-haiku-20240307'
                ],
                'api_token' => env('ANTHROPIC_API_KEY')
            ],
            'gemini' => [
                'name' => 'Gemini',
                'models' => ['gemini-pro', 'gemini-pro-vision'],
                'api_token' => env('GOOGLE_API_KEY')
            ],
            'grok' => [
                'name' => 'Grok',
                'models' => ['mixtral-8x7b-instruct', 'mixtral-8x7b'],
                'api_token' => env('GROK_API_KEY')
            ],
            'copilot' => [
                'name' => 'Copilot',
                'models' => ['copilot'],
                'api_token' => env('COPILOT_API_KEY')
            ]
        ];

        return view('settings.replicate', compact('settings', 'providers'));
    }

    /**
     * Salva as configurações
     */
    public function store(Request $request)
    {
        $request->validate([
            'provider' => 'required|string|in:openai,anthropic,gemini,grok,copilot',
            'api_token' => 'required|string',
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
                    'claude-3-opus-20240229',
                    'claude-3-sonnet-20240229',
                    'claude-3-haiku-20240307'
                ],
                'gemini' => ['gemini-pro', 'gemini-pro-vision'],
                'grok' => ['mixtral-8x7b-instruct', 'mixtral-8x7b'],
                'copilot' => ['copilot']
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
                    'has_system_prompt' => !empty($request->system_prompt)
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
            // Verificar se há configurações salvas
            $settings = ReplicateSetting::getActive();
            
            if (!$settings) {
                Log::warning('Teste de conexão IA: Nenhuma configuração encontrada');

                SystemLog::register(
                    'test',
                    'ai_settings',
                    'Teste de conexão falhou: Nenhuma configuração encontrada'
                );
                
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Nenhuma configuração encontrada. Salve as configurações antes de testar.'
                    ], 422);
                }
                
                return redirect()->route('settings.replicate.index')
                    ->with('error', 'Nenhuma configuração encontrada. Salve as configurações antes de testar.');
            }
            
            if (empty($settings->api_token) || empty($settings->model_version)) {
                Log::warning('Teste de conexão IA: Campos obrigatórios não preenchidos', [
                    'has_token' => !empty($settings->api_token),
                    'has_model' => !empty($settings->model_version)
                ]);
                
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Chave da API e Modelo são obrigatórios. Preencha todos os campos e salve antes de testar.'
                    ], 422);
                }
                
                return redirect()->route('settings.replicate.index')
                    ->with('error', 'Chave da API e Modelo são obrigatórios. Preencha todos os campos e salve antes de testar.');
            }
            
            Log::info('Iniciando teste de conexão com IA', [
                'provider' => $settings->provider,
                'model' => $settings->model_version
            ]);
            
            // Testar a conexão usando o serviço apropriado
            $service = new AIService($settings);
            $response = $service->test();
            
            Log::info('Teste de conexão com IA bem-sucedido', [
                'provider' => $settings->provider,
                'response' => $response
            ]);

            // Registra o log do teste bem-sucedido
            SystemLog::register(
                'test',
                'ai_settings',
                'Teste de conexão com IA realizado com sucesso',
                [
                    'provider' => $settings->provider,
                    'model' => $settings->model_version
                ]
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Conexão com o provedor de IA estabelecida com sucesso.'
                ]);
            }
            
            return redirect()->route('settings.replicate.index')
                ->with('success', 'Conexão com o provedor de IA estabelecida com sucesso.');
        } catch (\Exception $e) {
            Log::error('Erro ao testar conexão com IA: ' . $e->getMessage());

            // Registra o log do erro no teste
            SystemLog::register(
                'error',
                'ai_settings',
                'Erro ao testar conexão com IA: ' . $e->getMessage(),
                [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            );
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao testar conexão: ' . $e->getMessage()
                ], 422);
            }
            
            return redirect()->route('settings.replicate.index')
                ->with('error', 'Erro ao testar conexão: ' . $e->getMessage());
        }
    }
}
