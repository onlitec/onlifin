<?php

namespace App\Http\Controllers;

use App\Models\ReplicateSetting;
use App\Services\ReplicateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReplicateSettingController extends Controller
{
    /**
     * Mostra o formulário de configuração
     */
    public function index()
    {
        $settings = ReplicateSetting::getActive() ?? new ReplicateSetting();
        return view('settings.replicate', compact('settings'));
    }

    /**
     * Salva as configurações
     */
    public function store(Request $request)
    {
        $request->validate([
            'api_token' => 'required|string',
            'model_version' => 'required|string',
            'system_prompt' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        try {
            // Desativa todas as configurações existentes
            ReplicateSetting::query()->update(['is_active' => false]);

            // Cria ou atualiza a configuração
            $settings = ReplicateSetting::getActive() ?? new ReplicateSetting();
            $settings->fill($request->all());
            $settings->is_active = $request->boolean('is_active', true);
            $settings->save();

            return redirect()->route('settings.replicate.index')
                ->with('success', 'Configurações do Replicate salvas com sucesso.');
        } catch (\Exception $e) {
            Log::error('Erro ao salvar configurações do Replicate: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erro ao salvar configurações: ' . $e->getMessage());
        }
    }

    /**
     * Testa a conexão com o Replicate
     */
    public function test(Request $request)
    {
        try {
            // Verificar se há configurações salvas
            $settings = ReplicateSetting::getActive();
            
            if (!$settings) {
                Log::warning('Teste de conexão Replicate: Nenhuma configuração encontrada');
                
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
                Log::warning('Teste de conexão Replicate: Campos obrigatórios não preenchidos', [
                    'has_token' => !empty($settings->api_token),
                    'has_model' => !empty($settings->model_version)
                ]);
                
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Token da API e Versão do Modelo são obrigatórios. Preencha todos os campos e salve antes de testar.'
                    ], 422);
                }
                
                return redirect()->route('settings.replicate.index')
                    ->with('error', 'Token da API e Versão do Modelo são obrigatórios. Preencha todos os campos e salve antes de testar.');
            }
            
            Log::info('Iniciando teste de conexão com Replicate', [
                'model_version' => $settings->model_version
            ]);
            
            // Testar a conexão
            if (!class_exists(ReplicateService::class)) {
                throw new \Exception('Classe ReplicateService não encontrada');
            }
            
            $service = new ReplicateService();
            $response = $service->analyzeStatement('Teste de conexão com Replicate API');
            
            Log::info('Teste de conexão com Replicate bem-sucedido', [
                'response' => $response
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Conexão com Replicate estabelecida com sucesso.'
                ]);
            }
            
            return redirect()->route('settings.replicate.index')
                ->with('success', 'Conexão com Replicate estabelecida com sucesso.');
        } catch (\Exception $e) {
            Log::error('Erro ao testar conexão com Replicate: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
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
