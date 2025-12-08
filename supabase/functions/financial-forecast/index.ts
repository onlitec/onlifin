import "jsr:@supabase/functions-js/edge-runtime.d.ts";
import { createClient } from "npm:@supabase/supabase-js@2.39.3";

// Tipos
interface Transaction {
  id: string;
  amount: number;
  date: string;
  type: string;
  category_id: string | null;
}

interface Bill {
  amount: number;
  due_date: string;
  status: string;
}

interface ForecastResult {
  forecast_daily: Record<string, number>;
  forecast_weekly: Record<string, number>;
  forecast_monthly: Record<string, number>;
  insights: string[];
  alerts: Array<{ tipo: string; descricao: string; gravidade: string }>;
  risk_negative: boolean;
  risk_date: string | null;
  spending_patterns: Record<string, unknown>;
}

Deno.serve(async (req: Request) => {
  try {
    // Parse request body
    const { user_id } = await req.json();

    if (!user_id) {
      return new Response(
        JSON.stringify({ error: "user_id é obrigatório" }),
        { status: 400, headers: { "Content-Type": "application/json" } }
      );
    }

    // Inicializar cliente Supabase
    const supabaseUrl = Deno.env.get("SUPABASE_URL")!;
    const supabaseKey = Deno.env.get("SUPABASE_SERVICE_ROLE_KEY")!;
    const supabase = createClient(supabaseUrl, supabaseKey);

    console.log(`Gerando previsão financeira para usuário: ${user_id}`);

    // 1. Buscar saldo atual total
    const { data: accounts, error: accountsError } = await supabase
      .from("accounts")
      .select("balance")
      .eq("user_id", user_id);

    if (accountsError) throw accountsError;

    const currentBalance = accounts?.reduce((sum, acc) => sum + Number(acc.balance), 0) || 0;

    // 2. Buscar transações dos últimos 6 meses
    const sixMonthsAgo = new Date();
    sixMonthsAgo.setMonth(sixMonthsAgo.getMonth() - 6);

    const { data: transactions, error: transactionsError } = await supabase
      .from("transactions")
      .select("id, amount, date, type, category_id")
      .eq("user_id", user_id)
      .gte("date", sixMonthsAgo.toISOString().split("T")[0])
      .order("date", { ascending: true });

    if (transactionsError) throw transactionsError;

    // 3. Buscar contas a pagar pendentes
    const { data: billsToPay, error: billsToPayError } = await supabase
      .from("bills_to_pay")
      .select("amount, due_date, status")
      .eq("user_id", user_id)
      .in("status", ["pending", "overdue"]);

    if (billsToPayError) throw billsToPayError;

    // 4. Buscar contas a receber pendentes
    const { data: billsToReceive, error: billsToReceiveError } = await supabase
      .from("bills_to_receive")
      .select("amount, due_date, status")
      .eq("user_id", user_id)
      .in("status", ["pending", "overdue"]);

    if (billsToReceiveError) throw billsToReceiveError;

    // 5. Analisar padrões e gerar previsões
    const forecast = generateForecast(
      currentBalance,
      transactions || [],
      billsToPay || [],
      billsToReceive || []
    );

    // 6. Salvar previsão no banco
    const { data: savedForecast, error: saveForecastError } = await supabase
      .from("financial_forecasts")
      .insert({
        user_id,
        calculation_date: new Date().toISOString(),
        initial_balance: currentBalance,
        forecast_daily: forecast.forecast_daily,
        forecast_weekly: forecast.forecast_weekly,
        forecast_monthly: forecast.forecast_monthly,
        insights: forecast.insights,
        alerts: forecast.alerts,
        risk_negative: forecast.risk_negative,
        risk_date: forecast.risk_date,
        spending_patterns: forecast.spending_patterns,
      })
      .select()
      .single();

    if (saveForecastError) throw saveForecastError;

    // 7. Criar notificações para alertas de alta gravidade
    for (const alert of forecast.alerts) {
      if (alert.gravidade === "alta") {
        await supabase.rpc("create_notification", {
          p_user_id: user_id,
          p_title: "⚠️ Alerta Financeiro",
          p_message: alert.descricao,
          p_type: "alert",
          p_severity: "high",
          p_related_forecast_id: savedForecast.id,
        });
      }
    }

    console.log(`Previsão gerada com sucesso para usuário: ${user_id}`);

    return new Response(
      JSON.stringify({
        success: true,
        forecast_id: savedForecast.id,
        message: "Previsão financeira gerada com sucesso",
      }),
      { status: 200, headers: { "Content-Type": "application/json" } }
    );
  } catch (error) {
    console.error("Erro ao gerar previsão:", error);
    return new Response(
      JSON.stringify({ error: error.message || "Erro interno do servidor" }),
      { status: 500, headers: { "Content-Type": "application/json" } }
    );
  }
});

// Função principal de geração de previsão
function generateForecast(
  currentBalance: number,
  transactions: Transaction[],
  billsToPay: Bill[],
  billsToReceive: Bill[]
): ForecastResult {
  const insights: string[] = [];
  const alerts: Array<{ tipo: string; descricao: string; gravidade: string }> = [];

  // Calcular médias de entrada e saída
  const incomeTransactions = transactions.filter((t) => t.type === "income");
  const expenseTransactions = transactions.filter((t) => t.type === "expense");

  const avgMonthlyIncome =
    incomeTransactions.reduce((sum, t) => sum + Number(t.amount), 0) / 6;
  const avgMonthlyExpense =
    expenseTransactions.reduce((sum, t) => sum + Number(t.amount), 0) / 6;

  const avgDailyIncome = avgMonthlyIncome / 30;
  const avgDailyExpense = avgMonthlyExpense / 30;

  // Análise de categorias
  const categoryExpenses: Record<string, number> = {};
  expenseTransactions.forEach((t) => {
    const catId = t.category_id || "sem_categoria";
    categoryExpenses[catId] = (categoryExpenses[catId] || 0) + Number(t.amount);
  });

  // Identificar categoria com maior gasto
  let maxCategory = "";
  let maxAmount = 0;
  for (const [cat, amount] of Object.entries(categoryExpenses)) {
    if (amount > maxAmount) {
      maxAmount = amount;
      maxCategory = cat;
    }
  }

  // Gerar insights
  if (avgMonthlyExpense > avgMonthlyIncome) {
    insights.push(
      `⚠️ Suas despesas mensais (R$ ${avgMonthlyExpense.toFixed(2)}) excedem suas receitas (R$ ${avgMonthlyIncome.toFixed(2)})`
    );
    alerts.push({
      tipo: "despesa_alta",
      descricao: "Despesas mensais excedem receitas",
      gravidade: "alta",
    });
  } else {
    insights.push(
      `✅ Suas receitas mensais (R$ ${avgMonthlyIncome.toFixed(2)}) cobrem suas despesas (R$ ${avgMonthlyExpense.toFixed(2)})`
    );
  }

  if (maxCategory && maxAmount > avgMonthlyExpense * 0.3) {
    insights.push(
      `📊 Uma categoria representa ${((maxAmount / (avgMonthlyExpense * 6)) * 100).toFixed(1)}% dos seus gastos totais`
    );
  }

  // Previsão diária (30 dias)
  const forecast_daily: Record<string, number> = {};
  let runningBalance = currentBalance;
  let riskDate: string | null = null;

  for (let i = 0; i < 30; i++) {
    const date = new Date();
    date.setDate(date.getDate() + i);
    const dateStr = date.toISOString().split("T")[0];

    // Aplicar média diária
    runningBalance += avgDailyIncome - avgDailyExpense;

    // Aplicar contas a pagar
    const paymentsDue = billsToPay.filter((b) => b.due_date === dateStr);
    paymentsDue.forEach((b) => {
      runningBalance -= Number(b.amount);
    });

    // Aplicar contas a receber
    const receiptsDue = billsToReceive.filter((b) => b.due_date === dateStr);
    receiptsDue.forEach((b) => {
      runningBalance += Number(b.amount);
    });

    forecast_daily[dateStr] = Math.round(runningBalance * 100) / 100;

    // Detectar risco de saldo negativo
    if (runningBalance < 0 && !riskDate) {
      riskDate = dateStr;
      alerts.push({
        tipo: "risco",
        descricao: `Saldo negativo previsto para ${new Date(dateStr).toLocaleDateString("pt-BR")}`,
        gravidade: "alta",
      });
    }
  }

  // Previsão semanal (12 semanas)
  const forecast_weekly: Record<string, number> = {};
  runningBalance = currentBalance;

  for (let week = 1; week <= 12; week++) {
    const weeklyIncome = avgDailyIncome * 7;
    const weeklyExpense = avgDailyExpense * 7;
    runningBalance += weeklyIncome - weeklyExpense;

    forecast_weekly[`semana_${week}`] = Math.round(runningBalance * 100) / 100;
  }

  // Previsão mensal (6 meses)
  const forecast_monthly: Record<string, number> = {};
  runningBalance = currentBalance;
  const monthNames = [
    "janeiro",
    "fevereiro",
    "março",
    "abril",
    "maio",
    "junho",
    "julho",
    "agosto",
    "setembro",
    "outubro",
    "novembro",
    "dezembro",
  ];

  for (let month = 0; month < 6; month++) {
    runningBalance += avgMonthlyIncome - avgMonthlyExpense;
    const date = new Date();
    date.setMonth(date.getMonth() + month);
    const monthName = monthNames[date.getMonth()];

    forecast_monthly[monthName] = Math.round(runningBalance * 100) / 100;
  }

  // Alertas adicionais
  if (billsToPay.length > 0) {
    const overdueCount = billsToPay.filter((b) => b.status === "overdue").length;
    if (overdueCount > 0) {
      alerts.push({
        tipo: "contas_atrasadas",
        descricao: `Você tem ${overdueCount} conta(s) em atraso`,
        gravidade: "alta",
      });
      insights.push(`⚠️ ${overdueCount} conta(s) a pagar em atraso`);
    }
  }

  // Padrões de gastos
  const spending_patterns = {
    avg_monthly_income: Math.round(avgMonthlyIncome * 100) / 100,
    avg_monthly_expense: Math.round(avgMonthlyExpense * 100) / 100,
    avg_daily_income: Math.round(avgDailyIncome * 100) / 100,
    avg_daily_expense: Math.round(avgDailyExpense * 100) / 100,
    category_expenses: categoryExpenses,
  };

  return {
    forecast_daily,
    forecast_weekly,
    forecast_monthly,
    insights,
    alerts,
    risk_negative: riskDate !== null,
    risk_date: riskDate,
    spending_patterns,
  };
}
