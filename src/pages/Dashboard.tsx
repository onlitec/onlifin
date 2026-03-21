import * as React from 'react';
import { useAuth } from 'miaoda-auth-react';
import { supabase } from '@/db/client';
import { transactionsApi, billsToReceiveApi, accountsApi, cardsApi } from '@/db/api';
import { Skeleton } from '@/components/ui/skeleton';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { DashboardStats, CategoryExpense, MonthlyData } from '@/types/types';
import { useNavigate } from 'react-router-dom';
import { Building2, CreditCard, Plus, Wallet } from 'lucide-react';

import { BalanceCards } from '@/components/dashboard/BalanceCards';
import { InsightsCards } from '@/components/dashboard/InsightsCards';
import { useFinanceScope } from '@/hooks/useFinanceScope';

const CategoryBreakdown = React.lazy(() =>
  import('@/components/dashboard/CategoryBreakdown').then((module) => ({ default: module.CategoryBreakdown }))
);
const SpendingChart = React.lazy(() =>
  import('@/components/dashboard/SpendingChart').then((module) => ({ default: module.SpendingChart }))
);

interface EnhancedStats extends DashboardStats {
  savingsRate: number;
  averageDailyExpense: number;
  projectedMonthEnd: number;
}

function buildEnhancedStats(baseStats: DashboardStats, year: number, month: number): EnhancedStats {
  const daysInMonth = new Date(year, month + 1, 0).getDate();
  const now = new Date();
  const isCurrentMonth = year === now.getFullYear() && month === now.getMonth();
  const currentDay = isCurrentMonth ? now.getDate() : daysInMonth;
  const averageDailyExpense = currentDay > 0 ? baseStats.monthlyExpenses / currentDay : 0;
  const projectedMonthEnd = isCurrentMonth
    ? averageDailyExpense * daysInMonth
    : baseStats.monthlyExpenses;
  const savingsRate = baseStats.monthlyIncome > 0
    ? ((baseStats.monthlyIncome - baseStats.monthlyExpenses) / baseStats.monthlyIncome) * 100
    : 0;

  return {
    ...baseStats,
    savingsRate,
    averageDailyExpense,
    projectedMonthEnd,
  };
}

function AnalyticsCardSkeleton() {
  return <Skeleton className="h-[360px] rounded-[1.5rem]" />;
}

export default function Dashboard() {
  const navigate = useNavigate();
  const { user } = useAuth();
  const { companyId, personId, isPJ } = useFinanceScope();
  const [stats, setStats] = React.useState<DashboardStats | null>(null);
  const [enhancedStats, setEnhancedStats] = React.useState<EnhancedStats | null>(null);
  const [categoryExpenses, setCategoryExpenses] = React.useState<CategoryExpense[]>([]);
  const [monthlyData, setMonthlyData] = React.useState<MonthlyData[]>([]);
  const [pendingToReceive, setPendingToReceive] = React.useState(0);
  const [setupStatus, setSetupStatus] = React.useState({
    accountsCount: 0,
    cardsCount: 0,
    transactionsCount: 0,
  });
  const [isLoading, setIsLoading] = React.useState(true);
  const [isLoadingAnalytics, setIsLoadingAnalytics] = React.useState(true);
  const [selectedMonth, setSelectedMonth] = React.useState(new Date().getMonth().toString());
  const [selectedYear, setSelectedYear] = React.useState(new Date().getFullYear().toString());
  const requestIdRef = React.useRef(0);

  const loadDashboardData = React.useCallback(async () => {
    if (!user?.id) {
      setIsLoading(false);
      setIsLoadingAnalytics(false);
      return;
    }

    const requestId = ++requestIdRef.current;

    try {
      setIsLoading(true);
      setIsLoadingAnalytics(true);

      const year = parseInt(selectedYear);
      const month = parseInt(selectedMonth);
      const firstDayOfMonth = new Date(year, month, 1).toISOString().split('T')[0];
      const lastDayOfMonth = new Date(year, month + 1, 0).toISOString().split('T')[0];

      let transactionsCountQuery = supabase
        .from('transactions')
        .select('id', { count: 'exact', head: true })
        .eq('user_id', user.id);

      if (companyId !== undefined) {
        transactionsCountQuery = companyId === null
          ? transactionsCountQuery.is('company_id', null)
          : transactionsCountQuery.eq('company_id', companyId);
      }

      if (personId !== undefined) {
        transactionsCountQuery = personId === null
          ? transactionsCountQuery.is('person_id', null)
          : transactionsCountQuery.eq('person_id', personId);
      }

      const [dashboardStats, accountList, cardList, transactionsCountResult] = await Promise.all([
        transactionsApi.getDashboardStats(user.id, companyId, personId, month, year),
        accountsApi.getAccounts(user.id, companyId, personId),
        cardsApi.getCards(user.id, companyId, personId),
        transactionsCountQuery,
      ]);

      if (requestId !== requestIdRef.current) {
        return;
      }

      setStats(dashboardStats);
      setEnhancedStats(buildEnhancedStats(dashboardStats, year, month));
      setSetupStatus({
        accountsCount: accountList.length,
        cardsCount: cardList.length,
        transactionsCount: transactionsCountResult.count || 0,
      });
      setIsLoading(false);

      const [expenses, monthly, pendingBills] = await Promise.all([
        transactionsApi.getCategoryExpenses(user.id, firstDayOfMonth, lastDayOfMonth, companyId, personId),
        transactionsApi.getMonthlyData(user.id, 6, { companyId, personId, month, year }),
        billsToReceiveApi.getPending(user.id, companyId, personId).catch(() => []),
      ]);

      if (requestId !== requestIdRef.current) {
        return;
      }

      setCategoryExpenses(expenses);
      setMonthlyData(monthly);
      setPendingToReceive(pendingBills.reduce((sum, bill) => sum + bill.amount, 0));
    } catch (error) {
      console.error('Erro ao carregar dados do dashboard:', error);
    } finally {
      if (requestId === requestIdRef.current) {
        setIsLoading(false);
        setIsLoadingAnalytics(false);
      }
    }
  }, [companyId, personId, selectedMonth, selectedYear, user?.id]);

  React.useEffect(() => {
    void loadDashboardData();
  }, [loadDashboardData]);

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
  const prefix = isPJ && companyId ? `/pj/${companyId}` : '/pf';
  const onboardingSteps = [
    {
      key: 'account',
      done: setupStatus.accountsCount > 0,
      title: isPJ ? 'Cadastrar primeira conta PJ' : 'Cadastrar primeira conta',
      description: isPJ
        ? 'Crie a conta bancária principal da empresa para ativar o ambiente corporativo.'
        : 'Crie sua conta principal para começar a organizar o fluxo financeiro.',
      actionLabel: 'Criar Conta',
      onClick: () => navigate(`${prefix}/accounts?onboarding=1`),
      icon: Wallet,
    },
    {
      key: 'transaction',
      done: setupStatus.transactionsCount > 0,
      title: 'Registrar primeira transação',
      description: 'Lance uma receita ou despesa para iniciar o histórico e alimentar os gráficos.',
      actionLabel: 'Nova Transação',
      onClick: () => navigate(`${prefix}/transactions?onboarding=1`),
      icon: Plus,
      disabled: setupStatus.accountsCount === 0,
    },
    {
      key: 'card',
      done: setupStatus.cardsCount > 0,
      title: 'Cadastrar cartão',
      description: 'Etapa opcional para acompanhar limites, vencimentos e gastos no crédito.',
      actionLabel: 'Novo Cartão',
      onClick: () => navigate(`${prefix}/cards?onboarding=1`),
      icon: CreditCard,
      optional: true,
      disabled: setupStatus.accountsCount === 0,
    },
  ];
  const shouldShowOnboarding = onboardingSteps.some((step) => !step.done);

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

      {shouldShowOnboarding && (
        <section className="bg-white border border-blue-100 rounded-3xl p-5 lg:p-6 shadow-sm space-y-4">
          <div className="flex flex-col gap-1 lg:flex-row lg:items-center lg:justify-between">
            <div>
              <p className="text-[10px] font-black uppercase tracking-[0.2em] text-blue-600">Primeiros Passos</p>
              <h2 className="text-xl font-black tracking-tight text-slate-900">
                {isPJ ? 'Complete o setup da empresa' : 'Complete o setup da conta'}
              </h2>
            </div>
            {isPJ && (
              <Button
                variant="outline"
                className="h-10 rounded-xl font-black text-[10px] uppercase tracking-widest"
                onClick={() => navigate('/companies')}
              >
                <Building2 className="mr-2 h-4 w-4" />
                Gerenciar CNPJs
              </Button>
            )}
          </div>

          <div className="grid gap-4 lg:grid-cols-3">
            {onboardingSteps.map((step) => {
              const Icon = step.icon;

              return (
                <div
                  key={step.key}
                  className={`rounded-2xl border p-4 transition-all ${
                    step.done
                      ? 'border-emerald-100 bg-emerald-50/60'
                      : 'border-slate-200 bg-slate-50/40'
                  }`}
                >
                  <div className="flex items-center justify-between gap-3">
                    <div className={`flex h-10 w-10 items-center justify-center rounded-xl ${step.done ? 'bg-emerald-500 text-white' : 'bg-white border border-slate-200 text-slate-500'}`}>
                      <Icon className="h-5 w-5" />
                    </div>
                    <span className={`text-[10px] font-black uppercase tracking-widest ${step.done ? 'text-emerald-600' : 'text-slate-400'}`}>
                      {step.done ? 'Concluído' : step.optional ? 'Opcional' : 'Pendente'}
                    </span>
                  </div>

                  <div className="mt-4 space-y-1">
                    <h3 className="text-sm font-black text-slate-900">{step.title}</h3>
                    <p className="text-xs text-slate-500">{step.description}</p>
                  </div>

                  <Button
                    className="mt-4 h-10 rounded-xl font-black text-[10px] uppercase tracking-widest"
                    variant={step.done ? 'outline' : 'default'}
                    disabled={step.done || step.disabled}
                    onClick={step.onClick}
                  >
                    {step.actionLabel}
                  </Button>
                </div>
              );
            })}
          </div>
        </section>
      )}

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
          {isLoadingAnalytics ? (
            <AnalyticsCardSkeleton />
          ) : (
            <React.Suspense fallback={<AnalyticsCardSkeleton />}>
              <SpendingChart data={monthlyData} />
            </React.Suspense>
          )}
        </div>
        <div>
          {isLoadingAnalytics ? (
            <AnalyticsCardSkeleton />
          ) : (
            <React.Suspense fallback={<AnalyticsCardSkeleton />}>
              <CategoryBreakdown categories={categoryExpenses} />
            </React.Suspense>
          )}
        </div>
      </div>
    </div>
  );
}
