<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB; // Para salvar em banco de dados, assumindo uma tabela 'config'

class OpenRouterConfigController extends Controller
{
    // Middleware auth estu00e1 definido nas rotas

    public function showConfigForm()
    {
        // Lista de provedores suportados
        $providers = [
            'openai' => [
                'name' => 'OpenAI',
                'models' => ['gpt-3.5-turbo', 'gpt-4', 'gpt-4-turbo', 'gpt-4o']
            ],
            'anthropic' => [
                'name' => 'Anthropic',
                'models' => ['claude-3-opus', 'claude-3-sonnet', 'claude-3-haiku']
            ],
            'google' => [
                'name' => 'Google Gemini',
                'models' => ['gemini-pro', 'gemini-1.5-pro']
            ],
            'openrouter' => [
                'name' => 'OpenRouter',
                'models' => ['any', 'recommended']
            ]
        ];
        
        // Carregar chaves de API salvas
        $modelKeys = collect(); // Se tiver um modelo para ModelApiKey, use ModelApiKey::all() aqui
        if (class_exists('\App\Models\ModelApiKey')) {
            $modelKeys = \App\Models\ModelApiKey::all();
        }
        
        return view('settings.model-keys', compact('providers', 'modelKeys'));
    }

    public function saveConfig(Request $request)
    {
        // Validação dos dados de entrada
        $validated = $request->validate([
            'openrouter_api_key' => 'required|string',
            'openrouter_endpoint' => 'nullable|string',
        ]);

        // Salvar em banco de dados ou config (exemplo: use uma tabela 'configs')
        DB::table('configs')->updateOrInsert(
            ['key' => 'openrouter_api_key'],
            ['value' => encrypt($validated['openrouter_api_key'])] // Criptografe para segurança
        );

        // Atualize configurações dinamicamente se necessário
        Config::set('laravel-openrouter.api_key', decrypt(DB::table('configs')->where('key', 'openrouter_api_key')->value('value')));

        return back()->with('status', 'Configuração salva com sucesso!');
    }
}
