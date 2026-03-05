import { TrendingUp, TrendingDown, Wallet, PiggyBank } from 'lucide-react';

interface BalanceCardsProps {
    totalBalance: number;
    monthlyIncome: number;
    monthlyExpenses: number;
    savingsRate: number;
    pendingToReceive?: number;
}

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
};

export function BalanceCards({
    totalBalance,
    monthlyIncome,
    monthlyExpenses,
    savingsRate
}: BalanceCardsProps) {
    const cards = [
        {
            title: 'Saldo Total',
            value: totalBalance,
            subtitle: 'Todas as contas',
            icon: Wallet,
            iconColor: 'text-slate-400',
            valueColor: 'text-slate-900',
            labelColor: 'text-slate-500'
        },
        {
            title: 'Receitas do Mês',
            value: monthlyIncome,
            subtitle: 'Entradas confirmadas',
            icon: TrendingUp,
            iconColor: 'text-emerald-500',
            valueColor: 'text-emerald-600',
            labelColor: 'text-emerald-500/80'
        },
        {
            title: 'Despesas do Mês',
            value: monthlyExpenses,
            subtitle: 'Saídas confirmadas',
            icon: TrendingDown,
            iconColor: 'text-red-500',
            valueColor: 'text-red-600',
            labelColor: 'text-red-500/80'
        },
        {
            title: 'Taxa de Poupança',
            value: savingsRate,
            subtitle: 'Do total de receitas',
            isPercentage: true,
            icon: PiggyBank,
            iconColor: 'text-slate-400',
            valueColor: 'text-slate-900',
            labelColor: 'text-slate-400'
        }
    ];

    return (
        <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
            {cards.map((card, index) => {
                const Icon = card.icon;

                return (
                    <div
                        key={index}
                        className="premium-card p-6 flex flex-col justify-between min-h-[140px] shadow-sm group"
                    >
                        <div className="flex items-start justify-between mb-4">
                            <span className="text-sm font-semibold text-slate-500">
                                {card.title}
                            </span>
                            <Icon className={`h-4 w-4 ${card.iconColor}`} />
                        </div>

                        <div className="space-y-1">
                            <h3 className={`text-2xl font-bold tracking-tight ${card.valueColor}`}>
                                {card.isPercentage ? `${card.value.toFixed(1)}%` : formatCurrency(card.value)}
                            </h3>
                            <p className="text-xs font-medium text-slate-400">
                                {card.subtitle}
                            </p>
                        </div>
                    </div>
                );
            })}
        </div>
    );
}
