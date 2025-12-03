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
      const [accounts, cards, transactions] = await Promise.all([
        supabaseClient.from('accounts').select('id, name, balance, currency').eq('user_id', userId),
        supabaseClient.from('cards').select('id, name, card_limit, available_limit').eq('user_id', userId),
        supabaseClient.from('transactions').select('type, amount, category_id, date').eq('user_id', userId)
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

Deno.serve(async (req: Request) => {
  // Handle CORS preflight requests
  if (req.method === 'OPTIONS') {
    return new Response('ok', { headers: corsHeaders });
  }

  try {
    const { message, userId } = await req.json();

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
      .select('permission_level')
      .eq('is_active', true)
      .order('created_at', { ascending: false })
      .limit(1)
      .maybeSingle();

    const permissionLevel = configData?.permission_level || 'read_aggregated';

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
              { text: 'Entendido. Estou pronto para ajudar com questões financeiras usando os dados fornecidos.' }
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

    return new Response(
      JSON.stringify({ 
        response: fullResponse || 'Desculpe, não consegui gerar uma resposta.',
        permission_level: permissionLevel,
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
  } catch (error) {
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
