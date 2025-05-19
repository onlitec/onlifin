<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FinancialReportAIService
{
    /**
     * Gera um relatório financeiro detalhado com insights e explicações em linguagem natural.
     * @param array $transactions
     * @param string $periodo
     * @return array
     */
    public function generateReport(array $transactions, string $periodo = 'mensal'): array
    {
        $prompt = "A seguir estão transações financeiras do usuário (com descrição, valor, categoria, data). Gere um relatório financeiro $periodo detalhado, com totais por categoria, gráficos sugeridos, insights e explicações em português. Responda em JSON com os campos: resumo, totais_por_categoria, insights, explicacao. Transações: ";
        $prompt .= json_encode($transactions);

        $response = Http::withToken(config('services.openrouter.token'))
            ->post(config('services.openrouter.endpoint'), [
                'prompt' => $prompt,
                'max_tokens' => 500,
                'temperature' => 0.7,
            ]);

        if ($response->successful() && isset($response['choices'][0]['text'])) {
            $json = json_decode($response['choices'][0]['text'], true);
            if (is_array($json)) {
                return $json;
            }
        }
        return [
            'resumo' => 'Não foi possível gerar o relatório.',
            'totais_por_categoria' => [],
            'insights' => [],
            'explicacao' => '',
        ];
    }
} 