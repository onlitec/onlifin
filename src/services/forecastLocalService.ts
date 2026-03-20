import { supabase } from '@/db/client';
import { chatWithOllama } from './ollamaService';
import type { FinancialForecast, ForecastAlert } from '@/types/types';

export interface ForecastOptions {
    userId: string;
    companyId?: string | null;
    personId?: string | null;
    startDate: string;
    endDate: string;
}

export const forecastLocalService = {
    async generateForecast(options: ForecastOptions): Promise<FinancialForecast> {
        const { userId, companyId, personId, startDate, endDate } = options;

        // 1. Fetch current balance
        let balanceQuery = supabase
            .from('accounts')
            .select('balance')
            .eq('user_id', userId);

        if (companyId !== undefined) balanceQuery = companyId === null ? balanceQuery.is('company_id', null) : balanceQuery.eq('company_id', companyId);
        if (personId !== undefined) balanceQuery = personId === null ? balanceQuery.is('person_id', null) : balanceQuery.eq('person_id', personId);

        const { data: accountsData } = await balanceQuery;
        const initialBalance = (accountsData || []).reduce((acc, account) => acc + Number(account.balance || 0), 0);

        // 2. Fetch past transactions for the period (to understand patterns)
        let txQuery = supabase
            .from('transactions')
            .select('amount, type, date')
            .eq('user_id', userId)
            .eq('is_transfer', false)
            .gte('date', startDate)
            .lte('date', endDate);

        if (companyId !== undefined) txQuery = companyId === null ? txQuery.is('company_id', null) : txQuery.eq('company_id', companyId);
        if (personId !== undefined) txQuery = personId === null ? txQuery.is('person_id', null) : txQuery.eq('person_id', personId);

        const { data: transactions } = await txQuery;

        // Calculate simple stats
        let totalIncome = 0;
        let totalExpense = 0;
        (transactions || []).forEach(tx => {
            if (tx.type === 'income') totalIncome += Number(tx.amount);
            if (tx.type === 'expense') totalExpense += Number(tx.amount);
        });

        // Calculate days between dates
        const start = new Date(startDate);
        const end = new Date(endDate);
        const diffTime = Math.abs(end.getTime() - start.getTime());
        let days = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) || 30;
        if (days === 0) days = 1;

        const avg_daily_income = totalIncome / days;
        const avg_daily_expense = totalExpense / days;
        const avg_monthly_income = avg_daily_income * 30;
        const avg_monthly_expense = avg_daily_expense * 30;

        // 3. Simple Mathematical Projection based on Average (Daily, Weekly, Monthly)
        // If we want to simulate the future, we start from 'initialBalance' 
        // and project forward taking into account "Bills to Pay/Receive".

        // Let's assume a simplified linear projection for UI demonstration if no specific bills are used.
        // Or we project simply based on avg daily net.
        const dailyNet = avg_daily_income - avg_daily_expense;
        
        const forecast_daily: Record<string, number> = {};
        let currentSimulatedBalance = initialBalance;
        let riskNegative = false;
        let riskDate: string | null = null;
        
        // Project next 30 days
        const todayForProj = new Date();
        for(let i=1; i<=30; i++) {
            const projDate = new Date(todayForProj);
            projDate.setDate(projDate.getDate() + i);
            currentSimulatedBalance += dailyNet;
            
            if (currentSimulatedBalance < 0 && !riskNegative) {
                riskNegative = true;
                riskDate = projDate.toISOString().split('T')[0];
            }
            forecast_daily[projDate.toISOString().split('T')[0]] = currentSimulatedBalance;
        }

        // Project next 12 weeks
        const forecast_weekly: Record<string, number> = {};
        for(let i=1; i<=12; i++) {
            forecast_weekly[`semana_${i}`] = initialBalance + (dailyNet * 7 * i);
        }

        // Project next 6 months
        const forecast_monthly: Record<string, number> = {};
        for(let i=1; i<=6; i++) {
            const mDate = new Date();
            mDate.setMonth(mDate.getMonth() + i);
            forecast_monthly[mDate.toISOString().substring(0, 7)] = initialBalance + (avg_monthly_income - avg_monthly_expense) * i;
        }

        const promptText = `Escreva como um analista financeiro.
Você tem os seguintes dados:
Saldo: R$ ${initialBalance.toFixed(2)}
Receita Média por Mês: R$ ${avg_monthly_income.toFixed(2)}
Despesa Média por Mês: R$ ${avg_monthly_expense.toFixed(2)}
Fluxo Estimado por Mês: R$ ${(avg_monthly_income - avg_monthly_expense).toFixed(2)}

Gere 3 frases de dicas curtas avaliando APENAS estes números exatos (fale se está bom, ruim, e sugira algo).
Responda APENAS em JSON no formato exato:
{
  "insights": [
    "Sua primeira frase detalhada baseada nos números",
    "Sua segunda frase com alerta ou sugestao baseada nos números",
    "Sua terceira frase com conclusão clara baseada nos números"
  ],
  "alerts": [
    {
      "tipo": "alerta_fluxo",
      "descricao": "Texto sobre risco",
      "gravidade": "media"
    }
  ]
}`;

        let aiInsights: string[] = ["Calculando tendências baseadas na média histórica..."];
        let aiAlerts: ForecastAlert[] = [];

        try {
            // Voltando para "user" role pois o qwen0.5b as vezes ignora pure system messages
            const aiResponse = await chatWithOllama([{ role: 'user', content: promptText }]);
            if (!aiResponse) {
                console.warn('Forecast executado sem IA local; usando apenas projeção matemática.');
            } else {
                console.log("Ollama Response for Forecast:", aiResponse);

            // Clean markdown
                let cleaned = aiResponse.replace(/```json/g, '').replace(/```/g, '').trim();
                const aiData = JSON.parse(cleaned);

                if (aiData.insights && Array.isArray(aiData.insights)) {
                    aiInsights = aiData.insights.slice(0, 4);
                }
                if (aiData.alerts && Array.isArray(aiData.alerts)) {
                    aiAlerts = aiData.alerts.slice(0, 3);
                }
            }
        } catch (e) {
            console.error("Erro na Análise de IA:", e);
        }

        // 5. Construct Final Object
        const forecastRecord = {
            user_id: userId,
            company_id: companyId === undefined ? null : companyId,
            person_id: personId === undefined ? null : personId,
            initial_balance: initialBalance,
            forecast_daily,
            forecast_weekly,
            forecast_monthly,
            insights: aiInsights,
            alerts: aiAlerts,
            risk_negative: riskNegative,
            risk_date: riskDate,
            spending_patterns: {
                avg_monthly_income,
                avg_monthly_expense,
                avg_daily_income,
                avg_daily_expense
            }
        };

        // 6. Save to DB
        const { data: savedForecast, error } = await supabase
            .from('financial_forecasts')
            .insert(forecastRecord)
            .select()
            .single();

        if (error) {
            console.error("Erro ao salvar forecast no DB:", error);
            throw error;
        }

        return savedForecast;
    }
};
