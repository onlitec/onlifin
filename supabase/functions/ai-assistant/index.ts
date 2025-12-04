import "jsr:@supabase/functions-js/edge-runtime.d.ts";
import { createClient } from 'npm:@supabase/supabase-js@2';

const APP_ID = Deno.env.get('VITE_APP_ID') || 'app-7xkeeoe4bsap';
const GEMINI_API_URL = `https://api-integrations.appmedo.com/${APP_ID}/api-rLob8RdzAOl9/v1beta/models/gemini-2.5-flash:streamGenerateContent?alt=sse`;

const corsHeaders = {
  'Access-Control-Allow-Origin': '*',
  'Access-Control-Allow-Headers': 'authorization, x-client-info, apikey, content-type',
};

// Função para buscar dados do usuário baseado no nível de permissão
async function getUserFinancialData(supabaseClient: any, userId: string, permissionLevel: string) {
  const data: any = {
    permission_level: permissionLevel,
    user_id: userId
  };

  try {
    if (permissionLevel === 'read_aggregated') {
      // Apenas dados agregados (totais, somatórios, médias)
      const [accounts, cards, transactions, categories] = await Promise.all([
        supabaseClient.from('accounts').select('id, name, balance, currency').eq('user_id', userId),
        supabaseClient.from('cards').select('id, name, card_limit, available_limit').eq('user_id', userId),
        supabaseClient.from('transactions').select('type, amount, category_id, date').eq('user_id', userId),
        supabaseClient.from('categories').select('id, name, type').eq('user_id', userId)
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

    const response = await fetch(GEMINI_API_URL, {
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
    const { message, userId, action, transactions, existingCategories } = requestBody;

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

    // Criar contexto com os dados do usuário
    let contextPrompt = `Você é um assistente financeiro inteligente e prestativo. 
Ajude o usuário com questões relacionadas a finanças pessoais, como:
- Categorização de transações
- Dicas de economia
- Análise de gastos
- Planejamento financeiro
- Explicações sobre conceitos financeiros

Seja conciso, claro e sempre forneça conselhos práticos e responsáveis.

DADOS FINANCEIROS DO USUÁRIO (Nível de acesso: ${permissionLevel}):
${JSON.stringify(userData, null, 2)}

Use esses dados para fornecer respostas personalizadas e relevantes ao contexto financeiro do usuário.
Sempre que possível, referencie os dados reais do usuário em suas respostas.`;

    // Adicionar instruções de criação de transações se permitido
    if (canWriteTransactions) {
      contextPrompt += `

PERMISSÃO DE ESCRITA ATIVADA:
Você tem permissão para criar transações em nome do usuário.

Quando o usuário solicitar o cadastro de uma transação (ex: "registre uma despesa de R$ 150 no supermercado"), 
você deve responder com um JSON no seguinte formato:

{
  "action": "create_transaction",
  "transaction_data": {
    "type": "expense" ou "income",
    "amount": valor numérico (ex: 150.00),
    "date": "YYYY-MM-DD" (use a data atual se não especificada),
    "description": "descrição da transação",
    "account_id": "id da conta" (use a primeira conta disponível se não especificada),
    "category_id": "id da categoria" (tente identificar a categoria apropriada)
  },
  "confirmation_message": "Mensagem amigável confirmando o que será registrado"
}

IMPORTANTE:
- Sempre confirme os detalhes antes de criar
- Use as contas e categorias disponíveis nos dados do usuário
- Se não houver conta ou categoria apropriada, informe o usuário
- Valide que o valor é positivo
- Use a data atual se não especificada

Contas disponíveis: ${JSON.stringify(userData.accounts_list || [])}
Categorias disponíveis: ${JSON.stringify(userData.categories_list || [])}`;
    }

    const response = await fetch(GEMINI_API_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-App-Id': APP_ID
      },
      body: JSON.stringify({
        contents: [
          {
            role: 'user',
            parts: [
              { text: contextPrompt }
            ]
          },
          {
            role: 'model',
            parts: [
              { text: 'Entendido. Estou pronto para ajudar com questões financeiras usando os dados fornecidos.' + (canWriteTransactions ? ' Posso também criar transações quando solicitado.' : '') }
            ]
          },
          {
            role: 'user',
            parts: [
              { text: message }
            ]
          }
        ]
      })
    });

    if (!response.ok) {
      const errorText = await response.text();
      console.error('Gemini API error:', errorText);
      throw new Error(`Gemini API error: ${response.status}`);
    }

    const reader = response.body?.getReader();
    const decoder = new TextDecoder();
    let fullResponse = '';

    if (reader) {
      while (true) {
        const { done, value } = await reader.read();
        if (done) break;

        const chunk = decoder.decode(value);
        const lines = chunk.split('\n');

        for (const line of lines) {
          if (line.startsWith('data: ')) {
            try {
              const jsonData = JSON.parse(line.slice(6));
              const text = jsonData.candidates?.[0]?.content?.parts?.[0]?.text;
              if (text) {
                fullResponse += text;
              }
            } catch (e) {
              console.error('Error parsing SSE data:', e);
            }
          }
        }
      }
    }

    // Verificar se a resposta contém uma solicitação de criação de transação
    let createdTransactionId = null;
    let actionType = 'read';
    let finalResponse = fullResponse || 'Desculpe, não consegui gerar uma resposta.';

    if (canWriteTransactions && fullResponse.includes('"action": "create_transaction"')) {
      try {
        // Tentar extrair o JSON da resposta
        const jsonMatch = fullResponse.match(/\{[\s\S]*"action":\s*"create_transaction"[\s\S]*\}/);
        if (jsonMatch) {
          const actionData = JSON.parse(jsonMatch[0]);
          
          if (actionData.transaction_data) {
            // Criar a transação
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
        }
      } catch (error: any) {
        console.error('Erro ao processar criação de transação:', error);
        finalResponse = `❌ Erro ao processar solicitação de criação de transação: ${error.message}`;
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
