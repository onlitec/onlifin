import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { PieChart, Pie, Cell, ResponsiveContainer } from 'recharts';
import { Home, UtensilsCrossed, ShoppingBag, Car, Gamepad2, MoreHorizontal } from 'lucide-react';

interface CategoryData {
    category: string;
    amount: number;
    icon?: string;
}

interface CategoryBreakdownProps {
    categories: CategoryData[];
}

const CATEGORY_CONFIG: Record<string, { color: string; bgColor: string; icon: React.ElementType }> = {
    'Habitação': { color: '#3b82f6', bgColor: 'bg-blue-500', icon: Home },
    'Moradia': { color: '#3b82f6', bgColor: 'bg-blue-500', icon: Home },
    'Alimentação': { color: '#ec4899', bgColor: 'bg-pink-500', icon: UtensilsCrossed },
    'Comida e Bebida': { color: '#ec4899', bgColor: 'bg-pink-500', icon: UtensilsCrossed },
    'Compras': { color: '#8b5cf6', bgColor: 'bg-purple-500', icon: ShoppingBag },
    'Transporte': { color: '#06b6d4', bgColor: 'bg-cyan-500', icon: Car },
    'Entretenimento': { color: '#3b82f6', bgColor: 'bg-blue-600', icon: Gamepad2 },
    'Lazer': { color: '#3b82f6', bgColor: 'bg-blue-600', icon: Gamepad2 },
    'Outro': { color: '#6b7280', bgColor: 'bg-gray-500', icon: MoreHorizontal },
    'Outros': { color: '#6b7280', bgColor: 'bg-gray-500', icon: MoreHorizontal },
};

const DEFAULT_CONFIG = { color: '#6b7280', bgColor: 'bg-gray-500', icon: MoreHorizontal };

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(value);
};

export function CategoryBreakdown({ categories }: CategoryBreakdownProps) {
    const total = categories.reduce((sum, cat) => sum + cat.amount, 0);

    const topCategories = [...categories]
        .sort((a, b) => b.amount - a.amount)
        .slice(0, 6);

    const chartData = topCategories.map((cat) => ({
        name: cat.category,
        value: cat.amount,
        color: CATEGORY_CONFIG[cat.category]?.color || DEFAULT_CONFIG.color
    }));

    return (
        <Card className="h-full flex flex-col">
            <CardHeader className="pb-2">
                <CardTitle className="text-xl font-semibold">Gastos por categoria</CardTitle>
            </CardHeader>
            <CardContent className="flex-1 flex items-center">
                <div className="flex items-start gap-6">
                    {/* Donut Chart */}
                    <div className="relative flex-shrink-0" style={{ width: 140, height: 140 }}>
                        <ResponsiveContainer width="100%" height="100%">
                            <PieChart>
                                <Pie
                                    data={chartData}
                                    cx="50%"
                                    cy="50%"
                                    innerRadius={45}
                                    outerRadius={65}
                                    paddingAngle={2}
                                    dataKey="value"
                                    stroke="none"
                                >
                                    {chartData.map((entry, index) => (
                                        <Cell key={`cell-${index}`} fill={entry.color} />
                                    ))}
                                </Pie>
                            </PieChart>
                        </ResponsiveContainer>
                        {/* Total in center */}
                        <div className="absolute inset-0 flex items-center justify-center">
                            <div className="text-center">
                                <div className="text-xl font-bold text-foreground">
                                    {formatCurrency(total)}
                                </div>
                                <div className="text-xs text-muted-foreground">Total</div>
                            </div>
                        </div>
                    </div>

                    {/* Category List */}
                    <div className="flex-1 space-y-3">
                        {topCategories.map((cat) => {
                            const percentage = total > 0 ? (cat.amount / total) * 100 : 0;
                            const config = CATEGORY_CONFIG[cat.category] || DEFAULT_CONFIG;
                            const Icon = config.icon;

                            return (
                                <div key={cat.category} className="flex items-center gap-3">
                                    {/* Icon */}
                                    <div className={`w-8 h-8 rounded-lg ${config.bgColor} flex items-center justify-center flex-shrink-0`}>
                                        <Icon className="w-4 h-4 text-white" />
                                    </div>

                                    {/* Name and Progress */}
                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-center justify-between mb-1">
                                            <span className="text-sm font-medium text-foreground truncate">
                                                {cat.category}
                                            </span>
                                            <span className="text-sm font-semibold text-foreground ml-2">
                                                {formatCurrency(cat.amount)}
                                            </span>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <div className="flex-1 h-1.5 bg-muted rounded-full overflow-hidden">
                                                <div
                                                    className="h-full rounded-full transition-all"
                                                    style={{
                                                        width: `${percentage}%`,
                                                        backgroundColor: config.color
                                                    }}
                                                />
                                            </div>
                                            <span className="text-xs text-muted-foreground w-10 text-right">
                                                {percentage.toFixed(0)} %
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
