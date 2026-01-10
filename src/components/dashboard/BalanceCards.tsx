import { TrendingUp, TrendingDown, Wallet, PiggyBank } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';

interface BalanceCardsProps {
    totalBalance: number;
    monthlyIncome: number;
    monthlyExpenses: number;
    savingsRate: number;
}

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
};

export function BalanceCards({ totalBalance, monthlyIncome, monthlyExpenses, savingsRate }: BalanceCardsProps) {
    const savings = monthlyIncome - monthlyExpenses;

    const cards = [
        {
            title: 'Saldo Total',
            value: totalBalance,
            change: '+12.5%',
            icon: Wallet,
            iconBg: 'bg-blue-500',
            iconColor: 'text-white'
        },
        {
            title: 'Receitas',
            value: monthlyIncome,
            change: '+8.2%',
            icon: TrendingUp,
            iconBg: 'bg-green-500',
            iconColor: 'text-white'
        },
        {
            title: 'Despesas',
            value: monthlyExpenses,
            change: '-4.1%',
            icon: TrendingDown,
            iconBg: 'bg-red-500',
            iconColor: 'text-white'
        },
        {
            title: 'Poupança',
            value: savings,
            change: `+${savingsRate.toFixed(1)}%`,
            icon: PiggyBank,
            iconBg: 'bg-purple-500',
            iconColor: 'text-white'
        }
    ];

    return (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {cards.map((card, index) => {
                const Icon = card.icon;
                const isNegative = card.value < 0;
                const changeIsNegative = card.change.startsWith('-');

                return (
                    <Card
                        key={index}
                        className="hover:shadow-md transition-shadow"
                    >
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between mb-4">
                                <span className="text-sm text-muted-foreground">{card.title}</span>
                                <div className={`p-2 rounded-lg ${card.iconBg}`}>
                                    <Icon className={`h-5 w-5 ${card.iconColor}`} />
                                </div>
                            </div>
                            <div className="space-y-1">
                                <h3 className={`text-2xl font-bold ${isNegative ? 'text-red-500' : 'text-foreground'
                                    }`}>
                                    {formatCurrency(card.value)}
                                </h3>
                                <p className={`text-sm flex items-center gap-1 ${changeIsNegative ? 'text-red-500' : 'text-green-500'
                                    }`}>
                                    {changeIsNegative ? (
                                        <TrendingDown className="h-3 w-3" />
                                    ) : (
                                        <TrendingUp className="h-3 w-3" />
                                    )}
                                    {card.change}
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                );
            })}
        </div>
    );
}
