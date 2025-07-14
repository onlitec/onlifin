<?php

namespace App\Http\Controllers;

use App\Models\ChatbotConfig;
use App\Services\AIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ChatbotConfigController extends Controller
{
    /**
     * Exibe a página de configuração do chatbot
     */
    public function index()
    {
        $currentConfig = ChatbotConfig::getDefault(Auth::id());
        
        return view('settings.chatbot-config', compact('currentConfig'));
    }

    /**
     * Salva ou atualiza a configuração do chatbot
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'provider' => 'required|string|in:openai,anthropic,gemini,groq,openrouter',
            'model' => 'required|string|max:255',
            'api_key' => 'nullable|string',
            'endpoint' => 'nullable|url',
            'system_prompt' => 'required|string|min:50',
            'temperature' => 'required|numeric|between:0,1',
            'max_tokens' => 'required|integer|between:100,4000',
            'enabled' => 'boolean',
            'is_default' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $userId = Auth::id();
            $currentConfig = ChatbotConfig::getDefault($userId);
            
            // Se não há API key no request e não há configuração atual, é obrigatória
            if (!$request->api_key && !$currentConfig) {
                return redirect()->back()
                    ->withErrors(['api_key' => 'API Key é obrigatória para nova configuração'])
                    ->withInput();
            }

            // Preparar dados para salvar
            $data = [
                'user_id' => $userId,
                'name' => $request->name,
                'provider' => $request->provider,
                'model' => $request->model,
                'endpoint' => $request->endpoint,
                'system_prompt' => $request->system_prompt,
                'temperature' => $request->temperature,
                'max_tokens' => $request->max_tokens,
                'enabled' => $request->boolean('enabled', true),
                'is_default' => $request->boolean('is_default', true)
            ];

            // Adicionar API key apenas se fornecida
            if ($request->api_key) {
                $data['api_key'] = $request->api_key;
            }

            if ($currentConfig) {
                // Atualizar configuração existente
                $currentConfig->update($data);
                $config = $currentConfig;
            } else {
                // Criar nova configuração
                $config = ChatbotConfig::create($data);
            }

            // Definir como padrão se solicitado
            if ($request->boolean('is_default', true)) {
                $config->setAsDefault();
            }

            return redirect()->route('settings.chatbot-config')
                ->with('success', 'Configuração do chatbot salva com sucesso!');

        } catch (\Exception $e) {
            Log::error('Erro ao salvar configuração do chatbot', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'data' => $request->except('api_key')
            ]);

            return redirect()->back()
                ->withErrors(['general' => 'Erro ao salvar configuração: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Testa a conexão com o provedor de IA
     */
    public function test(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'provider' => 'required|string',
            'model' => 'required|string',
            'api_key' => 'nullable|string',
            'system_prompt' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos: ' . $validator->errors()->first()
            ], 400);
        }

        try {
            $apiKey = $request->api_key;
            
            // Se não há API key no request, tentar usar a da configuração atual
            if (!$apiKey) {
                $currentConfig = ChatbotConfig::getDefault(Auth::id());
                if ($currentConfig) {
                    $apiKey = $currentConfig->api_key;
                }
            }

            if (!$apiKey) {
                return response()->json([
                    'success' => false,
                    'message' => 'API Key é necessária para testar a conexão'
                ], 400);
            }

            // Inicializar serviço de IA
            $aiService = new AIService(
                $request->provider,
                $request->model,
                $apiKey,
                $request->endpoint,
                $request->system_prompt
            );

            // Testar conexão
            $startTime = microtime(true);
            $result = $aiService->test();
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            if ($result['status'] === 'success') {
                return response()->json([
                    'success' => true,
                    'message' => 'Conexão testada com sucesso!',
                    'response_time' => $responseTime . 'ms',
                    'provider' => $request->provider,
                    'model' => $request->model
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Erro desconhecido no teste'
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Erro ao testar conexão do chatbot', [
                'error' => $e->getMessage(),
                'provider' => $request->provider,
                'model' => $request->model,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao testar conexão: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove uma configuração do chatbot
     */
    public function destroy(ChatbotConfig $config)
    {
        try {
            // Verificar se o usuário pode deletar esta configuração
            if ($config->user_id !== Auth::id()) {
                return redirect()->route('settings.chatbot-config')
                    ->withErrors(['general' => 'Você não tem permissão para deletar esta configuração']);
            }

            $config->delete();

            return redirect()->route('settings.chatbot-config')
                ->with('success', 'Configuração removida com sucesso!');

        } catch (\Exception $e) {
            Log::error('Erro ao remover configuração do chatbot', [
                'error' => $e->getMessage(),
                'config_id' => $config->id,
                'user_id' => Auth::id()
            ]);

            return redirect()->route('settings.chatbot-config')
                ->withErrors(['general' => 'Erro ao remover configuração: ' . $e->getMessage()]);
        }
    }

    /**
     * Lista todas as configurações do usuário
     */
    public function list()
    {
        $configs = ChatbotConfig::where('user_id', Auth::id())
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'configs' => $configs->map(function ($config) {
                return [
                    'id' => $config->id,
                    'name' => $config->name,
                    'provider' => $config->provider,
                    'model' => $config->model,
                    'enabled' => $config->enabled,
                    'is_default' => $config->is_default,
                    'masked_api_key' => $config->masked_api_key,
                    'created_at' => $config->created_at->format('d/m/Y H:i')
                ];
            })
        ]);
    }

    /**
     * Define uma configuração como padrão
     */
    public function setDefault(ChatbotConfig $config)
    {
        try {
            // Verificar se o usuário pode alterar esta configuração
            if ($config->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para alterar esta configuração'
                ], 403);
            }

            $config->setAsDefault();

            return response()->json([
                'success' => true,
                'message' => 'Configuração definida como padrão com sucesso!'
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao definir configuração padrão do chatbot', [
                'error' => $e->getMessage(),
                'config_id' => $config->id,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao definir configuração padrão: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtém estatísticas de uso do chatbot
     */
    public function stats()
    {
        try {
            $userId = Auth::id();
            $config = ChatbotConfig::getDefault($userId);

            if (!$config) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma configuração encontrada'
                ], 404);
            }

            // Aqui você pode adicionar lógica para coletar estatísticas
            // Por exemplo, número de mensagens processadas, tempo de resposta médio, etc.
            
            $stats = [
                'config_name' => $config->name,
                'provider' => $config->provider,
                'model' => $config->model,
                'enabled' => $config->enabled,
                'created_at' => $config->created_at->format('d/m/Y H:i'),
                'last_updated' => $config->updated_at->format('d/m/Y H:i')
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao obter estatísticas do chatbot', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao obter estatísticas: ' . $e->getMessage()
            ], 500);
        }
    }
}
