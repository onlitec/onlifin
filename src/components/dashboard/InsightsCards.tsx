import { Zap, Target, TrendingUp } from 'lucide-react';

interface InsightsCardsProps {
    averageDailyExpense: number;
    projectedMonthEnd: number;
    savingsRate: number;
}

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
};

export function InsightsCards({
    averageDailyExpense,
    projectedMonthEnd,
    savingsRate
}: InsightsCardsProps) {
    const insights = [
        {
            title: 'Gasto Médio Diário',
            value: formatCurrency(averageDailyExpense),
            description: 'Média de saídas por dia',
            icon: Zap,
            color: 'text-amber-500',
            bg: 'bg-amber-50'
        },
        {
            title: 'Projeção de Fechamento',
            value: formatCurrency(projectedMonthEnd),
            description: 'Estimativa baseada no ritmo atual',
            icon: Target,
            color: 'text-blue-500',
            bg: 'bg-blue-50'
        },
        {
            title: 'Performance de Poupança',
            value: `${savingsRate.toFixed(1)}%`,
            description: 'Eficiência de retenção de capital',
            icon: TrendingUp,
            color: 'text-emerald-500',
            bg: 'bg-emerald-50'
        }
    ];

    return (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            {insights.map((insight, index) => {
                const Icon = insight.icon;
                return (
                    <div key={index} className="bg-white border border-slate-200 rounded-2xl p-4 flex items-center gap-4 shadow-sm hover:shadow-md transition-all group">
                        <div className={`w-12 h-12 rounded-xl ${insight.bg} flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform`}>
                            <Icon className={`h-6 w-6 ${insight.color}`} />
                        </div>
                        <div className="flex-1 min-w-0">
                            <p className="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-0.5">
                                {insight.title}
                            </p>
                            <h4 className="text-lg font-black text-slate-900 tracking-tight">
                                {insight.value}
                            </h4>
                            <p className="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">
                                {insight.description}
                            </p>
                        </div>
                    </div>
                );
            })}
        </div>
    );
}
