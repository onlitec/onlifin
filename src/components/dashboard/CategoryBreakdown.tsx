import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { PieChart, Pie, Cell, ResponsiveContainer } from 'recharts';

interface CategoryData {
    category: string;
    amount: number;
    icon?: string;
}

interface CategoryBreakdownProps {
    categories: CategoryData[];
}

const CATEGORY_COLORS = [
    '#3b82f6', // blue
    '#ef4444', // red  
    '#f59e0b', // yellow
    '#8b5cf6', // purple
    '#ec4899', // pink
    '#10b981', // green
];

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
        minimumFractionDigits: 0
    }).format(value);
};

export function CategoryBreakdown({ categories }: CategoryBreakdownProps) {
    const total = categories.reduce((sum, cat) => sum + cat.amount, 0);

    const topCategories = categories
        .sort((a, b) => b.amount - a.amount)
        .slice(0, 6);

    return (
        <Card>
            <CardHeader>
                <CardTitle>Despesas por Categoria</CardTitle>
            </CardHeader>
            <CardContent>
                <div className="flex items-center justify-between gap-6">
                    <div className="relative flex-shrink-0">
                        <ResponsiveContainer width={200} height={200}>
                            <PieChart>
                                <Pie
                                    data={topCategories}
                                    cx="50%"
                                    cy="50%"
                                    innerRadius={60}
                                    outerRadius={80}
                                    paddingAngle={2}
                                    dataKey="amount"
                                >
                                    {topCategories.map((_, index) => (
                                        <Cell
                                            key={`cell-${index}`}
                                            fill={CATEGORY_COLORS[index % CATEGORY_COLORS.length]}
                                        />
                                    ))}
                                </Pie>
                            </PieChart>
                        </ResponsiveContainer>
                        <div className="absolute inset-0 flex items-center justify-center">
                            <div className="text-center">
                                <div className="text-2xl font-bold text-foreground">
                                    {formatCurrency(total)}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="flex-1 space-y-2">
                        {topCategories.map((cat, index) => {
                            const percentage = total > 0 ? (cat.amount / total) * 100 : 0;

                            return (
                                <div key={cat.category} className="flex items-center justify-between group">
                                    <div className="flex items-center gap-2 flex-1 min-w-0">
                                        <div
                                            className="w-2 h-2 rounded-full flex-shrink-0"
                                            style={{ backgroundColor: CATEGORY_COLORS[index % CATEGORY_COLORS.length] }}
                                        />
                                        <span className="text-sm text-muted-foreground truncate">
                                            {cat.icon} {cat.category}
                                        </span>
                                    </div>
                                    <div className="flex items-center gap-4 flex-shrink-0">
                                        <div className="w-24 h-1.5 bg-muted rounded-full overflow-hidden">
                                            <div
                                                className="h-full rounded-full transition-all"
                                                style={{
                                                    width: `${percentage}%`,
                                                    backgroundColor: CATEGORY_COLORS[index % CATEGORY_COLORS.length]
                                                }}
                                            />
                                        </div>
                                        <div className="text-right min-w-[80px]">
                                            <div className="text-sm font-semibold text-foreground">
                                                {formatCurrency(cat.amount)}
                                            </div>
                                            <div className="text-xs text-muted-foreground">
                                                {percentage.toFixed(0)}%
                                            </div>
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
