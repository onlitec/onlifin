<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotConfig extends Model
{
    use HasFactory;

    protected $table = 'chatbot_configs';

    protected $fillable = [
        'user_id',
        'name',
        'provider',
        'model',
        'api_key',
        'endpoint',
        'system_prompt',
        'temperature',
        'max_tokens',
        'enabled',
        'is_default',
        'settings'
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'is_default' => 'boolean',
        'temperature' => 'float',
        'max_tokens' => 'integer',
        'settings' => 'array'
    ];

    protected $hidden = [
        'api_key'
    ];

    /**
     * Relacionamento com usuário
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope para configurações ativas
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope para configuração padrão
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Obtém a configuração padrão do chatbot
     */
    public static function getDefault($userId = null)
    {
        $query = static::enabled()->default();
        
        if ($userId) {
            $query->where('user_id', $userId);
        }
        
        return $query->first();
    }

    /**
     * Define esta configuração como padrão
     */
    public function setAsDefault()
    {
        // Remove o padrão de outras configurações do mesmo usuário
        static::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);
        
        // Define esta como padrão
        $this->update(['is_default' => true]);
    }

    /**
     * Obtém a API key mascarada para exibição
     */
    public function getMaskedApiKeyAttribute()
    {
        if (!$this->api_key) {
            return null;
        }
        
        $key = $this->api_key;
        $length = strlen($key);
        
        if ($length <= 8) {
            return str_repeat('*', $length);
        }
        
        return substr($key, 0, 4) . str_repeat('*', $length - 8) . substr($key, -4);
    }

    /**
     * Valida se a configuração está completa
     */
    public function isValid()
    {
        return !empty($this->provider) && 
               !empty($this->model) && 
               !empty($this->api_key) &&
               !empty($this->system_prompt);
    }

    /**
     * Obtém configurações específicas do provedor
     */
    public function getProviderSettings()
    {
        $baseSettings = [
            'provider' => $this->provider,
            'model' => $this->model,
            'api_key' => $this->api_key,
            'endpoint' => $this->endpoint,
            'temperature' => $this->temperature ?? 0.7,
            'max_tokens' => $this->max_tokens ?? 1000
        ];

        // Adicionar configurações específicas do provedor
        if ($this->settings) {
            $baseSettings = array_merge($baseSettings, $this->settings);
        }

        return $baseSettings;
    }

    /**
     * Testa a conexão com o provedor de IA
     */
    public function testConnection()
    {
        try {
            $aiService = new \App\Services\AIService(
                $this->provider,
                $this->model,
                $this->api_key,
                $this->endpoint,
                $this->system_prompt
            );

            $result = $aiService->test();
            
            return [
                'success' => $result['status'] === 'success',
                'message' => $result['message'] ?? 'Teste realizado com sucesso',
                'response_time' => $result['response_time'] ?? null
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao testar conexão: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtém modelos disponíveis para o provedor
     */
    public static function getAvailableModels($provider)
    {
        $models = [
            'openai' => [
                'gpt-4' => 'GPT-4',
                'gpt-4-turbo' => 'GPT-4 Turbo',
                'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
                'gpt-3.5-turbo-16k' => 'GPT-3.5 Turbo 16K'
            ],
            'anthropic' => [
                'claude-3-opus-20240229' => 'Claude 3 Opus',
                'claude-3-sonnet-20240229' => 'Claude 3 Sonnet',
                'claude-3-haiku-20240307' => 'Claude 3 Haiku'
            ],
            'gemini' => [
                'gemini-pro' => 'Gemini Pro',
                'gemini-pro-vision' => 'Gemini Pro Vision',
                'gemini-2.0-flash' => 'Gemini 2.0 Flash'
            ],
            'groq' => [
                'llama-3.3-70b-versatile' => 'Llama 3.3 70B Versatile',
                'llama-3.1-8b-instant' => 'Llama 3.1 8B Instant',
                'gemma2-9b-it' => 'Gemma 2 9B IT',
                'deepseek-r1-distill-llama-70b' => 'DeepSeek R1 Distill Llama 70B'
            ],
            'openrouter' => [
                'openai/gpt-4' => 'GPT-4 (OpenRouter)',
                'anthropic/claude-3-opus' => 'Claude 3 Opus (OpenRouter)',
                'meta-llama/llama-3.1-70b-instruct' => 'Llama 3.1 70B (OpenRouter)'
            ]
        ];

        return $models[$provider] ?? [];
    }

    /**
     * Obtém prompt padrão para chatbot financeiro
     */
    public static function getDefaultFinancialPrompt()
    {
        return "Você é um assistente financeiro inteligente especializado em análise de dados financeiros pessoais. " .
               "Sua função é ajudar usuários a entender suas finanças, identificar padrões de gastos, " .
               "fornecer insights sobre receitas e despesas, e sugerir melhorias na gestão financeira.\n\n" .
               
               "CAPACIDADES:\n" .
               "- Analisar transações bancárias e categorizações\n" .
               "- Calcular saldos, receitas e despesas\n" .
               "- Identificar tendências e padrões de gastos\n" .
               "- Gerar previsões financeiras baseadas em histórico\n" .
               "- Sugerir otimizações e melhorias no orçamento\n" .
               "- Responder perguntas sobre contas bancárias e cartões\n\n" .
               
               "DIRETRIZES:\n" .
               "- Sempre baseie suas respostas nos dados financeiros fornecidos\n" .
               "- Seja preciso com números, cálculos e percentuais\n" .
               "- Use linguagem clara e acessível, evitando jargões técnicos\n" .
               "- Forneça insights acionáveis e sugestões práticas\n" .
               "- Seja empático e compreensivo com a situação financeira do usuário\n" .
               "- Use formatação markdown para melhor legibilidade\n" .
               "- Inclua emojis relevantes para tornar a conversa mais amigável\n\n" .
               
               "FORMATO DE RESPOSTA:\n" .
               "- Comece com um resumo claro da situação\n" .
               "- Apresente dados relevantes com formatação adequada\n" .
               "- Inclua insights e análises baseadas nos dados\n" .
               "- Termine com sugestões ou próximos passos quando apropriado";
    }
}
