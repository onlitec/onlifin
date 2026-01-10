// Serviço para carregar contexto financeiro completo para a IA
import { supabase } from '@/db/client';

export interface FinancialContext {
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

    // Categorias
    categories: {
        income: string[];
        expense: string[];
    };
}

/**
 * Carrega todos os dados financeiros do usuário para contexto da IA
 */
export async function loadFinancialContext(userId: string): Promise<FinancialContext> {
    const today = new Date();
    const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1).toISOString();
    const endOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0).toISOString();

    // Carregar todos os dados em paralelo
    const [
        accountsResult,
        cardsResult,
        transactionsResult,
        billsToPayResult,
        billsToReceiveResult,
        schedulesResult,
        categoriesResult
    ] = await Promise.all([
        supabase.from('accounts').select('*').eq('user_id', userId),
        supabase.from('cards').select('*').eq('user_id', userId),
        supabase.from('transactions').select('*, category:categories(name, type)').eq('user_id', userId),
        supabase.from('bills_to_pay').select('*').eq('user_id', userId),
        supabase.from('bills_to_receive').select('*').eq('user_id', userId),
        supabase.from('recurring_schedules').select('*').eq('user_id', userId),
        supabase.from('categories').select('*')
    ]);

    const accounts = accountsResult.data || [];
    const cards = cardsResult.data || [];
    const transactions = transactionsResult.data || [];
    const billsToPay = billsToPayResult.data || [];
    const billsToReceive = billsToReceiveResult.data || [];
    const schedules = schedulesResult.data || [];
    const categories = categoriesResult.data || [];

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

    return {
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

    let text = `
═══════════════════════════════════════════════════════════
                    DADOS FINANCEIROS DO USUÁRIO
═══════════════════════════════════════════════════════════

📊 RESUMO FINANCEIRO DO MÊS ATUAL:
• Saldo Total em Contas: ${formatCurrency(ctx.summary.totalBalance)}
• Receitas do Mês: ${formatCurrency(ctx.summary.totalIncome)}
• Despesas do Mês: ${formatCurrency(ctx.summary.totalExpense)}
• Saldo Líquido: ${formatCurrency(ctx.summary.netBalance)}
• Taxa de Poupança: ${ctx.summary.savingsRate.toFixed(1)}%

🏦 CONTAS BANCÁRIAS (${ctx.accounts.total}):
${ctx.accounts.list.map(a => `• ${a.name}${a.bank ? ` (${a.bank})` : ''}: ${formatCurrency(a.balance)}`).join('\n') || '• Nenhuma conta cadastrada'}

💳 CARTÕES DE CRÉDITO (${ctx.cards.total}):
${ctx.cards.list.map(c => `• ${c.name}: Limite ${formatCurrency(c.limit)} (Fecha dia ${c.closingDay}, vence dia ${c.dueDay})`).join('\n') || '• Nenhum cartão cadastrado'}
• Limite Total: ${formatCurrency(ctx.cards.totalLimit)}

📋 TRANSAÇÕES DO MÊS (${ctx.transactions.count}):
${ctx.transactions.byCategory.map(c =>
        `• ${c.category}: ${c.count}x - ${formatCurrency(c.total)} (${c.type === 'income' ? 'Receita' : 'Despesa'})`
    ).join('\n') || '• Nenhuma transação este mês'}

📍 ÚLTIMAS TRANSAÇÕES:
${ctx.transactions.recent.map(t =>
        `• ${t.date}: ${t.description} - ${t.type === 'income' ? '+' : '-'}${formatCurrency(Math.abs(t.amount))}${t.category ? ` [${t.category}]` : ''}`
    ).join('\n') || '• Nenhuma transação recente'}

📤 CONTAS A PAGAR:
• Total: ${ctx.billsToPay.total} contas
• Em Aberto: ${ctx.billsToPay.upcoming} contas - ${formatCurrency(ctx.billsToPay.totalAmount - ctx.billsToPay.overdueAmount)}
• ATRASADAS: ${ctx.billsToPay.overdue} contas - ${formatCurrency(ctx.billsToPay.overdueAmount)}
${ctx.billsToPay.list.length > 0 ? ctx.billsToPay.list.map(b =>
        `• ${b.isOverdue ? '⚠️ ATRASADA' : '📅'} ${b.dueDate}: ${b.description} - ${formatCurrency(b.amount)}`
    ).join('\n') : '• Nenhuma conta a pagar pendente'}

📥 CONTAS A RECEBER:
• Total: ${ctx.billsToReceive.total} contas
• A Receber: ${formatCurrency(ctx.billsToReceive.totalAmount)}
• Atrasadas: ${ctx.billsToReceive.overdue} contas
${ctx.billsToReceive.list.length > 0 ? ctx.billsToReceive.list.map(b =>
        `• ${b.dueDate}: ${b.description} - ${formatCurrency(b.amount)}`
    ).join('\n') : '• Nenhuma conta a receber pendente'}

🔄 AGENDAMENTOS RECORRENTES (${ctx.schedules.total}):
${ctx.schedules.list.map(s =>
        `• ${s.description}: ${formatCurrency(s.amount)} - ${s.frequency} (${s.type === 'income' ? 'Receita' : 'Despesa'})`
    ).join('\n') || '• Nenhum agendamento recorrente'}

📂 CATEGORIAS DISPONÍVEIS:
• Receitas: ${ctx.categories.income.join(', ') || 'Nenhuma'}
• Despesas: ${ctx.categories.expense.join(', ') || 'Nenhuma'}

═══════════════════════════════════════════════════════════
`;

    return text;
}
