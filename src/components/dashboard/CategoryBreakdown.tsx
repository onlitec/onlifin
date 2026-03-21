import { PieChart, Pie, Cell, ResponsiveContainer, Tooltip } from 'recharts';
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
    'Entretenimento': { color: '#fbbf24', bgColor: 'bg-amber-400', icon: Gamepad2 },
    'Lazer': { color: '#fbbf24', bgColor: 'bg-amber-400', icon: Gamepad2 },
    'Outro': { color: '#94a3b8', bgColor: 'bg-slate-400', icon: MoreHorizontal },
    'Outros': { color: '#94a3b8', bgColor: 'bg-slate-400', icon: MoreHorizontal },
};

const DEFAULT_CONFIG = { color: '#94a3b8', bgColor: 'bg-slate-400', icon: MoreHorizontal };

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
        .slice(0, 5);

    const chartData = topCategories.map((cat) => ({
        name: cat.category,
        value: cat.amount,
        color: CATEGORY_CONFIG[cat.category]?.color || DEFAULT_CONFIG.color
    }));

    if (topCategories.length === 0 || total === 0) {
        return (
            <div className="bg-white border border-slate-200 rounded-2xl p-4 lg:p-6 h-full flex flex-col gap-4 shadow-sm">
                <div className="space-y-0.5">
                    <h3 className="text-sm font-black tracking-tight text-slate-900 uppercase">Gastos por Categoria</h3>
                    <p className="text-[9px] font-bold text-slate-400 uppercase tracking-widest leading-none">Distribuição Mensal</p>
                </div>
                <div className="flex-1 flex items-center justify-center">
                    <div className="text-center space-y-2 max-w-xs">
                        <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-50 border border-slate-100">
                            <MoreHorizontal className="h-8 w-8 text-slate-300" />
                        </div>
                        <p className="text-sm font-bold text-slate-900">Sem despesas categorizadas</p>
                        <p className="text-xs font-medium text-slate-400 uppercase tracking-widest">
                            As categorias aparecem aqui depois das primeiras transações de despesa.
                        </p>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="bg-white border border-slate-200 rounded-2xl p-4 lg:p-6 h-full flex flex-col gap-4 shadow-sm">
            <div className="space-y-0.5">
                <h3 className="text-sm font-black tracking-tight text-slate-900 uppercase">Gastos por Categoria</h3>
                <p className="text-[9px] font-bold text-slate-400 uppercase tracking-widest leading-none">Distribuição Mensal</p>
            </div>

            <div className="flex-1 flex flex-col justify-center">
                <div className="flex flex-col items-center gap-6">
                    <div className="relative flex-shrink-0" style={{ width: 140, height: 140 }}>
                        <ResponsiveContainer width="100%" height="100%">
                            <PieChart>
                                <Pie
                                    data={chartData}
                                    cx="50%"
                                    cy="50%"
                                    innerRadius={60}
                                    outerRadius={75}
                                    paddingAngle={5}
                                    dataKey="value"
                                    stroke="none"
                                >
                                    {chartData.map((entry, index) => (
                                        <Cell
                                            key={`cell-${index}`}
                                            fill={entry.color}
                                            className="transition-all duration-300"
                                        />
                                    ))}
                                </Pie>
                                <Tooltip
                                    contentStyle={{ backgroundColor: '#fff', border: '1px solid #f1f5f9', borderRadius: '12px', boxShadow: '0 10px 15px -3px rgba(0,0,0,0.1)' }}
                                    itemStyle={{ color: '#1e293b', fontSize: '11px', fontWeight: 'bold' }}
                                    formatter={(value: number) => formatCurrency(value)}
                                />
                            </PieChart>
                        </ResponsiveContainer>
                        <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
                            <div className="text-center">
                                <span className="text-xs font-bold text-slate-400 uppercase tracking-tighter block mb-0.5">Total</span>
                                <span className="text-base font-bold text-slate-900 tracking-tight">
                                    {formatCurrency(total)}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div className="w-full space-y-3">
                        {topCategories.map((cat) => {
                            const percentage = total > 0 ? (cat.amount / total) * 100 : 0;
                            const config = CATEGORY_CONFIG[cat.category] || DEFAULT_CONFIG;
                            const Icon = config.icon;

                            return (
                                <div key={cat.category} className="group flex items-center gap-3">
                                    <div className="w-8 h-8 rounded-lg bg-white border border-slate-200 flex items-center justify-center flex-shrink-0 transition-transform group-hover:scale-110 shadow-sm">
                                        <Icon className="w-4 h-4 text-slate-700" />
                                    </div>

                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-center justify-between mb-1.5">
                                            <span className="text-[11px] font-bold text-slate-600 uppercase tracking-tight truncate">
                                                {cat.category}
                                            </span>
                                            <span className="text-xs font-bold text-slate-900 tracking-tight">
                                                {formatCurrency(cat.amount)}
                                            </span>
                                        </div>
                                        <div className="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                            <div
                                                className="h-full rounded-full transition-all duration-1000 ease-out"
                                                style={{
                                                    width: `${percentage}%`,
                                                    backgroundColor: config.color
                                                }}
                                            />
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>
            </div>
        </div>
    );
}
