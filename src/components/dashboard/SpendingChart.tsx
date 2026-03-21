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
            <div className="bg-white shadow-xl border border-slate-100 rounded-2xl p-4 min-w-[140px] space-y-3">
                <p className="text-xs font-bold text-slate-400 uppercase tracking-wider border-b border-slate-50 pb-2">
                    {payload[0].payload.month}
                </p>
                <div className="space-y-2">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex items-center gap-2">
                            <div className="w-1.5 h-1.5 rounded-full bg-emerald-500" />
                            <span className="text-xs font-semibold text-slate-500">Receitas</span>
                        </div>
                        <span className="text-xs font-bold text-emerald-600">
                            {formatCurrency(payload[0].value)}
                        </span>
                    </div>
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex items-center gap-2">
                            <div className="w-1.5 h-1.5 rounded-full bg-red-500" />
                            <span className="text-xs font-semibold text-slate-500">Despesas</span>
                        </div>
                        <span className="text-xs font-bold text-red-600">
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
    const hasMeaningfulData = data.some((item) => item.income > 0 || item.expenses > 0);

    if (!hasMeaningfulData) {
        return (
            <div className="bg-white border border-slate-200 rounded-2xl p-4 lg:p-6 h-full flex flex-col gap-4 shadow-sm">
                <div className="flex items-center justify-between">
                    <div className="space-y-0.5">
                        <h3 className="text-sm font-black tracking-tight text-slate-900 uppercase">Evolução Mensal</h3>
                        <p className="text-[9px] font-bold text-slate-400 uppercase tracking-widest leading-none">Performance Fiscal</p>
                    </div>
                </div>
                <div className="flex-1 flex items-center justify-center min-h-[220px]">
                    <div className="text-center space-y-2 max-w-xs">
                        <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-50 border border-slate-100">
                            <svg className="h-8 w-8 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                <path d="M3 3v18h18" />
                                <path d="m7 14 4-4 3 3 5-7" />
                            </svg>
                        </div>
                        <p className="text-sm font-bold text-slate-900">Sem histórico suficiente</p>
                        <p className="text-xs font-medium text-slate-400 uppercase tracking-widest">
                            O gráfico será preenchido após as primeiras receitas e despesas.
                        </p>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="bg-white border border-slate-200 rounded-2xl p-4 lg:p-6 h-full flex flex-col gap-4 shadow-sm">
            <div className="flex items-center justify-between">
                <div className="space-y-0.5">
                    <h3 className="text-sm font-black tracking-tight text-slate-900 uppercase">Evolução Mensal</h3>
                    <p className="text-[9px] font-bold text-slate-400 uppercase tracking-widest leading-none">Performance Fiscal</p>
                </div>
                <div className="flex items-center gap-4">
                    <div className="flex items-center gap-1.5">
                        <div className="w-2 h-2 rounded-full bg-emerald-500" />
                        <span className="text-[9px] font-bold text-slate-500 uppercase tracking-widest">Receitas</span>
                    </div>
                    <div className="flex items-center gap-1.5">
                        <div className="w-2 h-2 rounded-full bg-red-500" />
                        <span className="text-[9px] font-bold text-slate-500 uppercase tracking-widest">Despesas</span>
                    </div>
                </div>
            </div>

            <div className="flex-1 w-full min-h-[220px]">
                <ResponsiveContainer width="100%" height="100%">
                    <AreaChart data={data} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
                        <defs>
                            <linearGradient id="incomeGradient" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="5%" stopColor="#10b981" stopOpacity={0.15} />
                                <stop offset="95%" stopColor="#10b981" stopOpacity={0} />
                            </linearGradient>
                            <linearGradient id="expenseGradient" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="5%" stopColor="#ef4444" stopOpacity={0.15} />
                                <stop offset="95%" stopColor="#ef4444" stopOpacity={0} />
                            </linearGradient>
                        </defs>
                        <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" vertical={false} />
                        <XAxis
                            dataKey="month"
                            axisLine={false}
                            tickLine={false}
                            tick={{ fill: '#94a3b8', fontSize: 10, fontWeight: '700' }}
                            dy={15}
                        />
                        <YAxis
                            axisLine={false}
                            tickLine={false}
                            tick={{ fill: '#94a3b8', fontSize: 10, fontWeight: '700' }}
                        />
                        <Tooltip content={<CustomTooltip />} cursor={{ stroke: '#e2e8f0' }} />
                        <Area
                            type="monotone"
                            dataKey="income"
                            stroke="#10b981"
                            strokeWidth={3}
                            fill="url(#incomeGradient)"
                            dot={{ fill: '#fff', r: 4, strokeWidth: 2, stroke: '#10b981' }}
                            activeDot={{ r: 6, strokeWidth: 0, fill: '#10b981' }}
                        />
                        <Area
                            type="monotone"
                            dataKey="expenses"
                            stroke="#ef4444"
                            strokeWidth={3}
                            fill="url(#expenseGradient)"
                            dot={{ fill: '#fff', r: 4, strokeWidth: 2, stroke: '#ef4444' }}
                            activeDot={{ r: 6, strokeWidth: 0, fill: '#ef4444' }}
                        />
                    </AreaChart>
                </ResponsiveContainer>
            </div>
        </div>
    );
}
