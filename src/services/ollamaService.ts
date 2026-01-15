// API Service para Ollama AI local

const OLLAMA_MODEL = 'qwen2.5:1.5b';

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
 * Categoriza transações usando IA local com aprendizado por exemplos
 */
export async function categorizeTransactionsWithAI(
    transactions: any[],
    existingCategories: any[],
    userExamples?: any[], // Transações recentes do usuário para few-shot learning
    keywordRules?: any[]  // Regras de palavras-chave
): Promise<{
    categorizedTransactions: any[];
    newCategories: any[];
}> {
    // 1. Primeiro, aplicar regras de palavras-chave (mais rápido e preciso)
    const preProcessed = transactions.map(t => {
        const description = (t.description || '').toUpperCase();

        // Verificar regras de palavras-chave
        if (keywordRules && keywordRules.length > 0) {
            for (const rule of keywordRules) {
                const keyword = (rule.keyword || '').toUpperCase();
                let matches = false;

                if (rule.match_type === 'exact') {
                    matches = description === keyword;
                } else if (rule.match_type === 'starts_with') {
                    matches = description.startsWith(keyword);
                } else { // contains (default)
                    matches = description.includes(keyword);
                }

                if (matches) {
                    const category = existingCategories.find(c => c.id === rule.category_id);
                    if (category) {
                        return {
                            ...t,
                            suggestedCategory: category.name,
                            suggestedCategoryId: category.id,
                            isNewCategory: false,
                            confidence: 1.0, // 100% confidence for rule match
                            matchedByRule: true
                        };
                    }
                }
            }
        }

        return { ...t, matchedByRule: false };
    });

    // Separar transações já categorizadas por regras das que precisam de IA
    const alreadyCategorized = preProcessed.filter(t => t.matchedByRule);
    const needsAI = preProcessed.filter(t => !t.matchedByRule);

    // Se todas foram categorizadas por regras, retornar direto
    if (needsAI.length === 0) {
        return {
            categorizedTransactions: alreadyCategorized,
            newCategories: []
        };
    }

    // 2. Formatar categorias de forma clara para a IA
    const incomeCategories = existingCategories
        .filter(c => c.type === 'income')
        .map(c => `- "${c.name}" (ID: ${c.id})`)
        .join('\n');

    const expenseCategories = existingCategories
        .filter(c => c.type === 'expense')
        .map(c => `- "${c.name}" (ID: ${c.id})`)
        .join('\n');

    // 3. Formatar exemplos do usuário (Few-Shot Learning)
    let examplesSection = '';
    if (userExamples && userExamples.length > 0) {
        const examples = userExamples.slice(0, 10).map(ex =>
            `- "${ex.description}" → ${ex.category?.name || 'Sem categoria'} (${ex.type === 'income' ? 'Receita' : 'Despesa'})`
        ).join('\n');

        examplesSection = `
EXEMPLOS DE COMO O USUÁRIO JÁ CATEGORIZOU TRANSAÇÕES SIMILARES:
${examples}

Use estes exemplos como referência para categorizar transações similares.
`;
    }

    // 4. Formatar transações que precisam de IA
    const formattedTransactions = needsAI.map((t, i) =>
        `${i + 1}. ${t.date} | ${t.description} | R$ ${Math.abs(t.amount).toFixed(2)} | ${t.amount >= 0 ? 'RECEITA' : 'DESPESA'}`
    ).join('\n');

    const prompt = `Você é um especialista brasileiro em finanças pessoais. Analise transações bancárias e categorize cada uma usando PREFERENCIALMENTE as categorias já existentes.

CATEGORIAS DE RECEITA EXISTENTES:
${incomeCategories || '(nenhuma categoria de receita cadastrada)'}

CATEGORIAS DE DESPESA EXISTENTES:
${expenseCategories || '(nenhuma categoria de despesa cadastrada)'}
${examplesSection}
TRANSAÇÕES PARA CATEGORIZAR:
${formattedTransactions}

REGRAS IMPORTANTES:
1. SEMPRE use categorias existentes quando possível
2. Use o NOME EXATO da categoria existente no campo "suggestedCategory"
3. Use o ID da categoria existente no campo "suggestedCategoryId"
4. Se precisar criar nova categoria, use nome em PORTUGUÊS
5. Marque "isNewCategory: true" APENAS se a categoria não existe
6. Para novas categorias, sugira nomes claros em português (ex: "Supermercado", "Restaurante", "Salário", "Aluguel")
7. Analise padrões: "PIX", "TED", "Transferência" geralmente são Transferências
8. Lojas conhecidas: UBER=Transporte, IFOOD=Alimentação, NETFLIX=Entretenimento

Responda APENAS com JSON válido:
{
  "categorizedTransactions": [
    {
      "date": "data original",
      "description": "descrição original",
      "amount": valor_numerico,
      "type": "income" ou "expense",
      "suggestedCategory": "Nome da Categoria",
      "suggestedCategoryId": "id-da-categoria-existente-ou-null",
      "isNewCategory": false,
      "confidence": 0.9
    }
  ],
  "newCategories": []
}`;

    try {
        console.log('[AI] Enviando prompt para Ollama...');
        const response = await generateWithOllama(prompt);
        console.log('[AI] Resposta bruta:', response.substring(0, 500));

        // Extrair JSON da resposta - tentar múltiplos padrões
        let jsonMatch = response.match(/\{[\s\S]*\}/);
        if (!jsonMatch) {
            // Tentar extrair de bloco de código
            const codeBlockMatch = response.match(/```(?:json)?\s*([\s\S]*?)```/);
            if (codeBlockMatch) {
                jsonMatch = codeBlockMatch[1].match(/\{[\s\S]*\}/);
            }
        }

        if (!jsonMatch) {
            console.error('[AI] Resposta não contém JSON:', response);
            throw new Error('Resposta da IA não contém JSON válido');
        }

        console.log('[AI] JSON extraído:', jsonMatch[0].substring(0, 300));
        const result = JSON.parse(jsonMatch[0]);

        // Garantir que as transações tenham os campos necessários
        const aiCategorized = (result.categorizedTransactions || []).map((t: any) => ({
            ...t,
            suggestedCategoryId: t.suggestedCategoryId || null,
            isNewCategory: t.isNewCategory || false,
            matchedByRule: false
        }));

        // Combinar transações categorizadas por regras + IA
        return {
            categorizedTransactions: [...alreadyCategorized, ...aiCategorized],
            newCategories: result.newCategories || []
        };
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
