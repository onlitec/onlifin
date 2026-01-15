// API Service para Ollama AI local

const OLLAMA_MODEL = 'qwen2.5:0.5b';

interface OllamaMessage {
    role: 'system' | 'user' | 'assistant';
    content: string;
}

interface OllamaChatRequest {
    model: string;
    messages: OllamaMessage[];
    stream?: boolean;
    options?: {
        temperature?: number;
        num_predict?: number;
    };
}

interface OllamaChatResponse {
    model: string;
    message: OllamaMessage;
    done: boolean;
}

/**
 * Chama a API do Ollama para gerar uma resposta usando o endpoint de chat
 */
export async function chatWithOllama(
    messages: OllamaMessage[]
): Promise<string> {
    const requestBody: OllamaChatRequest = {
        model: OLLAMA_MODEL,
        messages,
        stream: false,
        options: {
            temperature: 0.6, // Reduzido ligeiramente para maior consistência
            num_predict: 1024,
        }
    };

    try {
        const response = await fetch('/ollama/api/chat', {
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

        const data: OllamaChatResponse = await response.json();
        return data.message.content || '';
    } catch (error: any) {
        console.error('Erro ao chamar Ollama Chat:', error.message);
        throw error;
    }
}

// Mantendo suporte para generate se necessário
export async function generateWithOllama(
    prompt: string,
    systemPrompt?: string
): Promise<string> {
    const messages: OllamaMessage[] = [];
    if (systemPrompt) messages.push({ role: 'system', content: systemPrompt });
    messages.push({ role: 'user', content: prompt });
    return chatWithOllama(messages);
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
 * Gera resposta do assistente financeiro com memória de conversa e contexto completo
 */
export async function chatWithAssistant(
    message: string,
    conversationHistory?: { role: 'user' | 'assistant'; content: string }[],
    financialContextText?: string
): Promise<string> {
    const systemPrompt = `Você é o Onlifin AI, assistente financeiro pessoal.
Responda sempre em Português (PT-BR). Seja conciso, direto e amigável. Use emojis.
Analise os dados financeiros abaixo para fundamentar suas respostas. Se não houver dados, peça para o usuário cadastrar.

DADOS FINANCEIROS DO USUÁRIO:
${financialContextText || 'Nenhum dado financeiro disponível.'}

INSTRUÇÕES:
1. Use os dados acima para responder perguntas sobre gastos, saldo e economia.
2. Identifique tendências ou gastos excessivos.
3. Se o usuário perguntar algo não financeiro, tente trazer o assunto de volta para finanças.`;

    const messages: OllamaMessage[] = [
        { role: 'system', content: systemPrompt }
    ];

    // Adicionar histórico (últimas 10 mensagens para manter contexto sem estourar token limit)
    if (conversationHistory && conversationHistory.length > 0) {
        const recentHistory = conversationHistory.slice(-10);
        recentHistory.forEach(msg => {
            messages.push({
                role: msg.role as 'user' | 'assistant',
                content: msg.content
            });
        });
    }

    // Adicionar a mensagem atual
    messages.push({ role: 'user', content: message });

    return chatWithOllama(messages);
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
