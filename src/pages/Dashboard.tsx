import * as React from 'react';
import { supabase } from '@/db/client';
import { transactionsApi, billsToReceiveApi } from '@/db/api';
import { Skeleton } from '@/components/ui/skeleton';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { DashboardStats, CategoryExpense, MonthlyData } from '@/types/types';

import { BalanceCards } from '@/components/dashboard/BalanceCards';
import { CategoryBreakdown } from '@/components/dashboard/CategoryBreakdown';
import { SpendingChart } from '@/components/dashboard/SpendingChart';
import { InsightsCards } from '@/components/dashboard/InsightsCards';
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
  const [pendingToReceive, setPendingToReceive] = React.useState(0);
  const [isLoading, setIsLoading] = React.useState(true);
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

      const [dashboardStats, expenses, monthly] = await Promise.all([
        transactionsApi.getDashboardStats(user.id, companyId, personId, month, year),
        transactionsApi.getCategoryExpenses(user.id, firstDayOfMonth, lastDayOfMonth, companyId, personId),
        transactionsApi.getMonthlyData(user.id, 6, { companyId, personId, month, year })
      ]);

      setStats(dashboardStats);
      setCategoryExpenses(expenses);
      setMonthlyData(monthly);

      try {
        const pendingBills = await billsToReceiveApi.getPending(user.id, companyId, personId);
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

    if (companyId !== undefined) {
      if (companyId === null) {
        transactionsQuery = transactionsQuery.is('company_id', null);
      } else {
        transactionsQuery = transactionsQuery.eq('company_id', companyId);
      }
    }

    if (personId) {
      transactionsQuery = transactionsQuery.eq('person_id', personId);
    } else if (personId === null) {
      transactionsQuery = transactionsQuery.is('person_id', null);
    }

    const { data: transactions } = await transactionsQuery;

    const monthlyIncome = transactions
      ?.filter((t: any) => t.type === 'income' && !t.is_transfer)
      .reduce((sum: number, t: any) => sum + Number(t.amount), 0) || 0;

    const monthlyExpenses = transactions
      ?.filter((t: any) => t.type === 'expense' && !t.is_transfer)
      .reduce((sum: number, t: any) => sum + Number(t.amount), 0) || 0;

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
    <div className="p-4 lg:p-6 space-y-6 max-w-[1600px] mx-auto animate-slide-up bg-slate-50/30 min-h-screen">
      {/* Page Header */}
      <header className="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
          <h1 className="text-xl font-black tracking-[0.05em] text-slate-900 uppercase">
            Painel Financeiro
          </h1>
          <p className="text-[11px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">
            {currentMonthLabel} de {selectedYear}
          </p>
        </div>

        {/* Filters Bar - Refactored for compactness */}
        <div className="flex items-center gap-3">
          <div className="flex items-center gap-2">
            <span className="text-[9px] font-black text-slate-400 uppercase tracking-tighter">Mês</span>
            <Select value={selectedMonth} onValueChange={setSelectedMonth}>
              <SelectTrigger className="w-[110px] bg-white border-slate-200 rounded-lg h-8 text-[11px] font-bold shadow-sm">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {months.map(month => (
                  <SelectItem key={month.value} value={month.value} className="text-xs font-bold">
                    {month.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          <div className="flex items-center gap-2">
            <span className="text-[9px] font-black text-slate-400 uppercase tracking-tighter">Ano</span>
            <Select value={selectedYear} onValueChange={setSelectedYear}>
              <SelectTrigger className="w-[85px] bg-white border-slate-200 rounded-lg h-8 text-[11px] font-bold shadow-sm">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {years.map(year => (
                  <SelectItem key={year.value} value={year.value} className="text-xs font-bold">
                    {year.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          <Button
            size="sm"
            className="bg-blue-600 hover:bg-blue-700 text-white font-black text-[10px] uppercase tracking-widest h-8 px-4 rounded-lg shadow-sm transition-all"
            onClick={() => {
              const now = new Date();
              setSelectedMonth(now.getMonth().toString());
              setSelectedYear(now.getFullYear().toString());
            }}
          >
            Mês Atual
          </Button>
        </div>
      </header>

      {/* Primary Stats */}
      <section className="space-y-4">
        <BalanceCards
          totalBalance={stats?.totalBalance || 0}
          monthlyIncome={enhancedStats?.monthlyIncome || 0}
          monthlyExpenses={enhancedStats?.monthlyExpenses || 0}
          savingsRate={enhancedStats?.savingsRate || 0}
          pendingToReceive={pendingToReceive}
        />
        
        <InsightsCards
          averageDailyExpense={enhancedStats?.averageDailyExpense || 0}
          projectedMonthEnd={enhancedStats?.projectedMonthEnd || 0}
          savingsRate={enhancedStats?.savingsRate || 0}
        />
      </section>

      {/* Main Insights Grid */}
      <div className="grid gap-4 lg:grid-cols-3 pb-8">
        <div className="lg:col-span-2">
          <SpendingChart data={monthlyData} />
        </div>
        <div>
          <CategoryBreakdown categories={categoryExpenses} />
        </div>
      </div>
    </div>
  );
}
