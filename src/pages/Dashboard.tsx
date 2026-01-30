import * as React from 'react';
import { supabase } from '@/db/client';
import { transactionsApi, forecastsApi, billsToReceiveApi } from '@/db/api';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
  Calendar,
  Sparkles,
  Target,
  Activity,
  DollarSign
} from 'lucide-react';
import type { DashboardStats, CategoryExpense, MonthlyData, FinancialForecast } from '@/types/types';
import {
  BarChart,
  Bar,
  PieChart,
  Pie,
  Cell,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer,
  LineChart,
  Line
} from 'recharts';
import { BalanceCards } from '@/components/dashboard/BalanceCards';
import { CategoryBreakdown } from '@/components/dashboard/CategoryBreakdown';
import { SpendingChart } from '@/components/dashboard/SpendingChart';
import { useFinanceScope } from '@/hooks/useFinanceScope';

interface EnhancedStats extends DashboardStats {
  savingsRate: number;
  averageDailyExpense: number;
  projectedMonthEnd: number;
}

export default function Dashboard() {
  const { companyId, isPJ } = useFinanceScope();
  const [stats, setStats] = React.useState<DashboardStats | null>(null);
  const [enhancedStats, setEnhancedStats] = React.useState<EnhancedStats | null>(null);
  const [categoryExpenses, setCategoryExpenses] = React.useState<CategoryExpense[]>([]);
  const [monthlyData, setMonthlyData] = React.useState<MonthlyData[]>([]);
  const [forecast, setForecast] = React.useState<FinancialForecast | null>(null);
  const [pendingToReceive, setPendingToReceive] = React.useState(0);
  const [isLoading, setIsLoading] = React.useState(true);

  // Estado para mês/ano selecionado
  const [selectedMonth, setSelectedMonth] = React.useState(new Date().getMonth().toString());
  const [selectedYear, setSelectedYear] = React.useState(new Date().getFullYear().toString());

  React.useEffect(() => {
    loadDashboardData();
  }, [selectedMonth, selectedYear, companyId]);

  const loadDashboardData = async () => {
    try {
      setIsLoading(true);
      const { data: { user } } = await supabase.auth.getUser();
      if (!user) return;

      const year = parseInt(selectedYear);
      const month = parseInt(selectedMonth);
      const firstDayOfMonth = new Date(year, month, 1).toISOString().split('T')[0];
      const lastDayOfMonth = new Date(year, month + 1, 0).toISOString().split('T')[0];

      // Carregar dados básicos incluindo previsão, filtrando pelo ID da URL (null para PF)
      const [dashboardStats, expenses, monthly, latestForecast] = await Promise.all([
        transactionsApi.getDashboardStats(user.id, companyId),
        transactionsApi.getCategoryExpenses(user.id, firstDayOfMonth, lastDayOfMonth, companyId),
        transactionsApi.getMonthlyData(user.id, 6, { companyId: companyId }),
        forecastsApi.getLatest(user.id, companyId).catch(() => null)
      ]);

      setStats(dashboardStats);
      setCategoryExpenses(expenses);
      setMonthlyData(monthly);
      setForecast(latestForecast);

      // Carregar contas a receber pendentes
      try {
        const pendingBills = await billsToReceiveApi.getPending(user.id);
        const totalPending = pendingBills.reduce((sum, bill) => sum + bill.amount, 0);
        setPendingToReceive(totalPending);
      } catch (err) {
        console.error('Erro ao carregar contas a receber:', err);
        setPendingToReceive(0);
      }

      // Calcular estatísticas avançadas
      await loadEnhancedStats(user.id, dashboardStats, year, month);
    } catch (error) {
      console.error('Erro ao carregar dados do dashboard:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const formatDate = (dateStr: string, options?: Intl.DateTimeFormatOptions) => {
    if (!dateStr) return '';
    try {
      const [year, month, day] = dateStr.split('T')[0].split('-').map(Number);
      return new Date(year, month - 1, day).toLocaleDateString('pt-BR', options);
    } catch (e) {
      return dateStr;
    }
  };

  const loadEnhancedStats = async (
    userId: string,
    baseStats: DashboardStats,
    year: number,
    month: number
  ) => {
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const now = new Date();
    const isCurrentMonth = year === now.getFullYear() && month === now.getMonth();
    const currentDay = isCurrentMonth ? now.getDate() : daysInMonth;

    const firstDay = new Date(year, month, 1).toISOString().split('T')[0];
    const lastDay = new Date(year, month + 1, 0).toISOString().split('T')[0];

    let transactionsQuery = supabase
      .from('transactions')
      .select('amount, type, is_transfer')
      .eq('user_id', userId)
      .gte('date', firstDay)
      .lte('date', lastDay);

    if (companyId) {
      transactionsQuery = transactionsQuery.eq('company_id', companyId);
    } else {
      transactionsQuery = transactionsQuery.is('company_id', null);
    }

    const { data: transactions } = await transactionsQuery;

    // Filtrar transferências internas - não contam como receita/despesa real
    const monthlyIncome = transactions
      ?.filter((t: any) => t.type === 'income' && !t.is_transfer)
      .reduce((sum: number, t: any) => sum + t.amount, 0) || 0;

    const monthlyExpenses = transactions
      ?.filter((t: any) => t.type === 'expense' && !t.is_transfer)
      .reduce((sum: number, t: any) => sum + t.amount, 0) || 0;

    const savingsRate = monthlyIncome > 0
      ? ((monthlyIncome - monthlyExpenses) / monthlyIncome) * 100
      : 0;

    const averageDailyExpense = currentDay > 0 ? monthlyExpenses / currentDay : 0;
    const projectedMonthEnd = isCurrentMonth ? averageDailyExpense * daysInMonth : monthlyExpenses;

    setEnhancedStats({
      ...baseStats,
      monthlyIncome,
      monthlyExpenses,
      savingsRate,
      averageDailyExpense,
      projectedMonthEnd
    });
  };

  const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL'
    }).format(value);
  };

  const formatPercent = (value: number) => {
    return `${value.toFixed(1)}%`;
  };

  const months = [
    { value: '0', label: 'Janeiro' },
    { value: '1', label: 'Fevereiro' },
    { value: '2', label: 'Março' },
    { value: '3', label: 'Abril' },
    { value: '4', label: 'Maio' },
    { value: '5', label: 'Junho' },
    { value: '6', label: 'Julho' },
    { value: '7', label: 'Agosto' },
    { value: '8', label: 'Setembro' },
    { value: '9', label: 'Outubro' },
    { value: '10', label: 'Novembro' },
    { value: '11', label: 'Dezembro' }
  ];

  const years = Array.from({ length: 5 }, (_, i) => {
    const year = new Date().getFullYear() - 2 + i;
    return { value: year.toString(), label: year.toString() };
  });

  // Extrair previsões futuras do forecast
  const getFuturePredictions = () => {
    if (!forecast) return null;

    const today = new Date().toISOString().split('T')[0];
    const dailyForecasts = forecast.forecast_daily || {};

    // Pegar próximos 7 dias
    const next7Days = Object.entries(dailyForecasts)
      .filter(([date]) => date > today)
      .sort(([a], [b]) => a.localeCompare(b))
      .slice(0, 7);

    // Pegar próximas 4 semanas
    const weeklyForecasts = forecast.forecast_weekly || {};
    const next4Weeks = Object.entries(weeklyForecasts)
      .sort(([a], [b]) => a.localeCompare(b))
      .slice(0, 4);

    // Pegar próximos 3 meses
    const monthlyForecasts = forecast.forecast_monthly || {};
    const next3Months = Object.entries(monthlyForecasts)
      .sort(([a], [b]) => a.localeCompare(b))
      .slice(0, 3);

    return {
      daily: next7Days,
      weekly: next4Weeks,
      monthly: next3Months
    };
  };

  const predictions = getFuturePredictions();

  if (isLoading) {
    return (
      <div className="p-6 space-y-6">
        <Skeleton className="h-12 w-64 bg-muted" />
        <div className="flex gap-3">
          <Skeleton className="h-10 w-40 bg-muted" />
          <Skeleton className="h-10 w-32 bg-muted" />
          <Skeleton className="h-10 w-32 bg-muted" />
        </div>
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
          {[...Array(4)].map((_, i) => (
            <Skeleton key={i} className="h-32 bg-muted" />
          ))}
        </div>
      </div>
    );
  }

  const balance = (enhancedStats?.monthlyIncome || 0) - (enhancedStats?.monthlyExpenses || 0);
  const isPositiveBalance = balance >= 0;

  const COLORS = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];

  return (
    <div className="p-6 space-y-6">
      {/* Header com Título e Filtros na mesma linha */}
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-foreground">
            Dashboard {isPJ ? 'Pessoa Jurídica' : 'Pessoa Física'}
          </h1>
          <p className="text-sm text-muted-foreground">
            {months.find(m => m.value === selectedMonth)?.label} de {selectedYear}
          </p>
        </div>

        {/* Filtros de Data */}
        <div className="flex items-center gap-3">
          <Select value={selectedMonth} onValueChange={setSelectedMonth}>
            <SelectTrigger className="w-[140px]">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              {months.map(month => (
                <SelectItem key={month.value} value={month.value}>
                  {month.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>

          <Select value={selectedYear} onValueChange={setSelectedYear}>
            <SelectTrigger className="w-[100px]">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              {years.map(year => (
                <SelectItem key={year.value} value={year.value}>
                  {year.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>

          <Button
            variant="default"
            size="sm"
            onClick={() => {
              const now = new Date();
              setSelectedMonth(now.getMonth().toString());
              setSelectedYear(now.getFullYear().toString());
            }}
          >
            Mês Atual
          </Button>
        </div>
      </div>

      {/* Balance Cards - New Design */}
      <BalanceCards
        totalBalance={stats?.totalBalance || 0}
        monthlyIncome={enhancedStats?.monthlyIncome || 0}
        monthlyExpenses={enhancedStats?.monthlyExpenses || 0}
        savingsRate={enhancedStats?.savingsRate || 0}
        pendingToReceive={pendingToReceive}
      />

      {/* Charts Grid */}
      <div className="grid gap-4 lg:grid-cols-3">
        <div className="lg:col-span-2 h-full">
          <SpendingChart data={monthlyData} />
        </div>
        <div className="lg:col-span-1 h-full">
          <CategoryBreakdown categories={categoryExpenses} />
        </div>
      </div>

      {/* Previsões Futuras */}
      {predictions && (
        <div className="space-y-4">
          <div className="flex items-center gap-2">
            <Sparkles className="h-5 w-5 text-primary" />
            <h2 className="text-2xl font-bold">Previsões Futuras</h2>
          </div>

          <div className="grid gap-4 md:grid-cols-3">
            {/* Previsão Próximos 7 Dias */}
            <Card>
              <CardHeader>
                <CardTitle className="text-lg flex items-center gap-2">
                  <Calendar className="h-4 w-4" />
                  Próximos 7 Dias
                </CardTitle>
                <CardDescription>Saldo previsto diário</CardDescription>
              </CardHeader>
              <CardContent>
                {predictions.daily.length > 0 ? (
                  <div className="space-y-2">
                    {predictions.daily.map(([date, balance]) => {
                      const formattedDate = formatDate(date, {
                        day: '2-digit',
                        month: 'short'
                      });
                      const isNegative = balance < 0;
                      return (
                        <div key={date} className="flex justify-between items-center text-sm">
                          <span className="text-muted-foreground">{formattedDate}</span>
                          <span className={`font-semibold ${isNegative ? 'text-red-600' : 'text-green-600'}`}>
                            {formatCurrency(balance)}
                          </span>
                        </div>
                      );
                    })}
                  </div>
                ) : (
                  <p className="text-sm text-muted-foreground">Sem previsões disponíveis</p>
                )}
              </CardContent>
            </Card>

            {/* Previsão Próximas 4 Semanas */}
            <Card>
              <CardHeader>
                <CardTitle className="text-lg flex items-center gap-2">
                  <Activity className="h-4 w-4" />
                  Próximas 4 Semanas
                </CardTitle>
                <CardDescription>Saldo previsto semanal</CardDescription>
              </CardHeader>
              <CardContent>
                {predictions.weekly.length > 0 ? (
                  <div className="space-y-2">
                    {predictions.weekly.map(([week, balance], idx) => {
                      const isNegative = balance < 0;
                      return (
                        <div key={week} className="flex justify-between items-center text-sm">
                          <span className="text-muted-foreground">Semana {idx + 1}</span>
                          <span className={`font-semibold ${isNegative ? 'text-red-600' : 'text-green-600'}`}>
                            {formatCurrency(balance)}
                          </span>
                        </div>
                      );
                    })}
                  </div>
                ) : (
                  <p className="text-sm text-muted-foreground">Sem previsões disponíveis</p>
                )}
              </CardContent>
            </Card>

            {/* Previsão Próximos 3 Meses */}
            <Card>
              <CardHeader>
                <CardTitle className="text-lg flex items-center gap-2">
                  <Target className="h-4 w-4" />
                  Próximos 3 Meses
                </CardTitle>
                <CardDescription>Saldo previsto mensal</CardDescription>
              </CardHeader>
              <CardContent>
                {predictions.monthly.length > 0 ? (
                  <div className="space-y-2">
                    {predictions.monthly.map(([month, balance]) => {
                      const monthName = formatDate(month, {
                        month: 'short',
                        year: '2-digit'
                      });
                      const isNegative = balance < 0;
                      return (
                        <div key={month} className="flex justify-between items-center text-sm">
                          <span className="text-muted-foreground capitalize">{monthName}</span>
                          <span className={`font-semibold ${isNegative ? 'text-red-600' : 'text-green-600'}`}>
                            {formatCurrency(balance)}
                          </span>
                        </div>
                      );
                    })}
                  </div>
                ) : (
                  <p className="text-sm text-muted-foreground">Sem previsões disponíveis</p>
                )}
              </CardContent>
            </Card>
          </div>
        </div>
      )}

      {/* Gráficos */}
      <div className="grid gap-4 md:grid-cols-2">
        {/* Gráfico de Despesas por Categoria */}
        <Card>
          <CardHeader>
            <CardTitle>Despesas por Categoria</CardTitle>
            <CardDescription>Distribuição dos gastos do mês</CardDescription>
          </CardHeader>
          <CardContent>
            {categoryExpenses.length > 0 ? (
              <ResponsiveContainer width="100%" height={300}>
                <PieChart>
                  <Pie
                    data={categoryExpenses}
                    cx="50%"
                    cy="50%"
                    labelLine={false}
                    label={({ category, percent }) => `${category} (${(percent * 100).toFixed(0)}%)`}
                    outerRadius={80}
                    fill="#8884d8"
                    dataKey="amount"
                    nameKey="category"
                  >
                    {categoryExpenses.map((_, index) => (
                      <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                    ))}
                  </Pie>
                  <Tooltip formatter={(value: number) => formatCurrency(value)} />
                </PieChart>
              </ResponsiveContainer>
            ) : (
              <div className="h-[300px] flex items-center justify-center text-muted-foreground">
                Sem dados de despesas
              </div>
            )}
          </CardContent>
        </Card>

        {/* Gráfico de Evolução Mensal */}
        <Card>
          <CardHeader>
            <CardTitle>Evolução Mensal</CardTitle>
            <CardDescription>Últimos 6 meses</CardDescription>
          </CardHeader>
          <CardContent>
            {monthlyData.length > 0 ? (
              <ResponsiveContainer width="100%" height={300}>
                <BarChart data={monthlyData}>
                  <CartesianGrid strokeDasharray="3 3" />
                  <XAxis dataKey="month" />
                  <YAxis />
                  <Tooltip formatter={(value: number) => formatCurrency(value)} />
                  <Legend />
                  <Bar dataKey="income" name="Receitas" fill="#10b981" />
                  <Bar dataKey="expenses" name="Despesas" fill="#ef4444" />
                </BarChart>
              </ResponsiveContainer>
            ) : (
              <div className="h-[300px] flex items-center justify-center text-muted-foreground">
                Sem dados mensais
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      {/* Insights da IA */}
      {forecast && forecast.insights && forecast.insights.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Sparkles className="h-5 w-5 text-primary" />
              Insights da IA
            </CardTitle>
            <CardDescription>Análises inteligentes sobre suas finanças</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-2">
              {forecast.insights.map((insight, idx) => (
                <div
                  key={idx}
                  className="flex items-start gap-2 p-3 rounded-lg bg-primary/5 border border-primary/10"
                >
                  <DollarSign className="h-4 w-4 text-primary mt-0.5 flex-shrink-0" />
                  <p className="text-sm">{insight}</p>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
