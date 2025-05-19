<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FinanceSummaryAIService
{
    /**
     * Gera um resumo inteligente e insights financeiros usando IA.
     * @param array $transactions
     * @return array
     */
    public function generateSummary(array $transactions): array
    {
        // Monta um prompt para IA com os dados das transações
        $prompt = "A seguir estão transações financeiras do usuário (com descrição, valor, categoria e data). Gere um resumo inteligente, insights e sugestões de economia em português. Responda em formato JSON com os campos: resumo, insights, sugestoes. Transações: ";
        $prompt .= json_encode($transactions);

        $response = Http::withToken(config('services.openrouter.token'))
            ->post(config('services.openrouter.endpoint'), [
                'prompt' => $prompt,
                'max_tokens' => 300,
                'temperature' => 0.7,
            ]);

        if ($response->successful() && isset($response['choices'][0]['text'])) {
            $json = json_decode($response['choices'][0]['text'], true);
            if (is_array($json)) {
                return $json;
            }
        }
        return [
            'resumo' => 'Não foi possível gerar o resumo.',
            'insights' => [],
            'sugestoes' => [],
        ];
    }
} 