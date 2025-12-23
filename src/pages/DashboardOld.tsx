import * as React from 'react';
import { supabase } from '@/db/client';
import { transactionsApi, forecastsApi } from '@/db/api';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { 
  Wallet, 
  TrendingUp, 
  TrendingDown, 
  CreditCard, 
  PiggyBank,
  Target,
  Calendar,
  ArrowUpRight,
  ArrowDownRight,
  DollarSign,
  Percent,
  Activity,
  ChevronLeft,
  ChevronRight,
  Sparkles,
  AlertTriangle,
  TrendingUpIcon
} from 'lucide-react';
import type { DashboardStats, CategoryExpense, MonthlyData, TransactionWithDetails, FinancialForecast } from '@/types/types';
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
  Line,
  Area,
  AreaChart,
  RadialBarChart,
  RadialBar
} from 'recharts';

// Interface para dados adicionais do dashboard
interface EnhancedStats extends DashboardStats {
  savingsRate: number;
  averageDailyExpense: number;
  projectedMonthEnd: number;
  topExpenseCategory: string;
  topExpenseAmount: number;
}

interface DailyBalance {
  day: string;
  balance: number;
  income: number;
  expense: number;
}

interface AccountBalance {
  name: string;
  balance: number;
  percentage: number;
}

export default function Dashboard() {
  const [stats, setStats] = React.useState<DashboardStats | null>(null);
  const [enhancedStats, setEnhancedStats] = React.useState<EnhancedStats | null>(null);
  const [categoryExpenses, setCategoryExpenses] = React.useState<CategoryExpense[]>([]);
  const [monthlyData, setMonthlyData] = React.useState<MonthlyData[]>([]);
  const [dailyBalance, setDailyBalance] = React.useState<DailyBalance[]>([]);
  const [accountBalances, setAccountBalances] = React.useState<AccountBalance[]>([]);
  const [recentTransactions, setRecentTransactions] = React.useState<TransactionWithDetails[]>([]);
  const [forecast, setForecast] = React.useState<FinancialForecast | null>(null);
  const [isLoading, setIsLoading] = React.useState(true);
  
  // Estado para mês/ano selecionado
  const [selectedDate, setSelectedDate] = React.useState(new Date());
  const [selectedMonth, setSelectedMonth] = React.useState(new Date().getMonth().toString());
  const [selectedYear, setSelectedYear] = React.useState(new Date().getFullYear().toString());

  React.useEffect(() => {
    loadDashboardData();
  }, [selectedDate]); // Recarregar quando o mês mudar

  const loadDashboardData = async () => {
    try {
      setIsLoading(true);
      const { data: { user } } = await supabase.auth.getUser();
      if (!user) return;

      // Usar a data selecionada ao invés da data atual
      const year = selectedDate.getFullYear();
      const month = selectedDate.getMonth();
      const firstDayOfMonth = new Date(year, month, 1).toISOString().split('T')[0];
      const lastDayOfMonth = new Date(year, month + 1, 0).toISOString().split('T')[0];

      // Carregar dados básicos incluindo previsão
      const [dashboardStats, expenses, monthly, latestForecast] = await Promise.all([
        transactionsApi.getDashboardStats(user.id),
        transactionsApi.getCategoryExpenses(user.id, firstDayOfMonth, lastDayOfMonth),
        transactionsApi.getMonthlyData(user.id, 6),
        forecastsApi.getLatest(user.id).catch(() => null)
      ]);

      setStats(dashboardStats);
      setCategoryExpenses(expenses);
      setMonthlyData(monthly);
      setForecast(latestForecast);

      // Calcular estatísticas avançadas
      await loadEnhancedStats(user.id, dashboardStats, expenses, year, month);
      await loadDailyBalance(user.id, year, month);
      await loadAccountBalances(user.id);
      await loadRecentTransactions(user.id, firstDayOfMonth, lastDayOfMonth);
    } catch (error) {
      console.error('Erro ao carregar dados do dashboard:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const loadEnhancedStats = async (
    userId: string, 
    baseStats: DashboardStats, 
    expenses: CategoryExpense[],
    year: number,
    month: number
  ) => {
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const now = new Date();
    const isCurrentMonth = year === now.getFullYear() && month === now.getMonth();
    const currentDay = isCurrentMonth ? now.getDate() : daysInMonth;

    // Calcular receitas e despesas do mês selecionado
    const firstDay = new Date(year, month, 1).toISOString().split('T')[0];
    const lastDay = new Date(year, month + 1, 0).toISOString().split('T')[0];

    const { data: transactions } = await supabase
      .from('transactions')
      .select('amount, type')
      .eq('user_id', userId)
      .gte('date', firstDay)
      .lte('date', lastDay);

    const monthlyIncome = transactions
      ?.filter(t => t.type === 'income')
      .reduce((sum, t) => sum + t.amount, 0) || 0;

    const monthlyExpenses = transactions
      ?.filter(t => t.type === 'expense')
      .reduce((sum, t) => sum + t.amount, 0) || 0;

    // Taxa de poupança
    const savingsRate = monthlyIncome > 0 
      ? ((monthlyIncome - monthlyExpenses) / monthlyIncome) * 100 
      : 0;

    // Média de gastos diários
    const averageDailyExpense = currentDay > 0 ? monthlyExpenses / currentDay : 0;

    // Projeção para fim do mês (só faz sentido para o mês atual)
    const projectedMonthEnd = isCurrentMonth ? averageDailyExpense * daysInMonth : monthlyExpenses;

    // Maior categoria de despesa
    const topExpense = expenses.length > 0 
      ? expenses.reduce((max, cat) => cat.amount > max.amount ? cat : max, expenses[0])
      : { category: 'N/A', amount: 0 };

    setEnhancedStats({
      ...baseStats,
      monthlyIncome,
      monthlyExpenses,
      savingsRate,
      averageDailyExpense,
      projectedMonthEnd,
      topExpenseCategory: topExpense.category,
      topExpenseAmount: topExpense.amount
    });
  };

  const loadDailyBalance = async (userId: string, year: number, month: number) => {
    try {
      const firstDayOfMonth = new Date(year, month, 1).toISOString().split('T')[0];
      const lastDayOfMonth = new Date(year, month + 1, 0).toISOString().split('T')[0];
      const daysInMonth = new Date(year, month + 1, 0).getDate();

      const { data: transactions } = await supabase
        .from('transactions')
        .select('date, amount, type')
        .eq('user_id', userId)
        .gte('date', firstDayOfMonth)
        .lte('date', lastDayOfMonth)
        .order('date', { ascending: true });

      if (!transactions) return;

      // Agrupar por dia
      const dailyMap = new Map<string, { income: number; expense: number }>();
      
      transactions.forEach(t => {
        const day = new Date(t.date).getDate().toString();
        const current = dailyMap.get(day) || { income: 0, expense: 0 };
        
        if (t.type === 'income') {
          current.income += t.amount;
        } else {
          current.expense += t.amount;
        }
        
        dailyMap.set(day, current);
      });

      // Converter para array e calcular saldo acumulado
      let cumulativeBalance = 0;
      const dailyData: DailyBalance[] = [];
      
      const now = new Date();
      const isCurrentMonth = year === now.getFullYear() && month === now.getMonth();
      const maxDay = isCurrentMonth ? now.getDate() : daysInMonth;
      
      for (let i = 1; i <= maxDay; i++) {
        const day = i.toString();
        const data = dailyMap.get(day) || { income: 0, expense: 0 };
        cumulativeBalance += data.income - data.expense;
        
        dailyData.push({
          day: `Dia ${i}`,
          balance: cumulativeBalance,
          income: data.income,
          expense: data.expense
        });
      }

      setDailyBalance(dailyData);
    } catch (error) {
      console.error('Erro ao carregar saldo diário:', error);
    }
  };

  const loadAccountBalances = async (userId: string) => {
    try {
      const { data: accounts } = await supabase
        .from('accounts')
        .select('name, balance')
        .eq('user_id', userId);

      if (!accounts || accounts.length === 0) return;

      const total = accounts.reduce((sum, acc) => sum + acc.balance, 0);
      
      const balances: AccountBalance[] = accounts.map(acc => ({
        name: acc.name,
        balance: acc.balance,
        percentage: total > 0 ? (acc.balance / total) * 100 : 0
      }));

      setAccountBalances(balances);
    } catch (error) {
      console.error('Error loading account balances:', error);
    }
  };

  const loadRecentTransactions = async (userId: string, startDate: string, endDate: string) => {
    try {
      // Obter últimas 5 transações do mês selecionado
      const { data: transactions } = await supabase
        .from('transactions')
        .select(`
          *,
          category:categories(name),
          account:accounts(name)
        `)
        .eq('user_id', userId)
        .gte('date', startDate)
        .lte('date', endDate)
        .order('date', { ascending: false })
        .limit(5);

      if (transactions) {
        setRecentTransactions(transactions as TransactionWithDetails[]);
      }
    } catch (error) {
      console.error('Erro ao carregar transações recentes:', error);
    }
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

  // Funções de navegação de mês
  const handleMonthChange = (month: string) => {
    setSelectedMonth(month);
    const newDate = new Date(parseInt(selectedYear), parseInt(month), 1);
    setSelectedDate(newDate);
  };

  const handleYearChange = (year: string) => {
    setSelectedYear(year);
    const newDate = new Date(parseInt(year), parseInt(selectedMonth), 1);
    setSelectedDate(newDate);
  };

  const goToPreviousMonth = () => {
    setSelectedDate(prev => {
      const newDate = new Date(prev);
      newDate.setMonth(newDate.getMonth() - 1);
      setSelectedMonth(newDate.getMonth().toString());
      setSelectedYear(newDate.getFullYear().toString());
      return newDate;
    });
  };

  const goToNextMonth = () => {
    setSelectedDate(prev => {
      const newDate = new Date(prev);
      newDate.setMonth(newDate.getMonth() + 1);
      setSelectedMonth(newDate.getMonth().toString());
      setSelectedYear(newDate.getFullYear().toString());
      return newDate;
    });
  };

  const goToCurrentMonth = () => {
    const now = new Date();
    setSelectedDate(now);
    setSelectedMonth(now.getMonth().toString());
    setSelectedYear(now.getFullYear().toString());
  };

  const isCurrentMonth = () => {
    const now = new Date();
    return selectedDate.getFullYear() === now.getFullYear() && 
           selectedDate.getMonth() === now.getMonth();
  };

  const formatSelectedMonth = () => {
    return selectedDate.toLocaleDateString('pt-BR', { 
      month: 'long', 
      year: 'numeric' 
    });
  };

  if (isLoading) {
    return (
      <div className="container mx-auto p-6 space-y-6">
        <h1 className="text-3xl font-bold">Dashboard Financeiro</h1>
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
          {[...Array(8)].map((_, i) => (
            <Card key={i}>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <Skeleton className="h-4 w-24 bg-muted" />
                <Skeleton className="h-4 w-4 bg-muted" />
              </CardHeader>
              <CardContent>
                <Skeleton className="h-8 w-32 bg-muted" />
              </CardContent>
            </Card>
          ))}
        </div>
      </div>
    );
  }

  const balance = (enhancedStats?.monthlyIncome || 0) - (enhancedStats?.monthlyExpenses || 0);
  const isPositiveBalance = balance >= 0;

  return (
    <div className="container mx-auto p-6 space-y-6">
      {/* Header com Seletor de Mês */}
      <div className="flex flex-col gap-4">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold text-foreground">Dashboard Financeiro</h1>
            <p className="text-muted-foreground mt-1">
              Visão geral das suas finanças
            </p>
          </div>
          <Badge variant={isPositiveBalance ? "default" : "destructive"} className="text-lg px-4 py-2">
            {isPositiveBalance ? '✓ Positivo' : '⚠ Negativo'}
          </Badge>
        </div>

        {/* Seletor de Mês */}
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center justify-between gap-4">
              <Button
                variant="outline"
                size="icon"
                onClick={goToPreviousMonth}
                title="Mês anterior"
              >
                <ChevronLeft className="h-4 w-4" />
              </Button>

              <div className="flex items-center gap-4 flex-1 justify-center">
                <div className="flex items-center gap-2">
                  <Calendar className="h-5 w-5 text-primary" />
                  <span className="text-xl font-semibold capitalize">
                    {formatSelectedMonth()}
                  </span>
                </div>
                {!isCurrentMonth() && (
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={goToCurrentMonth}
                  >
                    Mês Atual
                  </Button>
                )}
              </div>

              <Button
                variant="outline"
                size="icon"
                onClick={goToNextMonth}
                disabled={isCurrentMonth()}
                title="Próximo mês"
              >
                <ChevronRight className="h-4 w-4" />
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Cards de Indicadores Principais */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        {/* Saldo Total */}
        <Card className="hover:shadow-lg transition-shadow">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Saldo Total</CardTitle>
            <Wallet className="h-5 w-5 text-primary" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{formatCurrency(stats?.totalBalance || 0)}</div>
            <p className="text-xs text-muted-foreground mt-1">
              {stats?.accountsCount || 0} contas ativas
            </p>
          </CardContent>
        </Card>

        {/* Receitas do Mês */}
        <Card className="hover:shadow-lg transition-shadow">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Receitas do Mês</CardTitle>
            <TrendingUp className="h-5 w-5 text-income" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-income">{formatCurrency(enhancedStats?.monthlyIncome || 0)}</div>
            <div className="flex items-center gap-1 mt-1">
              <ArrowUpRight className="h-3 w-3 text-income" />
              <p className="text-xs text-muted-foreground">Entradas</p>
            </div>
          </CardContent>
        </Card>

        {/* Despesas do Mês */}
        <Card className="hover:shadow-lg transition-shadow">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Despesas do Mês</CardTitle>
            <TrendingDown className="h-5 w-5 text-expense" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-expense">{formatCurrency(enhancedStats?.monthlyExpenses || 0)}</div>
            <div className="flex items-center gap-1 mt-1">
              <ArrowDownRight className="h-3 w-3 text-expense" />
              <p className="text-xs text-muted-foreground">Saídas</p>
            </div>
          </CardContent>
        </Card>

        {/* Balanço do Mês */}
        <Card className="hover:shadow-lg transition-shadow">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Balanço do Mês</CardTitle>
            <Activity className="h-5 w-5 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className={`text-2xl font-bold ${isPositiveBalance ? 'text-income' : 'text-expense'}`}>
              {formatCurrency(balance)}
            </div>
            <p className="text-xs text-muted-foreground mt-1">
              {isPositiveBalance ? 'Superávit' : 'Déficit'}
            </p>
          </CardContent>
        </Card>
      </div>

      {/* Cards de Indicadores Secundários */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        {/* Taxa de Poupança */}
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Taxa de Poupança</CardTitle>
            <PiggyBank className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{formatPercent(enhancedStats?.savingsRate || 0)}</div>
            <Progress 
              value={Math.max(0, Math.min(100, enhancedStats?.savingsRate || 0))} 
              className="mt-2"
            />
          </CardContent>
        </Card>

        {/* Média Diária */}
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Gasto Médio/Dia</CardTitle>
            <Calendar className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{formatCurrency(enhancedStats?.averageDailyExpense || 0)}</div>
            <p className="text-xs text-muted-foreground mt-1">
              Média do mês atual
            </p>
          </CardContent>
        </Card>

        {/* Projeção Fim do Mês */}
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Projeção Mensal</CardTitle>
            <Target className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{formatCurrency(enhancedStats?.projectedMonthEnd || 0)}</div>
            <p className="text-xs text-muted-foreground mt-1">
              Estimativa de gastos
            </p>
          </CardContent>
        </Card>

        {/* Maior Despesa */}
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Maior Categoria</CardTitle>
            <DollarSign className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-lg font-bold truncate">{enhancedStats?.topExpenseCategory || 'N/A'}</div>
            <p className="text-xs text-muted-foreground mt-1">
              {formatCurrency(enhancedStats?.topExpenseAmount || 0)}
            </p>
          </CardContent>
        </Card>
      </div>

      {/* Gráficos Principais */}
      <div className="grid gap-4 md:grid-cols-2">
        {/* Fluxo de Caixa Diário */}
        <Card>
          <CardHeader>
            <CardTitle>Fluxo de Caixa Diário</CardTitle>
            <CardDescription>Evolução do saldo ao longo do mês</CardDescription>
          </CardHeader>
          <CardContent>
            {dailyBalance.length > 0 ? (
              <ResponsiveContainer width="100%" height={300}>
                <AreaChart data={dailyBalance}>
                  <defs>
                    <linearGradient id="colorBalance" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="5%" stopColor="hsl(var(--primary))" stopOpacity={0.8}/>
                      <stop offset="95%" stopColor="hsl(var(--primary))" stopOpacity={0}/>
                    </linearGradient>
                  </defs>
                  <CartesianGrid strokeDasharray="3 3" />
                  <XAxis dataKey="day" />
                  <YAxis />
                  <Tooltip formatter={(value: number) => formatCurrency(value)} />
                  <Area 
                    type="monotone" 
                    dataKey="balance" 
                    stroke="hsl(var(--primary))" 
                    fillOpacity={1} 
                    fill="url(#colorBalance)" 
                    name="Saldo"
                  />
                </AreaChart>
              </ResponsiveContainer>
            ) : (
              <div className="flex items-center justify-center h-[300px] text-muted-foreground">
                Nenhum dado disponível
              </div>
            )}
          </CardContent>
        </Card>

        {/* Despesas por Categoria */}
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
                    dataKey="amount"
                    nameKey="category"
                    cx="50%"
                    cy="50%"
                    outerRadius={100}
                    label={(entry) => `${entry.category}: ${formatPercent((entry.amount / (enhancedStats?.monthlyExpenses || 1)) * 100)}`}
                  >
                    {categoryExpenses.map((entry, index) => (
                      <Cell key={`cell-${index}`} fill={entry.color} />
                    ))}
                  </Pie>
                  <Tooltip formatter={(value: number) => formatCurrency(value)} />
                </PieChart>
              </ResponsiveContainer>
            ) : (
              <div className="flex items-center justify-center h-[300px] text-muted-foreground">
                Nenhuma despesa registrada este mês
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      {/* Gráficos Secundários */}
      <div className="grid gap-4 md:grid-cols-2">
        {/* Histórico Mensal */}
        <Card>
          <CardHeader>
            <CardTitle>Histórico Mensal</CardTitle>
            <CardDescription>Receitas vs Despesas dos últimos 6 meses</CardDescription>
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
                  <Bar dataKey="income" name="Receitas" fill="hsl(var(--income))" radius={[8, 8, 0, 0]} />
                  <Bar dataKey="expenses" name="Despesas" fill="hsl(var(--expense))" radius={[8, 8, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>
            ) : (
              <div className="flex items-center justify-center h-[300px] text-muted-foreground">
                Nenhum dado disponível
              </div>
            )}
          </CardContent>
        </Card>

        {/* Tendência de Balanço */}
        <Card>
          <CardHeader>
            <CardTitle>Tendência de Balanço</CardTitle>
            <CardDescription>Evolução do saldo mensal</CardDescription>
          </CardHeader>
          <CardContent>
            {monthlyData.length > 0 ? (
              <ResponsiveContainer width="100%" height={300}>
                <LineChart data={monthlyData.map(m => ({
                  ...m,
                  balance: m.income - m.expenses
                }))}>
                  <CartesianGrid strokeDasharray="3 3" />
                  <XAxis dataKey="month" />
                  <YAxis />
                  <Tooltip formatter={(value: number) => formatCurrency(value)} />
                  <Legend />
                  <Line 
                    type="monotone" 
                    dataKey="balance" 
                    name="Balanço" 
                    stroke="hsl(var(--primary))" 
                    strokeWidth={3}
                    dot={{ r: 5 }}
                  />
                </LineChart>
              </ResponsiveContainer>
            ) : (
              <div className="flex items-center justify-center h-[300px] text-muted-foreground">
                Nenhum dado disponível
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      {/* Distribuição de Saldo por Conta */}
      {accountBalances.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle>Distribuição de Saldo por Conta</CardTitle>
            <CardDescription>Percentual do saldo total em cada conta</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              {accountBalances.map((account, index) => (
                <div key={index} className="space-y-2">
                  <div className="flex items-center justify-between">
                    <span className="text-sm font-medium">{account.name}</span>
                    <span className="text-sm text-muted-foreground">
                      {formatCurrency(account.balance)} ({formatPercent(account.percentage)})
                    </span>
                  </div>
                  <Progress value={account.percentage} className="h-2" />
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      )}

      {/* Transações Recentes */}
      {recentTransactions.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle>Transações Recentes</CardTitle>
            <CardDescription>Últimas 5 movimentações</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              {recentTransactions.map((transaction) => (
                <div key={transaction.id} className="flex items-center justify-between p-3 rounded-lg border">
                  <div className="flex items-center gap-3">
                    <div className={`p-2 rounded-full ${transaction.type === 'income' ? 'bg-income/10' : 'bg-expense/10'}`}>
                      {transaction.type === 'income' ? (
                        <ArrowUpRight className="h-4 w-4 text-income" />
                      ) : (
                        <ArrowDownRight className="h-4 w-4 text-expense" />
                      )}
                    </div>
                    <div>
                      <p className="font-medium">{transaction.description}</p>
                      <p className="text-xs text-muted-foreground">
                        {new Date(transaction.date).toLocaleDateString('pt-BR')}
                      </p>
                    </div>
                  </div>
                  <div className="text-right">
                    <p className={`font-bold ${transaction.type === 'income' ? 'text-income' : 'text-expense'}`}>
                      {transaction.type === 'income' ? '+' : '-'}{formatCurrency(transaction.amount)}
                    </p>
                    <Badge variant="outline" className="text-xs">
                      {transaction.category?.name || 'Sem categoria'}
                    </Badge>
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
