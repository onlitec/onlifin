// Serviço para carregar contexto financeiro completo para a IA
import { supabase } from '@/db/client';

export interface FinancialContextScope {
    mode?: 'PF' | 'PJ' | 'GERAL';
    companyId?: string | null;
    personId?: string | null;
}

export interface FinancialContext {
    scope: {
        mode: 'PF' | 'PJ' | 'GERAL';
        companyId: string | null;
        personId: string | null;
    };

    // Resumo Geral
    summary: {
        totalBalance: number;
        totalIncome: number;
        totalExpense: number;
        netBalance: number;
        savingsRate: number;
    };

    // Contas Bancárias
    accounts: {
        total: number;
        list: Array<{
            name: string;
            bank: string | null;
            balance: number;
            currency: string;
        }>;
    };

    // Cartões de Crédito
    cards: {
        total: number;
        totalLimit: number;
        list: Array<{
            name: string;
            limit: number;
            closingDay: number;
            dueDay: number;
        }>;
    };

    // Transações do Mês
    transactions: {
        count: number;
        totalIncome: number;
        totalExpense: number;
        byCategory: Array<{
            category: string;
            type: 'income' | 'expense';
            total: number;
            count: number;
        }>;
        recent: Array<{
            date: string;
            description: string;
            amount: number;
            type: 'income' | 'expense';
            category: string | null;
        }>;
    };

    // Contas a Pagar
    billsToPay: {
        total: number;
        overdue: number;
        upcoming: number;
        totalAmount: number;
        overdueAmount: number;
        list: Array<{
            description: string;
            amount: number;
            dueDate: string;
            status: string;
            isOverdue: boolean;
        }>;
    };

    // Contas a Receber
    billsToReceive: {
        total: number;
        overdue: number;
        upcoming: number;
        totalAmount: number;
        list: Array<{
            description: string;
            amount: number;
            dueDate: string;
            status: string;
        }>;
    };

    // Agendamentos Recorrentes
    schedules: {
        total: number;
        list: Array<{
            description: string;
            amount: number;
            frequency: string;
            type: 'income' | 'expense';
        }>;
    };

    // Histórico de importação / extratos
    imports: {
        totalHistory: number;
        totalImportedTransactions: number;
        pendingJobs: number;
        failedJobs: number;
        recent: Array<{
            fileName: string;
            format: string;
            status: string;
            importedCount: number;
            createdAt: string;
        }>;
    };

    // Gestão de Dívidas
    debts: {
        total: number;
        overdue: number;
        totalBalance: number;
        totalPaid: number;
        recent: Array<{
            description: string;
            creditor: string;
            dueDate: string;
            currentBalance: number;
            status: string;
        }>;
    };

    // Previsão Financeira
    forecast: {
        latestCalculationDate: string | null;
        riskNegative: boolean;
        riskDate: string | null;
        projectedBalance30d: number | null;
        insights: string[];
        alerts: string[];
    };

    // Categorias
    categories: {
        income: string[];
        expense: string[];
    };
}

function applyFinanceScope(query: any, scope?: FinancialContextScope) {
    if (scope?.companyId !== undefined) {
        query = scope.companyId === null
            ? query.is('company_id', null)
            : query.eq('company_id', scope.companyId);
    }

    if (scope?.personId !== undefined) {
        query = scope.personId === null
            ? query.is('person_id', null)
            : query.eq('person_id', scope.personId);
    }

    return query;
}

/**
 * Carrega todos os dados financeiros do usuário para contexto da IA
 */
export async function loadFinancialContext(userId: string, scope?: FinancialContextScope): Promise<FinancialContext> {
    const today = new Date();
    const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1).toISOString();
    const endOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0).toISOString();
    const resolvedScope = {
        mode: scope?.mode || 'GERAL',
        companyId: scope?.companyId ?? null,
        personId: scope?.personId ?? null
    };

    // Carregar todos os dados em paralelo
    const [
        accountsResult,
        cardsResult,
        transactionsResult,
        billsToPayResult,
        billsToReceiveResult,
        schedulesResult,
        categoriesResult,
        importHistoryResult,
        importJobsResult,
        debtsResult,
        forecastsResult
    ] = await Promise.all([
        applyFinanceScope(
            supabase.from('accounts').select('*').eq('user_id', userId),
            scope
        ),
        applyFinanceScope(
            supabase.from('cards').select('*').eq('user_id', userId),
            scope
        ),
        applyFinanceScope(
            supabase.from('transactions').select('*, category:categories(name, type)').eq('user_id', userId),
            scope
        ),
        applyFinanceScope(
            supabase.from('bills_to_pay').select('*').eq('user_id', userId),
            scope
        ),
        applyFinanceScope(
            supabase.from('bills_to_receive').select('*').eq('user_id', userId),
            scope
        ),
        applyFinanceScope(
            supabase.from('recurring_schedules').select('*').eq('user_id', userId),
            scope
        ),
        supabase.from('categories').select('*'),
        supabase.from('import_history').select('*').eq('user_id', userId).order('created_at', { ascending: false }).limit(10),
        applyFinanceScope(
            supabase.from('background_import_jobs').select('*').eq('user_id', userId).order('created_at', { ascending: false }).limit(10),
            scope
        ),
        applyFinanceScope(
            supabase.from('debts').select('*').eq('user_id', userId).order('due_date', { ascending: true }),
            scope
        ),
        applyFinanceScope(
            supabase.from('financial_forecasts').select('*').eq('user_id', userId).order('calculation_date', { ascending: false }).limit(1),
            scope
        )
    ]);

    const accounts = accountsResult.data || [];
    const cards = cardsResult.data || [];
    const transactions = transactionsResult.data || [];
    const billsToPay = billsToPayResult.data || [];
    const billsToReceive = billsToReceiveResult.data || [];
    const schedules = schedulesResult.data || [];
    const categories = categoriesResult.data || [];
    const importHistory = importHistoryResult.data || [];
    const importJobs = importJobsResult.data || [];
    const debts = debtsResult.data || [];
    const forecasts = forecastsResult.data || [];
    const latestForecast = forecasts[0] || null;

    // Calcular resumo
    const totalBalance = accounts.reduce((sum, acc) => sum + (acc.balance || 0), 0);

    // Transações do mês atual
    const monthTransactions = transactions.filter(t => {
        const tDate = new Date(t.date);
        return tDate >= new Date(startOfMonth) && tDate <= new Date(endOfMonth);
    });

    const totalIncome = monthTransactions
        .filter(t => t.type === 'income')
        .reduce((sum, t) => sum + (t.amount || 0), 0);

    const totalExpense = monthTransactions
        .filter(t => t.type === 'expense')
        .reduce((sum, t) => sum + Math.abs(t.amount || 0), 0);

    // Agrupar por categoria
    const categoryTotals: Record<string, { type: string; total: number; count: number }> = {};
    monthTransactions.forEach(t => {
        const catName = t.category?.name || 'Sem categoria';
        if (!categoryTotals[catName]) {
            categoryTotals[catName] = { type: t.type, total: 0, count: 0 };
        }
        categoryTotals[catName].total += Math.abs(t.amount || 0);
        categoryTotals[catName].count++;
    });

    // Contas a pagar
    const todayStr = today.toISOString().split('T')[0];
    const overdueBills = billsToPay.filter(b =>
        b.status !== 'paid' && b.due_date < todayStr
    );
    const upcomingBills = billsToPay.filter(b =>
        b.status !== 'paid' && b.due_date >= todayStr
    );

    // Contas a receber
    const overdueReceivables = billsToReceive.filter(b =>
        b.status !== 'received' && b.due_date < todayStr
    );
    const upcomingReceivables = billsToReceive.filter(b =>
        b.status !== 'received' && b.due_date >= todayStr
    );
    const overdueDebts = debts.filter(d => d.status === 'VENCIDO');
    const totalDebtBalance = debts.reduce((sum, debt) => sum + Number(debt.current_balance || 0), 0);
    const totalDebtPaid = debts.reduce((sum, debt) => sum + Number(debt.total_paid || 0), 0);
    const totalImportedTransactions = importHistory.reduce((sum, item) => sum + Number(item.imported_count || 0), 0);
    const pendingImportJobs = importJobs.filter(job => job.status === 'pending' || job.status === 'processing').length;
    const failedImportJobs = importJobs.filter(job => job.status === 'failed').length;
    const latestForecastDaily = latestForecast?.forecast_daily && typeof latestForecast.forecast_daily === 'object'
        ? Object.values(latestForecast.forecast_daily as Record<string, number>)
        : [];
    const projectedBalance30d = latestForecastDaily.length > 0
        ? Number(latestForecastDaily[latestForecastDaily.length - 1] || 0)
        : null;

    return {
        scope: resolvedScope,
        summary: {
            totalBalance,
            totalIncome,
            totalExpense,
            netBalance: totalIncome - totalExpense,
            savingsRate: totalIncome > 0 ? ((totalIncome - totalExpense) / totalIncome) * 100 : 0
        },

        accounts: {
            total: accounts.length,
            list: accounts.map(a => ({
                name: a.name,
                bank: a.bank,
                balance: a.balance || 0,
                currency: a.currency || 'BRL'
            }))
        },

        cards: {
            total: cards.length,
            totalLimit: cards.reduce((sum, c) => sum + (c.card_limit || 0), 0),
            list: cards.map(c => ({
                name: c.name,
                limit: c.card_limit || 0,
                closingDay: c.closing_day || 1,
                dueDay: c.due_day || 10
            }))
        },

        transactions: {
            count: monthTransactions.length,
            totalIncome,
            totalExpense,
            byCategory: Object.entries(categoryTotals).map(([category, data]) => ({
                category,
                type: data.type as 'income' | 'expense',
                total: data.total,
                count: data.count
            })),
            recent: transactions
                .sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime())
                .slice(0, 10)
                .map(t => ({
                    date: t.date,
                    description: t.description,
                    amount: t.amount,
                    type: t.type,
                    category: t.category?.name || null
                }))
        },

        billsToPay: {
            total: billsToPay.length,
            overdue: overdueBills.length,
            upcoming: upcomingBills.length,
            totalAmount: billsToPay.reduce((sum, b) => sum + (b.amount || 0), 0),
            overdueAmount: overdueBills.reduce((sum, b) => sum + (b.amount || 0), 0),
            list: billsToPay
                .filter(b => b.status !== 'paid')
                .slice(0, 10)
                .map(b => ({
                    description: b.description,
                    amount: b.amount,
                    dueDate: b.due_date,
                    status: b.status,
                    isOverdue: b.due_date < todayStr
                }))
        },

        billsToReceive: {
            total: billsToReceive.length,
            overdue: overdueReceivables.length,
            upcoming: upcomingReceivables.length,
            totalAmount: billsToReceive.reduce((sum, b) => sum + (b.amount || 0), 0),
            list: billsToReceive
                .filter(b => b.status !== 'received')
                .slice(0, 10)
                .map(b => ({
                    description: b.description,
                    amount: b.amount,
                    dueDate: b.due_date,
                    status: b.status
                }))
        },

        schedules: {
            total: schedules.length,
            list: schedules.map(s => ({
                description: s.description,
                amount: s.amount,
                frequency: s.frequency,
                type: s.type
            }))
        },

        imports: {
            totalHistory: importHistory.length,
            totalImportedTransactions,
            pendingJobs: pendingImportJobs,
            failedJobs: failedImportJobs,
            recent: importHistory.slice(0, 5).map(item => ({
                fileName: item.filename,
                format: item.format,
                status: item.status,
                importedCount: Number(item.imported_count || 0),
                createdAt: item.created_at
            }))
        },

        debts: {
            total: debts.length,
            overdue: overdueDebts.length,
            totalBalance: totalDebtBalance,
            totalPaid: totalDebtPaid,
            recent: debts.slice(0, 5).map(debt => ({
                description: debt.description,
                creditor: debt.creditor,
                dueDate: debt.due_date,
                currentBalance: Number(debt.current_balance || 0),
                status: debt.status
            }))
        },

        forecast: {
            latestCalculationDate: latestForecast?.calculation_date || null,
            riskNegative: Boolean(latestForecast?.risk_negative),
            riskDate: latestForecast?.risk_date || null,
            projectedBalance30d,
            insights: Array.isArray(latestForecast?.insights) ? latestForecast.insights.slice(0, 3) : [],
            alerts: Array.isArray(latestForecast?.alerts)
                ? latestForecast.alerts.slice(0, 3).map((alert: any) => alert?.descricao || alert?.description || String(alert))
                : []
        },

        categories: {
            income: categories.filter(c => c.type === 'income').map(c => c.name),
            expense: categories.filter(c => c.type === 'expense').map(c => c.name)
        }
    };
}

/**
 * Formata o contexto financeiro em texto para o prompt da IA
 */
export function formatFinancialContextForPrompt(ctx: FinancialContext): string {
    const formatCurrency = (value: number) =>
        new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);

    const topAccounts = ctx.accounts.list.slice(0, 3);
    const topCards = ctx.cards.list.slice(0, 3);
    const topCategories = ctx.transactions.byCategory
        .sort((a, b) => b.total - a.total)
        .slice(0, 5);
    const recentTransactions = ctx.transactions.recent.slice(0, 5);
    const topBillsToPay = ctx.billsToPay.list.slice(0, 5);
    const topBillsToReceive = ctx.billsToReceive.list.slice(0, 5);
    const topSchedules = ctx.schedules.list.slice(0, 5);
    const incomeCategories = ctx.categories.income.slice(0, 10);
    const expenseCategories = ctx.categories.expense.slice(0, 10);

    let text = `
ESCOPO ATUAL:
• Modo: ${ctx.scope.mode}
• Empresa: ${ctx.scope.companyId || 'Sem empresa'}
• Pessoa: ${ctx.scope.personId || 'Todas / não aplicável'}

RESUMO FINANCEIRO:
• Saldo Total em Contas: ${formatCurrency(ctx.summary.totalBalance)}
• Receitas do Mês: ${formatCurrency(ctx.summary.totalIncome)}
• Despesas do Mês: ${formatCurrency(ctx.summary.totalExpense)}
• Saldo Líquido: ${formatCurrency(ctx.summary.netBalance)}
• Taxa de Poupança: ${ctx.summary.savingsRate.toFixed(1)}%

CONTAS BANCÁRIAS (${ctx.accounts.total}):
${topAccounts.map(a => `• ${a.name}${a.bank ? ` (${a.bank})` : ''}: ${formatCurrency(a.balance)}`).join('\n') || '• Nenhuma conta cadastrada'}

CRÉDITO (${ctx.cards.total}):
${topCards.map(c => `• ${c.name}: Limite ${formatCurrency(c.limit)} (Fecha ${c.closingDay}, vence ${c.dueDay})`).join('\n') || '• Nenhum cartão cadastrado'}
• Limite Total: ${formatCurrency(ctx.cards.totalLimit)}

TRANSAÇÕES DO MÊS (${ctx.transactions.count}):
${topCategories.map(c =>
        `• ${c.category}: ${c.count}x - ${formatCurrency(c.total)} (${c.type === 'income' ? 'Receita' : 'Despesa'})`
    ).join('\n') || '• Nenhuma transação este mês'}

ÚLTIMAS TRANSAÇÕES:
${recentTransactions.map(t =>
        `• ${t.date}: ${t.description} - ${t.type === 'income' ? '+' : '-'}${formatCurrency(Math.abs(t.amount))}${t.category ? ` [${t.category}]` : ''}`
    ).join('\n') || '• Nenhuma transação recente'}

CONTAS A PAGAR:
• Total: ${ctx.billsToPay.total} contas
• Em Aberto: ${ctx.billsToPay.upcoming} contas - ${formatCurrency(ctx.billsToPay.totalAmount - ctx.billsToPay.overdueAmount)}
• ATRASADAS: ${ctx.billsToPay.overdue} contas - ${formatCurrency(ctx.billsToPay.overdueAmount)}
${topBillsToPay.length > 0 ? topBillsToPay.map(b =>
        `• ${b.isOverdue ? '⚠️ ATRASADA' : '📅'} ${b.dueDate}: ${b.description} - ${formatCurrency(b.amount)}`
    ).join('\n') : '• Nenhuma conta a pagar pendente'}

CONTAS A RECEBER:
• Total: ${ctx.billsToReceive.total} contas
• A Receber: ${formatCurrency(ctx.billsToReceive.totalAmount)}
• Atrasadas: ${ctx.billsToReceive.overdue} contas
${topBillsToReceive.length > 0 ? topBillsToReceive.map(b =>
        `• ${b.dueDate}: ${b.description} - ${formatCurrency(b.amount)}`
    ).join('\n') : '• Nenhuma conta a receber pendente'}

AGENDAMENTOS RECORRENTES (${ctx.schedules.total}):
${topSchedules.map(s =>
        `• ${s.description}: ${formatCurrency(s.amount)} - ${s.frequency} (${s.type === 'income' ? 'Receita' : 'Despesa'})`
    ).join('\n') || '• Nenhum agendamento recorrente'}

IMPORTAÇÕES / EXTRATOS:
• Histórico registrado: ${ctx.imports.totalHistory}
• Transações importadas: ${ctx.imports.totalImportedTransactions}
• Jobs pendentes: ${ctx.imports.pendingJobs}
• Jobs com falha: ${ctx.imports.failedJobs}
${ctx.imports.recent.map(item =>
        `• ${item.createdAt}: ${item.fileName} (${item.format}) - ${item.status} - ${item.importedCount} transações`
    ).join('\n') || '• Nenhuma importação recente'}

DÍVIDAS:
• Total de dívidas: ${ctx.debts.total}
• Dívidas vencidas: ${ctx.debts.overdue}
• Saldo devedor total: ${formatCurrency(ctx.debts.totalBalance)}
• Total já pago: ${formatCurrency(ctx.debts.totalPaid)}
${ctx.debts.recent.map(item =>
        `• ${item.dueDate}: ${item.description} / ${item.creditor} - ${formatCurrency(item.currentBalance)} [${item.status}]`
    ).join('\n') || '• Nenhuma dívida registrada'}

PREVISÃO FINANCEIRA:
• Último cálculo: ${ctx.forecast.latestCalculationDate || 'Sem previsão salva'}
• Risco de saldo negativo: ${ctx.forecast.riskNegative ? 'Sim' : 'Não'}
• Data de risco: ${ctx.forecast.riskDate || 'Sem risco identificado'}
• Saldo projetado em 30 dias: ${ctx.forecast.projectedBalance30d !== null ? formatCurrency(ctx.forecast.projectedBalance30d) : 'Indisponível'}
• Insights: ${ctx.forecast.insights.join(' | ') || 'Nenhum insight disponível'}
• Alertas: ${ctx.forecast.alerts.join(' | ') || 'Nenhum alerta disponível'}

CATÉGORIAS:
• Receitas: ${incomeCategories.join(', ') || 'Nenhuma'}
• Despesas: ${expenseCategories.join(', ') || 'Nenhuma'}
`;

    return text.trim();
}

export function buildLocalFinancialResponse(message: string, ctx: FinancialContext | null): string | null {
    if (!ctx) {
        return null;
    }

    const question = message.toLowerCase();
    const formatCurrency = (value: number) =>
        new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);

    if (question.includes('saldo')) {
        return `💰 Seu saldo total em contas hoje é ${formatCurrency(ctx.summary.totalBalance)}. No mês atual, seu saldo líquido está em ${formatCurrency(ctx.summary.netBalance)}.`;
    }

    if (question.includes('receita') || question.includes('ganhei') || question.includes('entrad')) {
        return `📈 Suas receitas do mês atual somam ${formatCurrency(ctx.summary.totalIncome)}.`;
    }

    if (question.includes('transa')) {
        const latestTransaction = ctx.transactions.recent[0];
        if (latestTransaction) {
            return `🧾 Você tem ${ctx.transactions.count} transação(ões) no mês atual. A mais recente é "${latestTransaction.description}" em ${latestTransaction.date}, no valor de ${formatCurrency(Math.abs(latestTransaction.amount))}.`;
        }

        return `🧾 Você não tem transações recentes no escopo atual ${ctx.scope.mode}.`;
    }

    if (question.includes('despesa') || question.includes('gasto') || question.includes('saíd')) {
        const topCategory = ctx.transactions.byCategory
            .filter(item => item.type === 'expense')
            .sort((a, b) => b.total - a.total)[0];

        if (topCategory) {
            return `📊 Suas despesas do mês atual somam ${formatCurrency(ctx.summary.totalExpense)}. A categoria com maior peso é ${topCategory.category}, com ${formatCurrency(topCategory.total)}.`;
        }

        return `📊 Suas despesas do mês atual somam ${formatCurrency(ctx.summary.totalExpense)}.`;
    }

    if (question.includes('poup') || question.includes('econom')) {
        return `🏦 Sua taxa de poupança no mês atual está em ${ctx.summary.savingsRate.toFixed(1)}%, com saldo líquido de ${formatCurrency(ctx.summary.netBalance)}.`;
    }

    if (question.includes('cart') || question.includes('limite')) {
        return `💳 Você tem ${ctx.cards.total} cartão(ões) cadastrado(s), com limite total de ${formatCurrency(ctx.cards.totalLimit)}.`;
    }

    if (question.includes('divid') || question.includes('dívid') || question.includes('credor') || question.includes('renegoci')) {
        return `📉 Você tem ${ctx.debts.total} dívida(s) no escopo atual, com saldo devedor total de ${formatCurrency(ctx.debts.totalBalance)}. ${ctx.debts.overdue > 0 ? `Há ${ctx.debts.overdue} vencida(s).` : 'Não há dívidas vencidas no momento.'}`;
    }

    if (question.includes('conta a pagar') || question.includes('pagar') || question.includes('venc')) {
        return `📤 Você tem ${ctx.billsToPay.total} conta(s) a pagar, sendo ${ctx.billsToPay.overdue} atrasada(s). O total em aberto é ${formatCurrency(ctx.billsToPay.totalAmount)}.`;
    }

    if (question.includes('conta a receber') || question.includes('receber')) {
        return `📥 Você tem ${ctx.billsToReceive.total} conta(s) a receber, totalizando ${formatCurrency(ctx.billsToReceive.totalAmount)}.`;
    }

    if (question.includes('extrato') || question.includes('importa') || question.includes('ofx') || question.includes('csv')) {
        return `📂 Você tem ${ctx.imports.totalHistory} importação(ões) registradas, totalizando ${ctx.imports.totalImportedTransactions} transações importadas. ${ctx.imports.pendingJobs > 0 ? `Existem ${ctx.imports.pendingJobs} job(s) em andamento.` : 'Não há importações pendentes agora.'}`;
    }

    if (question.includes('previs') || question.includes('forecast') || question.includes('proje')) {
        if (!ctx.forecast.latestCalculationDate) {
            return '🔮 Ainda não há previsão financeira salva no escopo atual. Gere uma nova previsão na tela de Previsão Financeira.';
        }

        return `🔮 A última previsão foi calculada em ${ctx.forecast.latestCalculationDate}. ${ctx.forecast.riskNegative ? `Existe risco de saldo negativo em ${ctx.forecast.riskDate}.` : 'Não há risco de saldo negativo identificado.'} ${ctx.forecast.projectedBalance30d !== null ? `O saldo projetado em 30 dias é ${formatCurrency(ctx.forecast.projectedBalance30d)}.` : ''}`;
    }

    if (
        question.includes('melhorar minhas finanças') ||
        question.includes('melhorar minha vida financeira') ||
        question.includes('dica') ||
        question.includes('conselho') ||
        question.includes('como posso melhorar')
    ) {
        const topExpense = ctx.transactions.byCategory
            .filter(item => item.type === 'expense')
            .sort((a, b) => b.total - a.total)[0];
        const guidance = [];

        guidance.push(`Seu saldo líquido do mês está em ${formatCurrency(ctx.summary.netBalance)} e a taxa de poupança em ${ctx.summary.savingsRate.toFixed(1)}%.`);

        if (topExpense) {
            guidance.push(`Sua maior concentração de gasto está em ${topExpense.category}, com ${formatCurrency(topExpense.total)}.`);
        }

        if (ctx.debts.totalBalance > 0) {
            guidance.push(`Você tem ${formatCurrency(ctx.debts.totalBalance)} em dívidas registradas, então priorizar redução desse saldo tende a melhorar seu fluxo.`);
        }

        if (ctx.forecast.insights[0]) {
            guidance.push(`Último insight da previsão: ${ctx.forecast.insights[0]}`);
        }

        return `💡 ${guidance.join(' ')}`;
    }

    return null;
}
