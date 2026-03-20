// API Service para Ollama AI local

const OLLAMA_MODEL = 'qwen2.5:0.5b'; // Modelo mais rápido para categorização

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

let cachedModelAvailability: boolean | null = null;

async function isOllamaModelAvailable(): Promise<boolean> {
    if (cachedModelAvailability !== null) {
        return cachedModelAvailability;
    }

    try {
        const response = await fetch('/ollama/api/tags');
        if (!response.ok) {
            cachedModelAvailability = false;
            return false;
        }

        const data = await response.json();
        const models = Array.isArray(data?.models) ? data.models : [];
        cachedModelAvailability = models.some((model: any) => {
            const name = String(model?.name || '');
            return name === OLLAMA_MODEL || name.startsWith(`${OLLAMA_MODEL}:`) || name.startsWith(OLLAMA_MODEL.split(':')[0]);
        });
        return cachedModelAvailability;
    } catch {
        cachedModelAvailability = false;
        return false;
    }
}

// Regras de palavras-chave padrão (fallback quando não há regras no banco)
// IMPORTANTE: Os nomes das categorias devem corresponder às categorias do banco de dados
const DEFAULT_KEYWORD_RULES = [
    // Gás e Combustível -> Transporte (categoria existente)
    { keyword: 'BRASIL GAS', category_name: 'Contas', type: 'expense', match_type: 'contains' },
    { keyword: 'POSTO', category_name: 'Transporte', type: 'expense', match_type: 'contains' },
    { keyword: 'SHELL', category_name: 'Transporte', type: 'expense', match_type: 'contains' },
    { keyword: 'IPIRANGA', category_name: 'Transporte', type: 'expense', match_type: 'contains' },

    // Supermercado -> Alimentação (categoria existente)
    { keyword: 'MERCADO', category_name: 'Alimentação', type: 'expense', match_type: 'contains' },
    { keyword: 'SUPERMERCADO', category_name: 'Alimentação', type: 'expense', match_type: 'contains' },
    { keyword: 'CARREFOUR', category_name: 'Alimentação', type: 'expense', match_type: 'contains' },
    { keyword: 'ATACADAO', category_name: 'Alimentação', type: 'expense', match_type: 'contains' },
    { keyword: 'ASSAI', category_name: 'Alimentação', type: 'expense', match_type: 'contains' },
    { keyword: 'EXTRA', category_name: 'Alimentação', type: 'expense', match_type: 'contains' },

    // Pagamentos Online -> Compras (categoria existente)
    { keyword: 'PAGAR.ME', category_name: 'Compras', type: 'expense', match_type: 'contains' },
    { keyword: 'PAGSEGURO', category_name: 'Compras', type: 'expense', match_type: 'contains' },
    { keyword: 'MERCADOPAGO', category_name: 'Compras', type: 'expense', match_type: 'contains' },
    { keyword: 'PICPAY', category_name: 'Compras', type: 'expense', match_type: 'contains' },
    { keyword: 'STONE', category_name: 'Compras', type: 'expense', match_type: 'contains' },

    // Delivery/Alimentação
    { keyword: 'IFOOD', category_name: 'Alimentação', type: 'expense', match_type: 'contains' },
    { keyword: 'UBER EATS', category_name: 'Alimentação', type: 'expense', match_type: 'contains' },
    { keyword: 'RAPPI', category_name: 'Alimentação', type: 'expense', match_type: 'contains' },
    { keyword: 'RESTAURANTE', category_name: 'Alimentação', type: 'expense', match_type: 'contains' },
    { keyword: 'LANCHONETE', category_name: 'Alimentação', type: 'expense', match_type: 'contains' },
    { keyword: 'PADARIA', category_name: 'Alimentação', type: 'expense', match_type: 'contains' },

    // Transporte
    { keyword: 'UBER ', category_name: 'Transporte', type: 'expense', match_type: 'contains' },
    { keyword: '99 APP', category_name: 'Transporte', type: 'expense', match_type: 'contains' },
    { keyword: '99POP', category_name: 'Transporte', type: 'expense', match_type: 'contains' },

    // Assinaturas -> Lazer (categoria existente)
    { keyword: 'NETFLIX', category_name: 'Lazer', type: 'expense', match_type: 'contains' },
    { keyword: 'SPOTIFY', category_name: 'Lazer', type: 'expense', match_type: 'contains' },
    { keyword: 'AMAZON PRIME', category_name: 'Lazer', type: 'expense', match_type: 'contains' },
    { keyword: 'DISNEY', category_name: 'Lazer', type: 'expense', match_type: 'contains' },
    { keyword: 'HBO', category_name: 'Lazer', type: 'expense', match_type: 'contains' },
    { keyword: 'GLOBOPLAY', category_name: 'Lazer', type: 'expense', match_type: 'contains' },

    // Transferências Recebidas -> Outros Rendimentos (categoria existente)
    { keyword: 'Transferência Recebida', category_name: 'Outros Rendimentos', type: 'income', match_type: 'starts_with' },
    { keyword: 'Transferência recebida', category_name: 'Outros Rendimentos', type: 'income', match_type: 'contains' },
    { keyword: 'TED recebid', category_name: 'Outros Rendimentos', type: 'income', match_type: 'contains' },
    { keyword: 'PIX recebid', category_name: 'Outros Rendimentos', type: 'income', match_type: 'contains' },

    // Transferências Enviadas -> Outros Gastos (categoria existente)
    { keyword: 'Transferência enviada', category_name: 'Outros Gastos', type: 'expense', match_type: 'contains' },
    { keyword: 'TED enviad', category_name: 'Outros Gastos', type: 'expense', match_type: 'contains' },
    { keyword: 'PIX enviad', category_name: 'Outros Gastos', type: 'expense', match_type: 'contains' },

    // Compras genéricas
    { keyword: 'Compra no débito', category_name: 'Compras', type: 'expense', match_type: 'starts_with' },
    { keyword: 'Compra no crédito', category_name: 'Compras', type: 'expense', match_type: 'starts_with' },

    // Saques -> Outros Gastos
    { keyword: 'SAQUE', category_name: 'Outros Gastos', type: 'expense', match_type: 'contains' },

    // Salário
    { keyword: 'SALARIO', category_name: 'Salário', type: 'income', match_type: 'contains' },
    { keyword: 'SALÁRIO', category_name: 'Salário', type: 'income', match_type: 'contains' },
    { keyword: 'FOLHA', category_name: 'Salário', type: 'income', match_type: 'contains' },
];

/**
 * Chama a API do Ollama para gerar uma resposta usando o endpoint de chat
 */
export async function chatWithOllama(
    messages: OllamaMessage[]
): Promise<string> {
    const modelAvailable = await isOllamaModelAvailable();
    if (!modelAvailable) {
        console.warn(`Modelo Ollama indisponível: ${OLLAMA_MODEL}. Usando fallback local.`);
        return '';
    }

    const requestBody: OllamaChatRequest = {
        model: OLLAMA_MODEL,
        messages,
        stream: false,
        options: {
            temperature: 0.3,
            num_predict: 2000 // Limitar resposta para evitar timeout
        }
    };

    try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 60000); // 60s timeout

        const response = await fetch('/ollama/api/chat', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestBody),
            signal: controller.signal
        });

        clearTimeout(timeoutId);

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
 * Encontra categoria por nome (busca flexível)
 */
function findCategoryByName(categories: any[], name: string, type?: string): any | null {
    const normalizedName = name.toLowerCase().trim();

    return categories.find(c => {
        const catName = (c.name || '').toLowerCase().trim();
        const matchesType = !type || c.type === type;

        return matchesType && (
            catName === normalizedName ||
            catName.includes(normalizedName) ||
            normalizedName.includes(catName)
        );
    }) || null;
}

/**
 * Aplica regras de palavras-chave (internas + do usuário)
 */
function applyKeywordRules(
    transactions: any[],
    existingCategories: any[],
    userRules?: any[]
): { categorized: any[], needsAI: any[] } {
    // Combinar regras do usuário com regras padrão
    const allRules = [...(userRules || []), ...DEFAULT_KEYWORD_RULES];

    const categorized: any[] = [];
    const needsAI: any[] = [];

    for (const t of transactions) {
        const description = (t.description || '').toUpperCase();
        let matched = false;

        for (const rule of allRules) {
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
                // Tentar encontrar categoria existente pelo ID ou nome
                let category = rule.category_id
                    ? existingCategories.find(c => c.id === rule.category_id)
                    : findCategoryByName(existingCategories, rule.category_name, rule.type);

                categorized.push({
                    ...t,
                    suggestedCategory: category?.name || rule.category_name,
                    suggestedCategoryId: category?.id || null,
                    isNewCategory: !category,
                    confidence: 1.0,
                    matchedByRule: true,
                    matchedKeyword: rule.keyword
                });
                matched = true;
                break;
            }
        }

        if (!matched) {
            needsAI.push({ ...t, matchedByRule: false });
        }
    }

    return { categorized, needsAI };
}

/**
 * Categoriza transações usando IA local com aprendizado por exemplos
 */
export async function categorizeTransactionsWithAI(
    transactions: any[],
    existingCategories: any[],
    userExamples?: any[],
    keywordRules?: any[]
): Promise<{
    categorizedTransactions: any[];
    newCategories: any[];
}> {
    console.log(`[AI] Iniciando categorização de ${transactions.length} transações...`);

    // 1. Aplicar regras de palavras-chave primeiro (rápido e preciso)
    const { categorized: byRules, needsAI } = applyKeywordRules(
        transactions,
        existingCategories,
        keywordRules
    );

    console.log(`[AI] ${byRules.length} categorizadas por regras, ${needsAI.length} precisam de IA`);

    // Se todas foram categorizadas por regras, retornar direto
    if (needsAI.length === 0) {
        return {
            categorizedTransactions: byRules,
            newCategories: []
        };
    }

    // 2. Para transações que precisam de IA, processar em lotes pequenos
    const BATCH_SIZE = 3;
    const aiCategorized: any[] = [];

    for (let i = 0; i < needsAI.length; i += BATCH_SIZE) {
        const batch = needsAI.slice(i, i + BATCH_SIZE);

        try {
            const batchResult = await categorizeSmallBatch(batch, existingCategories, userExamples);
            aiCategorized.push(...batchResult);
        } catch (error) {
            console.error(`[AI] Erro no lote ${i / BATCH_SIZE + 1}:`, error);
            // Fallback: marcar como não categorizado
            for (const t of batch) {
                aiCategorized.push({
                    ...t,
                    suggestedCategory: 'Não categorizado',
                    suggestedCategoryId: null,
                    isNewCategory: false,
                    confidence: 0,
                    matchedByRule: false
                });
            }
        }
    }

    return {
        categorizedTransactions: [...byRules, ...aiCategorized],
        newCategories: []
    };
}

/**
 * Categoriza um lote pequeno de transações com IA
 */
async function categorizeSmallBatch(
    transactions: any[],
    existingCategories: any[],
    _userExamples?: any[] // Prefixo _ indica que é opcional e pode não ser usado
): Promise<any[]> {
    const categoryList = existingCategories
        .map(c => `${c.name} (${c.type === 'income' ? 'receita' : 'despesa'})`)
        .join(', ');

    const formattedTransactions = transactions.map((t, i) =>
        `${i + 1}. ${t.description.substring(0, 50)} | R$ ${Math.abs(t.amount).toFixed(2)} | ${t.amount >= 0 ? 'RECEITA' : 'DESPESA'}`
    ).join('\n');

    // Prompt simplificado para resposta mais rápida
    const prompt = `Categorize estas transações brasileiras.

CATEGORIAS: ${categoryList}

TRANSAÇÕES:
${formattedTransactions}

Responda em JSON: {"results": [{"index": 1, "category": "Nome", "confidence": 0.9}]}`;

    console.log(`[AI] Enviando ${transactions.length} transações para IA...`);

    const response = await generateWithOllama(prompt,
        'Você categoriza transações financeiras. Responda APENAS em JSON válido, sem explicações.'
    );

    console.log('[AI] Resposta:', response.substring(0, 200));

    // Extrair e processar JSON - tentar múltiplos métodos
    let allResults: any[] = [];

    // Remover blocos de código markdown se existirem
    let cleanResponse = response
        .replace(/```json\s*/gi, '')
        .replace(/```\s*/g, '')
        .trim();

    // Método 1: Tentar encontrar TODOS os objetos JSON com "results"
    const jsonObjects = cleanResponse.match(/\{[^{}]*"results"\s*:\s*\[[^\]]*\][^{}]*\}/g);

    if (jsonObjects && jsonObjects.length > 0) {
        // Múltiplos objetos JSON - combinar todos os results
        for (const jsonStr of jsonObjects) {
            try {
                const parsed = JSON.parse(jsonStr);
                if (parsed.results && Array.isArray(parsed.results)) {
                    allResults.push(...parsed.results);
                }
            } catch (e) {
                // Ignorar objetos malformados
            }
        }

        if (allResults.length > 0) {
            console.log(`[AI] Combinados ${jsonObjects.length} objetos JSON em ${allResults.length} resultados`);
        }
    }

    // Método 2: Se não encontrou múltiplos, tentar JSON único
    if (allResults.length === 0) {
        let jsonMatch = cleanResponse.match(/\{[\s\S]*\}/);

        if (jsonMatch) {
            let jsonString = jsonMatch[0];

            // Tentar corrigir JSON truncado ou malformado
            try {
                const result = JSON.parse(jsonString);
                allResults = result.results || result.categorizedTransactions || [];
            } catch (parseError) {
                // Tentar corrigir JSON incompleto
                let fixedJson = jsonString;

                // Contar chaves e colchetes para corrigir
                const openBraces = (fixedJson.match(/\{/g) || []).length;
                const closeBraces = (fixedJson.match(/\}/g) || []).length;
                const openBrackets = (fixedJson.match(/\[/g) || []).length;
                const closeBrackets = (fixedJson.match(/\]/g) || []).length;

                // Adicionar fechamentos faltantes
                for (let i = 0; i < openBrackets - closeBrackets; i++) {
                    fixedJson += ']';
                }
                for (let i = 0; i < openBraces - closeBraces; i++) {
                    fixedJson += '}';
                }

                // Remover vírgula final antes de ] ou }
                fixedJson = fixedJson.replace(/,\s*([}\]])/g, '$1');

                try {
                    const result = JSON.parse(fixedJson);
                    allResults = result.results || result.categorizedTransactions || [];
                    console.log('[AI] JSON corrigido com sucesso');
                } catch (fixError) {
                    console.error('[AI] Não foi possível parsear JSON:', jsonString.substring(0, 100));
                }
            }
        }
    }

    const aiResults = allResults;

    // Mapear resultados de volta para as transações
    return transactions.map((t, index) => {
        const aiResult = aiResults.find((r: any) => r.index === index + 1) || aiResults[index];

        if (aiResult) {
            const category = findCategoryByName(existingCategories, aiResult.category || aiResult.suggestedCategory);
            return {
                ...t,
                suggestedCategory: category?.name || aiResult.category || 'Não categorizado',
                suggestedCategoryId: category?.id || null,
                isNewCategory: !category && aiResult.category,
                confidence: aiResult.confidence || 0.7,
                matchedByRule: false
            };
        }

        return {
            ...t,
            suggestedCategory: 'Não categorizado',
            suggestedCategoryId: null,
            isNewCategory: false,
            confidence: 0,
            matchedByRule: false
        };
    });
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
