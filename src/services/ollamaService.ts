// API Service para Ollama AI local

const OLLAMA_MODEL = 'qwen2.5:0.5b';

interface OllamaGenerateRequest {
    model: string;
    prompt: string;
    stream?: boolean;
    options?: {
        temperature?: number;
        num_predict?: number;
    };
}

interface OllamaGenerateResponse {
    model: string;
    response: string;
    done: boolean;
    done_reason?: string;
}

/**
 * Chama a API do Ollama para gerar uma resposta
 */
export async function generateWithOllama(
    prompt: string,
    systemPrompt?: string
): Promise<string> {
    const fullPrompt = systemPrompt
        ? `${systemPrompt}\n\nUsuário: ${prompt}\n\nAssistente:`
        : prompt;

    const requestBody: OllamaGenerateRequest = {
        model: OLLAMA_MODEL,
        prompt: fullPrompt,
        stream: false,
        options: {
            temperature: 0.7,
            num_predict: 2048,
        }
    };

    try {
        const response = await fetch('/ollama/api/generate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestBody)
        });

        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`Ollama error: ${response.status} - ${errorText}`);
        }

        const data: OllamaGenerateResponse = await response.json();
        return data.response || '';
    } catch (error: any) {
        console.error('Erro ao chamar Ollama:', error.message);
        throw error;
    }
}

/**
 * Categoriza transações usando IA local
 */
export async function categorizeTransactionsWithAI(
    transactions: any[],
    existingCategories: any[]
): Promise<{
    categorizedTransactions: any[];
    newCategories: any[];
}> {
    const prompt = `Você é um especialista em categorização de transações financeiras.

Analise as seguintes transações e sugira a categoria mais apropriada para cada uma.

CATEGORIAS EXISTENTES:
${JSON.stringify(existingCategories, null, 2)}

TRANSAÇÕES PARA CATEGORIZAR:
${JSON.stringify(transactions, null, 2)}

Para cada transação:
1. Analise a descrição e valor
2. Escolha a categoria mais apropriada das existentes
3. Se nenhuma existente servir, sugira uma nova

Responda APENAS com JSON válido no formato:
{
  "categorizedTransactions": [
    {
      "date": "data",
      "description": "descrição",
      "amount": valor,
      "type": "income" ou "expense",
      "suggestedCategory": "nome da categoria",
      "confidence": 0.0 a 1.0
    }
  ],
  "newCategories": [
    {
      "name": "nome",
      "type": "income" ou "expense"
    }
  ]
}

Responda APENAS com o JSON.`;

    try {
        const response = await generateWithOllama(prompt);

        // Extrair JSON da resposta
        const jsonMatch = response.match(/\{[\s\S]*\}/);
        if (!jsonMatch) {
            throw new Error('Resposta da IA não contém JSON válido');
        }

        return JSON.parse(jsonMatch[0]);
    } catch (error: any) {
        console.error('Erro ao categorizar transações:', error);
        throw error;
    }
}

/**
 * Gera resposta do assistente financeiro
 */
export async function chatWithAssistant(
    message: string,
    financialContext?: {
        totalBalance?: number;
        totalIncome?: number;
        totalExpense?: number;
        accountsCount?: number;
    }
): Promise<string> {
    const systemPrompt = `Você é um assistente financeiro amigável e profissional.
Você ajuda usuários a gerenciar suas finanças, categorizar gastos e dar dicas de economia.

${financialContext ? `
DADOS FINANCEIROS DO USUÁRIO:
- Saldo total: R$ ${financialContext.totalBalance?.toFixed(2) || '0.00'}
- Receitas: R$ ${financialContext.totalIncome?.toFixed(2) || '0.00'}
- Despesas: R$ ${financialContext.totalExpense?.toFixed(2) || '0.00'}
- Número de contas: ${financialContext.accountsCount || 0}
` : ''}

Responda de forma concisa e útil em português brasileiro.`;

    return generateWithOllama(message, systemPrompt);
}

/**
 * Fallback para quando a IA não está disponível
 */
export function getDegradedResponse(message: string): string {
    const lowerMessage = message.toLowerCase();

    if (lowerMessage.includes('saldo')) {
        return '💰 Para ver seu saldo, acesse a página de Contas ou o Dashboard.\n\n_Assistente de IA temporariamente indisponível_';
    }

    if (lowerMessage.includes('despesa') || lowerMessage.includes('gasto')) {
        return '📊 Para ver suas despesas, acesse a página de Transações ou Relatórios.\n\n_Assistente de IA temporariamente indisponível_';
    }

    return `🤖 Desculpe, o assistente de IA está temporariamente indisponível.

Por favor, use as funcionalidades manuais:
• **Transações**: Visualize e gerencie transações
• **Contas**: Veja seus saldos
• **Relatórios**: Acesse relatórios financeiros

_O assistente voltará em breve!_`;
}
