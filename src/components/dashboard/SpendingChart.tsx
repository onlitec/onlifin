import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';

interface MonthlyData {
    month: string;
    income: number;
    expenses: number;
}

interface SpendingChartProps {
    data: MonthlyData[];
}

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
        minimumFractionDigits: 0
    }).format(value);
};

const CustomTooltip = ({ active, payload }: any) => {
    if (active && payload && payload.length) {
        return (
            <div className="bg-card border border-border rounded-lg p-3 shadow-lg">
                <p className="text-sm text-muted-foreground mb-2">{payload[0].payload.month}</p>
                <div className="space-y-1">
                    <div className="flex items-center gap-2">
                        <div className="w-2 h-2 rounded-full bg-green-500" />
                        <span className="text-xs text-muted-foreground">Renda:</span>
                        <span className="text-sm font-semibold text-green-500">
                            {formatCurrency(payload[0].value)}
                        </span>
                    </div>
                    <div className="flex items-center gap-2">
                        <div className="w-2 h-2 rounded-full bg-red-500" />
                        <span className="text-xs text-muted-foreground">Despesa:</span>
                        <span className="text-sm font-semibold text-red-500">
                            {formatCurrency(payload[1].value)}
                        </span>
                    </div>
                </div>
            </div>
        );
    }
    return null;
};

export function SpendingChart({ data }: SpendingChartProps) {
    return (
        <Card>
            <CardHeader className="pb-4">
                <div className="flex items-center justify-between">
                    <CardTitle className="text-xl font-semibold">Visão geral dos gastos</CardTitle>
                    <div className="flex items-center gap-4">
                        <span className="flex items-center gap-2">
                            <span className="w-2.5 h-2.5 rounded-full bg-green-500" />
                            <span className="text-sm text-muted-foreground">Renda</span>
                        </span>
                        <span className="flex items-center gap-2">
                            <span className="w-2.5 h-2.5 rounded-full bg-red-500" />
                            <span className="text-sm text-muted-foreground">Despesa</span>
                        </span>
                    </div>
                </div>
            </CardHeader>
            <CardContent>
                <ResponsiveContainer width="100%" height={280}>
                    <AreaChart data={data} margin={{ top: 10, right: 10, left: 0, bottom: 0 }}>
                        <defs>
                            <linearGradient id="incomeGradient" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="5%" stopColor="#10b981" stopOpacity={0.3} />
                                <stop offset="95%" stopColor="#10b981" stopOpacity={0} />
                            </linearGradient>
                            <linearGradient id="expenseGradient" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="5%" stopColor="#ef4444" stopOpacity={0.3} />
                                <stop offset="95%" stopColor="#ef4444" stopOpacity={0} />
                            </linearGradient>
                        </defs>
                        <CartesianGrid strokeDasharray="3 3" className="stroke-muted" opacity={0.2} vertical={false} />
                        <XAxis
                            dataKey="month"
                            axisLine={false}
                            tickLine={false}
                            className="text-muted-foreground"
                            tick={{ fill: 'hsl(var(--muted-foreground))', fontSize: 12 }}
                            dy={10}
                        />
                        <YAxis
                            hide
                        />
                        <Tooltip content={<CustomTooltip />} />
                        <Area
                            type="monotone"
                            dataKey="income"
                            stroke="#10b981"
                            strokeWidth={2}
                            fill="url(#incomeGradient)"
                            dot={{ fill: '#10b981', r: 4, strokeWidth: 0 }}
                            activeDot={{ r: 6, strokeWidth: 0 }}
                        />
                        <Area
                            type="monotone"
                            dataKey="expenses"
                            stroke="#ef4444"
                            strokeWidth={2}
                            fill="url(#expenseGradient)"
                            dot={{ fill: '#ef4444', r: 4, strokeWidth: 0 }}
                            activeDot={{ r: 6, strokeWidth: 0 }}
                        />
                    </AreaChart>
                </ResponsiveContainer>
            </CardContent>
        </Card>
    );
}
