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
 * Gera resposta do assistente financeiro com memória de conversa e contexto completo
 */
export async function chatWithAssistant(
    message: string,
    conversationHistory?: { role: 'user' | 'assistant'; content: string }[],
    financialContextText?: string
): Promise<string> {
    // Build conversation context from history
    let conversationContext = '';
    if (conversationHistory && conversationHistory.length > 0) {
        // Include last 6 messages for context
        const recentHistory = conversationHistory.slice(-6);
        conversationContext = recentHistory
            .map(msg => `${msg.role === 'user' ? 'Usuário' : 'Assistente'}: ${msg.content}`)
            .join('\n\n');
    }

    const systemPrompt = `Você é o Onlifin AI, um consultor financeiro pessoal altamente qualificado.

═══════════════════════════════════════════════════════════
                    SUAS COMPETÊNCIAS
═══════════════════════════════════════════════════════════

🎯 ANÁLISE FINANCEIRA:
• Analisar receitas, despesas e fluxo de caixa
• Identificar padrões de gastos e oportunidades de economia
• Calcular indicadores financeiros (taxa de poupança, endividamento)
• Comparar períodos e identificar tendências

📈 PREVISÃO FINANCEIRA:
• Projetar saldo futuro baseado em padrões atuais
• Alertar sobre possíveis problemas de caixa
• Sugerir metas de economia realistas
• Calcular tempo para atingir objetivos financeiros

💡 CONSULTORIA:
• Dar dicas personalizadas de economia
• Sugerir realocação de gastos
• Recomendar categorização de transações
• Orientar sobre organização financeira

🔔 ALERTAS E LEMBRETES:
• Avisar sobre contas próximas do vencimento
• Alertar sobre contas atrasadas
• Identificar gastos acima do normal
• Monitorar uso de limites de cartão

═══════════════════════════════════════════════════════════
                    REGRAS DE COMPORTAMENTO
═══════════════════════════════════════════════════════════

1. Sempre analise os dados financeiros fornecidos antes de responder
2. Use emojis para tornar as respostas mais visuais e amigáveis
3. Seja específico com valores e datas quando disponíveis
4. Mantenha o contexto da conversa anterior
5. Se não tiver dados suficientes, peça que o usuário cadastre
6. Responda SEMPRE em português brasileiro
7. Seja conciso mas completo
8. Priorize ações práticas e executáveis

${financialContextText || '(Dados financeiros não disponíveis - sugira ao usuário cadastrar suas contas e transações)'}

${conversationContext ? `
═══════════════════════════════════════════════════════════
                    HISTÓRICO DA CONVERSA
═══════════════════════════════════════════════════════════
${conversationContext}
` : ''}

Agora responda à mensagem do usuário de forma útil e personalizada:`;

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
