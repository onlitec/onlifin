<?php

namespace App\Http\Controllers;

use App\Models\ModelApiKey;
use App\Services\AIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ModelApiKeyController extends Controller
{
    /**
     * Display a listing of the model-specific API keys.
     */
    public function index()
    {
        // Carregar todas as configurações de chaves por modelo
        $modelKeys = ModelApiKey::orderBy('provider')->orderBy('model')->get();
        
        // Carregar lista de provedores disponíveis
        $providers = config('ai.providers', []);
        
        if (empty($providers)) {
            // Fallback caso a configuração não esteja definida
            $providers = [
                'openai' => [
                    'name' => 'OpenAI',
                    'models' => ['gpt-3.5-turbo', 'gpt-4', 'gpt-4-turbo-preview']
                ],
                'anthropic' => [
                    'name' => 'Anthropic Claude',
                    'models' => ['claude-3-opus-20240229', 'claude-3-sonnet-20240229', 'claude-3-haiku-20240307']
                ],
                'gemini' => [
                    'name' => 'Google Gemini',
                    'models' => ['gemini-1.5-flash', 'gemini-1.5-pro', 'gemini-pro', 'gemini-pro-vision']
                ],
                'openrouter' => [
                    'name' => 'OpenRouter',
                    'models' => ['meta-llama/llama-3-70b-instruct', 'other-openrouter-models']
                ],
            ];
        }
        
        return view('settings.model-keys', compact('modelKeys', 'providers'));
    }

    /**
     * Store a newly created model-specific API key.
     */
    public function store(Request $request)
    {
        // Validar dados de entrada
        $validated = $request->validate([
            'provider' => 'required|string|max:50',
            'model' => 'required|string|max:100',
            'api_token' => 'required|string',
            'system_prompt' => 'nullable|string',
        ]);
        
        try {
            // Verificar se já existe uma configuração para este modelo específico
            $existingConfig = ModelApiKey::where('provider', $validated['provider'])
                ->where('model', $validated['model'])
                ->first();
            
            if ($existingConfig) {
                // Atualizar configuração existente
                $existingConfig->update($validated);
                $message = 'Configuração atualizada com sucesso!';
            } else {
                // Criar nova configuração
                ModelApiKey::create($validated);
                $message = 'Configuração salva com sucesso!';
            }
            
            return redirect()->route('settings.model-keys.index')->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Erro ao salvar configuração de chave por modelo: ' . $e->getMessage());
            return redirect()->route('settings.model-keys.index')->with('error', 'Erro ao salvar configuração: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified model-specific API key.
     */
    public function edit(ModelApiKey $modelKey)
    {
        $providers = config('ai.providers', []);
        return view('settings.model-keys-edit', compact('modelKey', 'providers'));
    }

    /**
     * Update the specified model-specific API key.
     */
    public function update(Request $request, ModelApiKey $modelKey)
    {
        // Debug para verificar os dados recebidos
        Log::info('Dados recebidos na atualização de ModelApiKey:', [
            'id' => $modelKey->id,
            'request_all' => $request->all(),
            'has_system_prompt' => $request->has('system_prompt'),
            'system_prompt_length' => strlen($request->system_prompt ?? '')
        ]);
        
        $validated = $request->validate([
            'api_token' => 'required|string',
            'system_prompt' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);
        
        try {
            // Definir is_active como false se não estiver presente
            if (!isset($validated['is_active'])) {
                $validated['is_active'] = false;
            }
            
            // Garantir que system_prompt seja incluído mesmo que vazio
            if ($request->has('system_prompt')) {
                $validated['system_prompt'] = $request->system_prompt;
            }
            
            // Debug antes da atualização
            Log::info('Dados validados para atualização:', $validated);
            
            // Atualizar diretamente com os dados brutos do request para garantir
            $modelKey->api_token = $request->api_token;
            $modelKey->system_prompt = $request->system_prompt;
            $modelKey->is_active = $request->has('is_active') ? true : false;
            $modelKey->save();
            
            // Debug após a atualização
            Log::info('ModelApiKey após atualização:', [
                'id' => $modelKey->id,
                'has_system_prompt' => !empty($modelKey->system_prompt),
                'system_prompt_length' => strlen($modelKey->system_prompt ?? '')
            ]);
            
            return redirect()->route('settings.model-keys.index')->with('success', 'Configuração atualizada com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar configuração de chave por modelo: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('settings.model-keys.index')->with('error', 'Erro ao atualizar configuração: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified model-specific API key.
     */
    public function destroy(ModelApiKey $modelKey)
    {
        try {
            $modelKey->delete();
            return redirect()->route('settings.model-keys.index')->with('success', 'Configuração excluída com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao excluir configuração de chave por modelo: ' . $e->getMessage());
            return redirect()->route('settings.model-keys.index')->with('error', 'Erro ao excluir configuração: ' . $e->getMessage());
        }
    }
    
    /**
     * Testa a conexão com o provedor de IA usando a chave específica do modelo.
     */
    public function testConnection(Request $request)
    {
        // Validar dados de entrada
        $validated = $request->validate([
            'provider' => 'required|string|max:50',
            'model' => 'required|string|max:100',
            'api_token' => 'required|string',
        ]);
        
        try {
            // Instanciar serviço de IA com o provedor e modelo específicos
            $aiService = new AIService(
                $validated['provider'],
                $validated['model'],
                $validated['api_token'],
                null, // endpoint
                null, // systemPrompt
                null, // chatPrompt
                null  // importPrompt
            );
            
            // Testar conexão
            $success = $aiService->test();
            
            return response()->json([
                'success' => true,
                'message' => 'Conexão estabelecida com sucesso!'
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao testar conexão: ' . $e->getMessage(), [
                'provider' => $validated['provider'], 
                'model' => $validated['model']
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
