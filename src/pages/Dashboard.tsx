import * as React from 'react';
import { supabase } from '@/db/client';
import { transactionsApi, forecastsApi, billsToReceiveApi } from '@/db/api';
import { Skeleton } from '@/components/ui/skeleton';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
  Calendar,
  Bot,
  Wallet
} from 'lucide-react';
import type { DashboardStats, CategoryExpense, MonthlyData, FinancialForecast } from '@/types/types';

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
  const { companyId, personId } = useFinanceScope();
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
  }, [selectedMonth, selectedYear, companyId, personId]);

  const loadDashboardData = async () => {
    try {
      setIsLoading(true);
      const { data: { user } } = await supabase.auth.getUser();
      if (!user) return;

      const year = parseInt(selectedYear);
      const month = parseInt(selectedMonth);
      const firstDayOfMonth = new Date(year, month, 1).toISOString().split('T')[0];
      const lastDayOfMonth = new Date(year, month + 1, 0).toISOString().split('T')[0];

      const [dashboardStats, expenses, monthly, latestForecast] = await Promise.all([
        transactionsApi.getDashboardStats(user.id, companyId, personId),
        transactionsApi.getCategoryExpenses(user.id, firstDayOfMonth, lastDayOfMonth, companyId, personId),
        transactionsApi.getMonthlyData(user.id, 6, { companyId: companyId, personId: personId }),
        forecastsApi.getLatest(user.id, companyId, personId).catch(() => null)
      ]);

      setStats(dashboardStats);
      setCategoryExpenses(expenses);
      setMonthlyData(monthly);
      setForecast(latestForecast);

      try {
        const pendingBills = await billsToReceiveApi.getPending(user.id);
        const totalPending = pendingBills.reduce((sum, bill) => sum + bill.amount, 0);
        setPendingToReceive(totalPending);
      } catch (err) {
        setPendingToReceive(0);
      }

      await loadEnhancedStats(user.id, dashboardStats, year, month);
    } catch (error) {
      console.error('Erro ao carregar dados do dashboard:', error);
    } finally {
      setIsLoading(false);
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

    if (personId) {
      transactionsQuery = transactionsQuery.eq('person_id', personId);
    } else if (personId === null) {
      transactionsQuery = transactionsQuery.is('person_id', null);
    }

    const { data: transactions } = await transactionsQuery;

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

  if (isLoading) {
    return (
      <div className="p-8 space-y-8 animate-pulse">
        <Skeleton className="h-10 w-64 rounded-xl" />
        <div className="grid gap-6 md:grid-cols-4">
          {[...Array(4)].map((_, i) => (
            <Skeleton key={i} className="h-32 rounded-[1.5rem]" />
          ))}
        </div>
        <div className="grid gap-6 lg:grid-cols-3">
          <Skeleton className="lg:col-span-2 h-[400px] rounded-[1.5rem]" />
          <Skeleton className="h-[400px] rounded-[1.5rem]" />
        </div>
      </div>
    );
  }

  const currentMonthLabel = months.find(m => m.value === selectedMonth)?.label;

  return (
    <div className="p-8 lg:p-12 space-y-10 max-w-[1600px] mx-auto animate-slide-up">
      {/* Page Header */}
      <header className="space-y-6">
        <div>
          <h1 className="text-3xl font-bold tracking-tight text-slate-900 uppercase">
            Painel Financeiro
          </h1>
          <p className="text-slate-500 font-medium">
            {currentMonthLabel} de {selectedYear}
          </p>
        </div>

        {/* Filters Bar */}
        <div className="flex flex-wrap items-center gap-4">
          <div className="space-y-1.5">
            <span className="text-[10px] font-bold text-slate-500 uppercase tracking-wider ml-1">Mês</span>
            <Select value={selectedMonth} onValueChange={setSelectedMonth}>
              <SelectTrigger className="w-[180px] bg-white border-slate-300 rounded-xl h-11 text-sm font-semibold shadow-sm">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {months.map(month => (
                  <SelectItem key={month.value} value={month.value} className="font-medium">
                    {month.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          <div className="space-y-1.5">
            <span className="text-[10px] font-bold text-slate-500 uppercase tracking-wider ml-1">Ano</span>
            <Select value={selectedYear} onValueChange={setSelectedYear}>
              <SelectTrigger className="w-[120px] bg-white border-slate-300 rounded-xl h-11 text-sm font-semibold shadow-sm">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {years.map(year => (
                  <SelectItem key={year.value} value={year.value} className="font-medium">
                    {year.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          <div className="pt-5">
            <Button
              className="bg-blue-600 hover:bg-blue-700 text-white font-bold h-11 px-8 rounded-xl shadow-sm transition-all"
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
      </header>

      {/* Primary Stats */}
      <section className="space-y-6">
        <BalanceCards
          totalBalance={stats?.totalBalance || 0}
          monthlyIncome={enhancedStats?.monthlyIncome || 0}
          monthlyExpenses={enhancedStats?.monthlyExpenses || 0}
          savingsRate={enhancedStats?.savingsRate || 0}
          pendingToReceive={pendingToReceive}
        />
      </section>

      {/* Main Insights Grid */}
      <div className="grid gap-8 lg:grid-cols-3">
        <div className="lg:col-span-2">
          <SpendingChart data={monthlyData} />
        </div>
        <div>
          <CategoryBreakdown categories={categoryExpenses} />
        </div>
      </div>

      {/* Featured Section: Contas a Pagar (Simulated based on image layout) */}
      <section className="section-container space-y-8">
        <div className="flex items-center gap-3 border-b-2 border-slate-100 pb-4">
          <div className="bg-red-500/10 p-2.5 rounded-xl border border-red-200/50">
            <Calendar className="h-6 w-6 text-red-500" />
          </div>
          <div>
            <h2 className="text-xl font-bold tracking-tight text-slate-900">Contas a Pagar</h2>
            <p className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Gestão de Vencimentos</p>
          </div>
        </div>

        <div className="grid gap-8 lg:grid-cols-2">
          <div className="glass-card premium-card p-10 flex flex-col justify-between">
            <div className="space-y-4">
              <h3 className="text-sm font-semibold text-slate-500 uppercase tracking-wider">Resumo de Contas</h3>
              <p className="text-xs text-slate-400">Status das contas a pagar</p>
            </div>

            <div className="bg-slate-100/80 p-8 rounded-2xl border border-slate-200 flex items-center justify-between my-8">
              <div className="space-y-1">
                <span className="text-[10px] font-bold text-slate-400 uppercase">Total a Pagar</span>
                <h4 className="text-4xl font-bold text-red-500 tracking-tight">{formatCurrency(100)}</h4>
              </div>
              <div className="p-3 bg-red-100/80 rounded-lg border border-red-200">
                <Wallet className="h-6 w-6 text-red-600" />
              </div>
            </div>

            <div className="space-y-4">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <div className="h-2.5 w-2.5 rounded-full bg-amber-500" />
                  <span className="text-sm font-semibold text-slate-600">Pendentes</span>
                </div>
                <div className="text-right">
                  <p className="text-sm font-bold text-slate-900">{formatCurrency(100)}</p>
                  <p className="text-[10px] text-slate-400">1 conta</p>
                </div>
              </div>
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <div className="h-2.5 w-2.5 rounded-full bg-red-500" />
                  <span className="text-sm font-semibold text-slate-600">Vencidas</span>
                </div>
                <div className="text-right">
                  <p className="text-sm font-bold text-slate-900">{formatCurrency(0)}</p>
                  <p className="text-[10px] text-slate-400">0 contas</p>
                </div>
              </div>
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <div className="h-2.5 w-2.5 rounded-full bg-emerald-500" />
                  <span className="text-sm font-semibold text-slate-600">Pagas</span>
                </div>
                <div className="text-right">
                  <p className="text-sm font-bold text-slate-900">{formatCurrency(0)}</p>
                  <p className="text-[10px] text-slate-400">0 contas</p>
                </div>
              </div>
            </div>
          </div>

          <div className="glass-card premium-card p-10 flex flex-col">
            <div className="space-y-2 mb-8">
              <h3 className="text-sm font-semibold text-slate-500 uppercase tracking-wider">Distribuição por Status</h3>
              <p className="text-xs text-slate-400">Valores das contas a pagar</p>
            </div>
            <div className="flex-1 flex items-center justify-center relative">
              {/* Simplified Donut Chart Representation for UI Matching */}
              <div className="h-64 w-64 rounded-full border-[30px] border-amber-500 flex items-center justify-center">
                <div className="text-center">
                  <span className="text-3xl font-bold text-slate-900">100%</span>
                  <p className="text-[10px] text-slate-400 uppercase font-black">Pendentes</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Inteligência Artificial */}
      {forecast && forecast.insights && (
        <section className="glass-card premium-card p-12 bg-blue-50/80 border-blue-200 flex items-start gap-8">
          <div className="p-4 bg-white rounded-2xl shadow-md border border-blue-200">
            <Bot className="h-8 w-8 text-blue-600 shadow-blue-200" />
          </div>
          <div className="space-y-4 flex-1">
            <div>
              <h2 className="text-xl font-bold text-slate-900 uppercase">Auditoria Inteligente</h2>
              <p className="text-xs text-slate-500 font-medium">Insights da IA para sua saúde financeira</p>
            </div>
            <div className="grid gap-4 md:grid-cols-2">
              {forecast.insights.slice(0, 4).map((insight, idx) => (
                <div key={idx} className="bg-white/50 p-4 rounded-xl border border-blue-100 flex items-center gap-3 backdrop-blur-sm shadow-sm hover:shadow-md transition-shadow">
                  <div className="h-2 w-2 rounded-full bg-blue-500" />
                  <p className="text-xs font-bold text-slate-700 leading-relaxed">{insight}</p>
                </div>
              ))}
            </div>
          </div>
        </section>
      )}
    </div>
  );
}
