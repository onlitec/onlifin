<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class FinancialReportAIService
{
    /**
     * Gera um relatório financeiro detalhado com insights e explicações em linguagem natural.
     * @param array $data
     * @param string $periodo
     * @return array
     */
    public function generateReport(array $data, string $periodo = 'mensal'): array
    {
        // Obter a data atual do sistema
        $dataAtual = Carbon::now()->format('d/m/Y');
        
        $prompt = "Você é um assistente financeiro automatizado que analisa transações e gera relatórios completos sem solicitar interação adicional do usuário. Você tem acesso a TODOS os dados da plataforma que são fornecidos neste prompt. 

A seguir estão dados financeiros do usuário incluindo:
1. Transações do mês atual (com descrição, valor, categoria, data e tipo)
2. Saldo de contas bancárias
3. Resumo financeiro dos últimos 30 dias
4. Transações recentes

Gere um relatório financeiro $periodo detalhado, com totais por categoria, insights e explicações em português. IMPORTANTE: NÃO solicite dados adicionais ao usuário, use APENAS os dados fornecidos neste prompt. Você DEVE processar os dados automaticamente e gerar os gráficos solicitados sem pedir informações adicionais. 

A data atual do sistema é $dataAtual. 

Resposta em JSON com os campos: 
- resumo (string com resumo geral da situação financeira)
- totais_por_categoria (um objeto com categorias como chaves e valores numéricos)
- insights (array de strings com recomendações e observações)
- explicacao (string detalhando a análise)
- dados_grafico (um objeto com: 
  * labels [array de nomes de categorias]
  * data [array de valores numéricos correspondentes às categorias] para o gráfico de pizza de despesas por categoria
  * trend_labels [array de períodos/datas]
  * trend_revenue [array de valores de receitas por período]
  * trend_expenses [array de valores de despesas por período] para o gráfico de linha de evolução financeira)

Você DEVE preencher todos os campos do JSON, incluindo os dados para os gráficos, usando os dados fornecidos. Dados financeiros: ";
        $prompt .= json_encode($data);

        $response = Http::withToken(config('services.openrouter.token'))
            ->post(config('services.openrouter.endpoint'), [
                'prompt' => $prompt,
                'max_tokens' => 1500, // Aumentado para acomodar dados de gráficos adicionais
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
            'dados_grafico' => [
                'labels' => [],
                'data' => [],
                'trend_labels' => [],
                'trend_revenue' => [],
                'trend_expenses' => []
            ],
        ];
    }
}