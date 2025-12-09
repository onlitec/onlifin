import "jsr:@supabase/functions-js/edge-runtime.d.ts";
import { createClient } from 'npm:@supabase/supabase-js@2';

const APP_ID = Deno.env.get('VITE_APP_ID') || 'app-7xkeeoe4bsap';
// Usar modelo estável e rápido
const GEMINI_API_URL = `https://api-integrations.appmedo.com/${APP_ID}/api-rLob8RdzAOl9/v1beta/models/gemini-1.5-flash:generateContent`;

const corsHeaders = {
  'Access-Control-Allow-Origin': '*',
  'Access-Control-Allow-Headers': 'authorization, x-client-info, apikey, content-type',
};

// Função auxiliar para retry com backoff exponencial
async function fetchWithRetry(url: string, options: any, maxRetries = 3): Promise<Response> {
  let lastError: Error | null = null;
  let lastStatus: number | null = null;
  
  for (let attempt = 0; attempt < maxRetries; attempt++) {
    try {
      console.log(`Tentativa ${attempt + 1} de ${maxRetries}`);
      const response = await fetch(url, options);
      
      lastStatus = response.status;
      console.log(`Status da resposta: ${response.status}`);
      
      // Se for 503 (Service Unavailable) ou 429 (Rate Limit), tentar novamente
      if (response.status === 503 || response.status === 429) {
        if (attempt < maxRetries - 1) {
          const delay = Math.min(1000 * Math.pow(2, attempt), 5000); // Max 5 segundos
          console.log(`Aguardando ${delay}ms antes da próxima tentativa...`);
          await new Promise(resolve => setTimeout(resolve, delay));
          continue;
        }
      }
      
      return response;
    } catch (error: any) {
      lastError = error;
      console.error(`Erro na tentativa ${attempt + 1}:`, error.message);
      if (attempt < maxRetries - 1) {
        const delay = Math.min(1000 * Math.pow(2, attempt), 5000);
        console.log(`Aguardando ${delay}ms antes da próxima tentativa...`);
        await new Promise(resolve => setTimeout(resolve, delay));
      }
    }
  }
  
  // Se chegou aqui, todas as tentativas falharam
  if (lastStatus === 503) {
    throw new Error('O serviço está temporariamente indisponível. Por favor, tente novamente em alguns instantes.');
  } else if (lastStatus === 429) {
    throw new Error('Muitas requisições. Por favor, aguarde um momento antes de tentar novamente.');
  }
  
  throw lastError || new Error('Falha após múltiplas tentativas');
}

// Função de fallback para modo degradado (quando IA está indisponível)
function getDegradedResponse(message: string, userData: any): string {
  const lowerMessage = message.toLowerCase();
  
  // Perguntas sobre saldo
  if (lowerMessage.includes('saldo')) {
    const summary = userData.financial_summary;
    if (summary) {
      return `💰 **Resumo Financeiro**\n\n` +
             `Saldo total: R$ ${summary.total_balance?.toFixed(2) || '0.00'}\n` +
             `Receitas: R$ ${summary.total_income?.toFixed(2) || '0.00'}\n` +
             `Despesas: R$ ${summary.total_expense?.toFixed(2) || '0.00'}\n` +
             `Saldo líquido: R$ ${summary.net_balance?.toFixed(2) || '0.00'}\n\n` +
             `_Resposta automática - Assistente de IA temporariamente indisponível_`;
    }
  }
  
  // Perguntas sobre despesas/gastos
  if (lowerMessage.includes('despesa') || lowerMessage.includes('gasto') || lowerMessage.includes('gastei')) {
    const summary = userData.financial_summary;
    if (summary) {
      return `📊 **Suas Despesas**\n\n` +
             `Total de despesas: R$ ${summary.total_expense?.toFixed(2) || '0.00'}\n` +
             `Número de transações: ${summary.transaction_count || 0}\n\n` +
             `_Resposta automática - Assistente de IA temporariamente indisponível_`;
    }
  }
  
  // Perguntas sobre receitas
  if (lowerMessage.includes('receita') || lowerMessage.includes('ganho') || lowerMessage.includes('renda')) {
    const summary = userData.financial_summary;
    if (summary) {
      return `💵 **Suas Receitas**\n\n` +
             `Total de receitas: R$ ${summary.total_income?.toFixed(2) || '0.00'}\n\n` +
             `_Resposta automática - Assistente de IA temporariamente indisponível_`;
    }
  }
  
  // Resposta padrão
  return `🤖 Desculpe, estou temporariamente indisponível devido a problemas técnicos.\n\n` +
         `Por favor, tente novamente em alguns instantes ou use as funcionalidades manuais da plataforma:\n\n` +
         `• **Transações**: Visualize e gerencie suas transações\n` +
         `• **Contas**: Veja seus saldos e contas\n` +
         `• **Relatórios**: Acesse relatórios financeiros\n\n` +
         `_O assistente de IA voltará em breve!_`;
}

// Função para buscar dados do usuário baseado no nível de permissão
async function getUserFinancialData(supabaseClient: any, userId: string, permissionLevel: string) {
  const data: any = {
    permission_level: permissionLevel,
    user_id: userId
  };

  try {
    if (permissionLevel === 'read_aggregated') {
      // Apenas dados agregados (totais, somatórios, médias) - otimizado
      const [accounts, cards, transactions, categories] = await Promise.all([
        supabaseClient.from('accounts').select('id, name, balance').eq('user_id', userId).limit(10),
        supabaseClient.from('cards').select('id, name, available_limit').eq('user_id', userId).limit(5),
        supabaseClient.from('transactions').select('type, amount, category_id').eq('user_id', userId).limit(100),
        supabaseClient.from('categories').select('id, name, type').eq('user_id', userId).limit(20)
      ]);

      // Calcular estatísticas agregadas
      const totalBalance = accounts.data?.reduce((sum: number, acc: any) => sum + (acc.balance || 0), 0) || 0;
      const totalIncome = transactions.data?.filter((t: any) => t.type === 'income').reduce((sum: number, t: any) => sum + t.amount, 0) || 0;
      const totalExpense = transactions.data?.filter((t: any) => t.type === 'expense').reduce((sum: number, t: any) => sum + t.amount, 0) || 0;
      
      // Agrupar despesas por categoria
      const expensesByCategory: any = {};
      transactions.data?.filter((t: any) => t.type === 'expense').forEach((t: any) => {
        const catId = t.category_id || 'sem_categoria';
        expensesByCategory[catId] = (expensesByCategory[catId] || 0) + t.amount;
      });

      data.financial_summary = {
        total_accounts: accounts.data?.length || 0,
        total_cards: cards.data?.length || 0,
        total_balance: totalBalance,
        total_income: totalIncome,
        total_expense: totalExpense,
        net_balance: totalIncome - totalExpense,
        expenses_by_category: expensesByCategory,
        transaction_count: transactions.data?.length || 0
      };

      // Incluir lista de contas e categorias para criação de transações
      data.accounts_list = accounts.data?.map((a: any) => ({ id: a.id, name: a.name })) || [];
      data.categories_list = categories.data?.map((c: any) => ({ id: c.id, name: c.name, type: c.type })) || [];

    } else if (permissionLevel === 'read_transactional') {
      // Dados transacionais (lista de transações com detalhes, mas sem informações sensíveis)
      const [accounts, cards, transactions, categories] = await Promise.all([
        supabaseClient.from('accounts').select('id, name, balance, currency').eq('user_id', userId),
        supabaseClient.from('cards').select('id, name, card_limit, available_limit').eq('user_id', userId),
        supabaseClient.from('transactions').select('id, type, amount, description, category_id, date, account_id').eq('user_id', userId).order('date', { ascending: false }).limit(50),
        supabaseClient.from('categories').select('id, name, type').eq('user_id', userId)
      ]);

      data.accounts = accounts.data || [];
      data.cards = cards.data || [];
      data.recent_transactions = transactions.data || [];
      data.categories = categories.data || [];

    } else if (permissionLevel === 'read_full') {
      // Acesso completo a todos os dados
      const [accounts, cards, transactions, categories] = await Promise.all([
        supabaseClient.from('accounts').select('*').eq('user_id', userId),
        supabaseClient.from('cards').select('*').eq('user_id', userId),
        supabaseClient.from('transactions').select('*').eq('user_id', userId).order('date', { ascending: false }),
        supabaseClient.from('categories').select('*').eq('user_id', userId)
      ]);

      data.accounts = accounts.data || [];
      data.cards = cards.data || [];
      data.transactions = transactions.data || [];
      data.categories = categories.data || [];
    }

  } catch (error) {
    console.error('Erro ao buscar dados financeiros:', error);
    data.error = 'Erro ao acessar dados financeiros';
  }

  return data;
}

// Função para criar transação
async function createTransaction(supabaseClient: any, userId: string, transactionData: any) {
  try {
    // Validar dados obrigatórios
    if (!transactionData.type || !transactionData.amount || !transactionData.date) {
      throw new Error('Dados obrigatórios faltando: type, amount, date');
    }

    // Validar tipo
    if (transactionData.type !== 'income' && transactionData.type !== 'expense') {
      throw new Error('Tipo de transação inválido. Use "income" ou "expense"');
    }

    // Validar valor
    const amount = parseFloat(transactionData.amount);
    if (isNaN(amount) || amount <= 0) {
      throw new Error('Valor inválido. Deve ser um número positivo');
    }

    // Preparar dados da transação
    const transaction = {
      user_id: userId,
      type: transactionData.type,
      amount: amount,
      date: transactionData.date,
      description: transactionData.description || null,
      account_id: transactionData.account_id || null,
      card_id: transactionData.card_id || null,
      category_id: transactionData.category_id || null,
      tags: transactionData.tags || null,
      is_recurring: false,
      recurrence_pattern: null,
      is_installment: false,
      installment_number: null,
      total_installments: null,
      parent_transaction_id: null,
      is_reconciled: false
    };

    // Inserir transação
    const { data, error } = await supabaseClient
      .from('transactions')
      .insert(transaction)
      .select()
      .single();

    if (error) {
      console.error('Erro ao criar transação:', error);
      throw new Error(`Erro ao criar transação: ${error.message}`);
    }

    // Atualizar saldo da conta se account_id foi fornecido
    if (transaction.account_id) {
      const balanceChange = transaction.type === 'income' ? amount : -amount;
      
      // Buscar saldo atual
      const { data: accountData } = await supabaseClient
        .from('accounts')
        .select('balance')
        .eq('id', transaction.account_id)
        .single();

      if (accountData) {
        const newBalance = accountData.balance + balanceChange;
        await supabaseClient
          .from('accounts')
          .update({ balance: newBalance })
          .eq('id', transaction.account_id);
      }
    }

    return { success: true, transaction: data };
  } catch (error: any) {
    console.error('Erro em createTransaction:', error);
    return { success: false, error: error.message };
  }
}

// Função para atualizar categoria de transação
async function updateTransactionCategory(supabaseClient: any, userId: string, transactionId: string, categoryId: string) {
  try {
    // Verificar se a transação pertence ao usuário
    const { data: transaction } = await supabaseClient
      .from('transactions')
      .select('id')
      .eq('id', transactionId)
      .eq('user_id', userId)
      .maybeSingle();

    if (!transaction) {
      throw new Error('Transação não encontrada ou não pertence ao usuário');
    }

    // Atualizar categoria
    const { data, error } = await supabaseClient
      .from('transactions')
      .update({ category_id: categoryId })
      .eq('id', transactionId)
      .select()
      .single();

    if (error) {
      throw new Error(`Erro ao atualizar categoria: ${error.message}`);
    }

    return { success: true, transaction: data };
  } catch (error: any) {
    console.error('Erro em updateTransactionCategory:', error);
    return { success: false, error: error.message };
  }
}

// Função para atualizar múltiplas transações (categorização em lote)
async function batchUpdateTransactions(supabaseClient: any, userId: string, updates: Array<{id: string, category_id: string}>) {
  try {
    const results = [];
    
    for (const update of updates) {
      const result = await updateTransactionCategory(supabaseClient, userId, update.id, update.category_id);
      results.push({
        transaction_id: update.id,
        ...result
      });
    }

    const successCount = results.filter(r => r.success).length;
    const failCount = results.filter(r => !r.success).length;

    return {
      success: failCount === 0,
      results,
      summary: {
        total: updates.length,
        success: successCount,
        failed: failCount
      }
    };
  } catch (error: any) {
    console.error('Erro em batchUpdateTransactions:', error);
    return { success: false, error: error.message };
  }
}

// Função para categorizar transações usando IA
async function categorizeTransactions(transactions: any[], existingCategories: any[]) {
  try {
    console.log('Iniciando categorização:', {
      transactionCount: transactions.length,
      categoryCount: existingCategories.length
    });

    const APP_ID = Deno.env.get('VITE_APP_ID') || 'app-7xkeeoe4bsap';
    const GEMINI_API_URL = `https://api-integrations.appmedo.com/${APP_ID}/api-rLob8RdzAOl9/v1beta/models/gemini-2.5-flash:generateContent`;

    const prompt = `Você é um especialista em categorização de transações financeiras.

Analise as seguintes transações e sugira a categoria mais apropriada para cada uma.

CATEGORIAS EXISTENTES:
${JSON.stringify(existingCategories, null, 2)}

TRANSAÇÕES PARA CATEGORIZAR:
${JSON.stringify(transactions, null, 2)}

Para cada transação, você deve:
1. Analisar a descrição, merchant e valor
2. Identificar a categoria mais apropriada das categorias existentes
3. Se nenhuma categoria existente se encaixar bem, sugerir uma nova categoria

Responda APENAS com um JSON válido no seguinte formato:
{
  "categorizedTransactions": [
    {
      "date": "data da transação",
      "description": "descrição",
      "amount": valor,
      "type": "income" ou "expense",
      "merchant": "estabelecimento",
      "suggestedCategory": "nome da categoria",
      "suggestedCategoryId": "id da categoria existente ou null se for nova",
      "isNewCategory": true ou false,
      "confidence": 0.0 a 1.0 (confiança na sugestão)
    }
  ],
  "newCategories": [
    {
      "name": "nome da nova categoria",
      "type": "income" ou "expense",
      "selected": true
    }
  ]
}

REGRAS IMPORTANTES:
- Seja preciso na categorização
- Sugira novas categorias apenas quando realmente necessário
- Use categorias existentes sempre que possível
- A confiança deve refletir quão certa está a categorização
- Todas as novas categorias devem vir com selected: true por padrão
- Não invente categorias genéricas demais
- Considere o contexto brasileiro (nomes de estabelecimentos, padrões de gastos)

Responda APENAS com o JSON, sem texto adicional.`;

    console.log('Enviando requisição para Gemini API...');

    const response = await fetchWithRetry(GEMINI_API_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-App-Id': APP_ID
      },
      body: JSON.stringify({
        contents: [
          {
            role: 'user',
            parts: [{ text: prompt }]
          }
        ]
      })
    });

    if (!response.ok) {
      const errorText = await response.text();
      console.error('Erro da Gemini API:', errorText);
      throw new Error(`Gemini API error: ${response.status} - ${errorText}`);
    }

    const data = await response.json();
    console.log('Resposta da Gemini API recebida');
    
    const text = data.candidates?.[0]?.content?.parts?.[0]?.text || '';
    console.log('Texto da resposta:', text.substring(0, 200) + '...');
    
    // Extrair JSON da resposta
    const jsonMatch = text.match(/\{[\s\S]*\}/);
    if (!jsonMatch) {
      console.error('Resposta não contém JSON:', text);
      throw new Error('Resposta da IA não contém JSON válido');
    }

    const result = JSON.parse(jsonMatch[0]);
    console.log('JSON parseado com sucesso:', {
      categorizedCount: result.categorizedTransactions?.length || 0,
      newCategoriesCount: result.newCategories?.length || 0
    });

    return result;
  } catch (error: any) {
    console.error('Erro ao categorizar transações:', error);
    throw error;
  }
}

Deno.serve(async (req: Request) => {
  // Handle CORS preflight requests
  if (req.method === 'OPTIONS') {
    return new Response('ok', { headers: corsHeaders });
  }

  try {
    const requestBody = await req.json();
    const { message, userId, action, transactions, existingCategories, conversationHistory } = requestBody;

    // Handle categorization action
    if (action === 'categorize_transactions') {
      console.log('Recebida requisição de categorização:', {
        transactionCount: transactions?.length || 0,
        categoryCount: existingCategories?.length || 0
      });

      if (!transactions || !Array.isArray(transactions)) {
        console.error('Transactions array inválido');
        return new Response(
          JSON.stringify({ error: 'Transactions array is required' }),
          { 
            status: 400, 
            headers: { ...corsHeaders, 'Content-Type': 'application/json' } 
          }
        );
      }

      try {
        const result = await categorizeTransactions(transactions, existingCategories || []);
        console.log('Categorização concluída com sucesso');
        return new Response(
          JSON.stringify(result),
          { 
            status: 200, 
            headers: { ...corsHeaders, 'Content-Type': 'application/json' } 
          }
        );
      } catch (error: any) {
        console.error('Erro na categorização:', error);
        return new Response(
          JSON.stringify({ error: error.message || 'Erro ao categorizar transações' }),
          { 
            status: 500, 
            headers: { ...corsHeaders, 'Content-Type': 'application/json' } 
          }
        );
      }
    }

    // Handle chat messages
    if (!message || !userId) {
      return new Response(
        JSON.stringify({ error: 'Message and userId are required' }),
        { 
          status: 400, 
          headers: { ...corsHeaders, 'Content-Type': 'application/json' } 
        }
      );
    }

    // Criar cliente Supabase
    const supabaseUrl = Deno.env.get('SUPABASE_URL')!;
    const supabaseKey = Deno.env.get('SUPABASE_SERVICE_ROLE_KEY')!;
    const supabaseClient = createClient(supabaseUrl, supabaseKey);

    // Buscar configuração de IA ativa
    const { data: configData } = await supabaseClient
      .from('ai_configurations')
      .select('permission_level, can_write_transactions')
      .eq('is_active', true)
      .order('created_at', { ascending: false })
      .limit(1)
      .maybeSingle();

    const permissionLevel = configData?.permission_level || 'read_aggregated';
    const canWriteTransactions = configData?.can_write_transactions || false;

    // Buscar dados financeiros do usuário
    const userData = await getUserFinancialData(supabaseClient, userId, permissionLevel);

    // Criar contexto com os dados do usuário (versão otimizada e concisa)
    let contextPrompt = `Você é um assistente financeiro. Ajude com: categorização, economia, análise de gastos, planejamento.

DADOS (${permissionLevel}):
${JSON.stringify(userData.financial_summary || {}, null, 2)}

Contas: ${JSON.stringify(userData.accounts_list || [])}
Categorias: ${JSON.stringify(userData.categories_list || [])}

Seja conciso e prático.`;

    // Adicionar instruções de criação de transações se permitido
    if (canWriteTransactions) {
      contextPrompt += `

PERMISSÃO DE ESCRITA: Você pode criar transações.

Para criar: responda JSON:
{
  "action": "create_transaction",
  "transaction_data": {
    "type": "expense/income",
    "amount": 150.00,
    "date": "YYYY-MM-DD",
    "description": "texto",
    "account_id": "id",
    "category_id": "id"
  },
  "confirmation_message": "mensagem"
}

Para categorizar: responda JSON:
{
  "action": "update_category",
  "transaction_id": "id",
  "category_id": "id",
  "confirmation_message": "mensagem"
}`;
    }

    // Construir histórico de conversa para a API
    const conversationContents = [
      // Mensagem inicial do sistema com contexto
      {
        role: 'user',
        parts: [
          { text: contextPrompt }
        ]
      },
      {
        role: 'model',
        parts: [
          { text: 'Pronto para ajudar!' + (canWriteTransactions ? ' Posso criar transações.' : '') }
        ]
      }
    ];

    // Adicionar histórico de conversa anterior se existir
    if (conversationHistory && Array.isArray(conversationHistory) && conversationHistory.length > 0) {
      // Limitar histórico aos últimos 6 mensagens para respostas mais rápidas
      const recentHistory = conversationHistory.slice(-6);
      
      for (const msg of recentHistory) {
        conversationContents.push({
          role: msg.role === 'user' ? 'user' : 'model',
          parts: [
            { text: msg.content }
          ]
        });
      }
    } else {
      // Se não houver histórico, adicionar apenas a mensagem atual
      conversationContents.push({
        role: 'user',
        parts: [
          { text: message }
        ]
      });
    }

    // Configuração otimizada para respostas rápidas
    const generationConfig = {
      temperature: 0.7,
      topK: 20,
      topP: 0.8,
      maxOutputTokens: 1024,
      candidateCount: 1
    };

    let fullResponse = '';
    let usedDegradedMode = false;

    try {
      // Tentar usar a API Gemini
      const response = await fetchWithRetry(GEMINI_API_URL, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-App-Id': APP_ID
        },
        body: JSON.stringify({
          contents: conversationContents,
          generationConfig: generationConfig
        })
      });

      if (!response.ok) {
        const errorText = await response.text();
        console.error('Gemini API error:', errorText);
        throw new Error(`API returned status ${response.status}`);
      }

      // Processar resposta não-streaming (mais rápido)
      const data = await response.json();
      fullResponse = data.candidates?.[0]?.content?.parts?.[0]?.text || '';
    } catch (apiError: any) {
      // Se a API falhar, usar modo degradado
      console.log('API indisponível, usando modo degradado:', apiError.message);
      fullResponse = getDegradedResponse(message, userData);
      usedDegradedMode = true;
    }

    // Verificar se a resposta contém uma solicitação de ação
    let createdTransactionId = null;
    let actionType = 'read';
    let finalResponse = fullResponse || 'Desculpe, não consegui gerar uma resposta.';

    // Apenas processar ações se não estiver em modo degradado
    if (!usedDegradedMode && canWriteTransactions && fullResponse.includes('"action":')) {
      try {
        // Tentar extrair o JSON da resposta
        const jsonMatch = fullResponse.match(/\{[\s\S]*"action":\s*"[^"]*"[\s\S]*\}/);
        if (jsonMatch) {
          const actionData = JSON.parse(jsonMatch[0]);
          
          // CRIAR TRANSAÇÃO
          if (actionData.action === 'create_transaction' && actionData.transaction_data) {
            const result = await createTransaction(supabaseClient, userId, actionData.transaction_data);
            
            if (result.success) {
              createdTransactionId = result.transaction.id;
              actionType = 'write';
              finalResponse = actionData.confirmation_message || 
                `✅ Transação registrada com sucesso!\n\n` +
                `Tipo: ${result.transaction.type === 'income' ? 'Receita' : 'Despesa'}\n` +
                `Valor: R$ ${result.transaction.amount.toFixed(2)}\n` +
                `Descrição: ${result.transaction.description || 'Sem descrição'}\n` +
                `Data: ${result.transaction.date}`;
            } else {
              finalResponse = `❌ Erro ao criar transação: ${result.error}\n\nPor favor, verifique os dados e tente novamente.`;
            }
          }
          
          // ATUALIZAR CATEGORIA DE UMA TRANSAÇÃO
          else if (actionData.action === 'update_category' && actionData.transaction_id && actionData.category_id) {
            const result = await updateTransactionCategory(supabaseClient, userId, actionData.transaction_id, actionData.category_id);
            
            if (result.success) {
              actionType = 'write';
              finalResponse = actionData.confirmation_message || 
                `✅ Categoria atualizada com sucesso!\n\n` +
                `A transação foi categorizada.`;
            } else {
              finalResponse = `❌ Erro ao atualizar categoria: ${result.error}`;
            }
          }
          
          // CATEGORIZAR EM LOTE
          else if (actionData.action === 'batch_categorize' && actionData.updates && Array.isArray(actionData.updates)) {
            const result = await batchUpdateTransactions(supabaseClient, userId, actionData.updates);
            
            if (result.success) {
              actionType = 'write';
              finalResponse = actionData.confirmation_message || 
                `✅ Categorização em lote concluída!\n\n` +
                `Total: ${result.summary.total}\n` +
                `Sucesso: ${result.summary.success}\n` +
                `Falhas: ${result.summary.failed}`;
            } else {
              finalResponse = `⚠️ Categorização parcialmente concluída:\n\n` +
                `Total: ${result.summary.total}\n` +
                `Sucesso: ${result.summary.success}\n` +
                `Falhas: ${result.summary.failed}\n\n` +
                `Algumas transações não puderam ser categorizadas.`;
            }
          }
        }
      } catch (error: any) {
        console.error('Erro ao processar ação:', error);
        finalResponse = `❌ Erro ao processar solicitação: ${error.message}`;
      }
    }

    return new Response(
      JSON.stringify({ 
        response: finalResponse,
        permission_level: permissionLevel,
        can_write_transactions: canWriteTransactions,
        action_type: actionType,
        created_transaction_id: createdTransactionId,
        data_accessed: Object.keys(userData).filter(k => k !== 'permission_level' && k !== 'user_id')
      }),
      {
        status: 200,
        headers: {
          ...corsHeaders,
          'Content-Type': 'application/json',
          'Connection': 'keep-alive'
        }
      }
    );
  } catch (error: any) {
    console.error('Error in ai-assistant function:', error);
    return new Response(
      JSON.stringify({ error: error.message || 'Internal server error' }),
      {
        status: 500,
        headers: { ...corsHeaders, 'Content-Type': 'application/json' }
      }
    );
  }
});
