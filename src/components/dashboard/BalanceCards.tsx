import { TrendingUp, TrendingDown, Wallet, PiggyBank, Clock } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';

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
    savingsRate,
    pendingToReceive = 0
}: BalanceCardsProps) {
    const savings = monthlyIncome - monthlyExpenses;

    const cards = [
        {
            title: 'Saldo Total',
            value: totalBalance,
            subtitle: 'Saldo atual das contas',
            icon: Wallet,
            iconBg: 'bg-blue-500',
            iconColor: 'text-white'
        },
        {
            title: 'Receitas Confirmadas',
            value: monthlyIncome,
            subtitle: 'Receitas recebidas no mês',
            icon: TrendingUp,
            iconBg: 'bg-green-500',
            iconColor: 'text-white'
        },
        {
            title: 'A Receber',
            value: pendingToReceive,
            subtitle: 'Contas pendentes',
            icon: Clock,
            iconBg: 'bg-amber-500',
            iconColor: 'text-white'
        },
        {
            title: 'Despesas',
            value: monthlyExpenses,
            subtitle: 'Gastos do mês',
            icon: TrendingDown,
            iconBg: 'bg-red-500',
            iconColor: 'text-white'
        },
        {
            title: 'Poupança',
            value: savings,
            subtitle: `Taxa: ${savingsRate.toFixed(1)}%`,
            icon: PiggyBank,
            iconBg: 'bg-purple-500',
            iconColor: 'text-white'
        }
    ];

    return (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            {cards.map((card, index) => {
                const Icon = card.icon;
                const isNegative = card.value < 0;

                return (
                    <Card
                        key={index}
                        className="hover:shadow-md transition-shadow"
                    >
                        <CardContent className="p-4">
                            <div className="flex items-center justify-between mb-3">
                                <span className="text-xs text-muted-foreground font-medium">{card.title}</span>
                                <div className={`p-1.5 rounded-lg ${card.iconBg}`}>
                                    <Icon className={`h-4 w-4 ${card.iconColor}`} />
                                </div>
                            </div>
                            <div className="space-y-0.5">
                                <h3 className={`text-xl font-bold ${isNegative ? 'text-red-500' : 'text-foreground'}`}>
                                    {formatCurrency(card.value)}
                                </h3>
                                <p className="text-xs text-muted-foreground">
                                    {card.subtitle}
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                );
            })}
        </div>
    );
}

