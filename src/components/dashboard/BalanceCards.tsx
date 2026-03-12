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
        <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            {cards.map((card, index) => {
                const Icon = card.icon;

                return (
                    <div
                        key={index}
                        className="bg-white border border-slate-200 rounded-2xl p-4 flex flex-col justify-between min-h-[130px] shadow-sm hover:shadow-md transition-all group relative overflow-hidden"
                    >
                        <div className="flex items-center justify-between">
                            <span className="text-[9px] font-black uppercase tracking-[0.15em] text-slate-400">
                                {card.title}
                            </span>
                            <div className="p-1 rounded-lg bg-slate-50 border border-slate-100/50 group-hover:scale-110 transition-transform">
                                <Icon className={`h-3 w-3 ${card.iconColor}`} />
                            </div>
                        </div>

                        <div className="my-2">
                            <h3 className={`text-2xl font-black tracking-tight ${card.valueColor}`}>
                                {card.isPercentage ? `${card.value.toFixed(1)}%` : formatCurrency(card.value)}
                            </h3>
                        </div>

                        <div className="flex items-center gap-1.5 mt-auto">
                            <div className={`h-1 w-1 rounded-full ${card.iconColor.replace('text-', 'bg-')} animate-pulse`} />
                            <p className="text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-none">
                                {card.subtitle}
                            </p>
                        </div>
                    </div>
                );
            })}
        </div>
    );
}
