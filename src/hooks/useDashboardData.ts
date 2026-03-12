import * as React from 'react';
import { supabase } from '@/db/client';
import { 
  transactionsApi, 
  forecastsApi, 
  billsToReceiveApi, 
  billsToPayApi,
  billsSummaryApi,
  notificationsApi
} from '@/db/api';
import { useCachedApi } from './useApiCache';
import { useFinanceScope } from './useFinanceScope';
import type { DashboardStats, CategoryExpense, MonthlyData, FinancialForecast } from '@/types/types';

interface DashboardData {
  stats: DashboardStats | null;
  categoryExpenses: CategoryExpense[];
  monthlyData: MonthlyData[];
  forecast: FinancialForecast | null;
  billsSummary: any;
  alerts: any[];
}

export function useDashboardData() {
  const { companyId, personId } = useFinanceScope();
  const [userId, setUserId] = React.useState<string | null>(null);

  React.useEffect(() => {
    const initUser = async () => {
      const { data: { user } } = await supabase.auth.getUser();
      if (user) {
        setUserId(user.id);
      }
    };
    initUser();
  }, []);

  // Função combinada para buscar todos os dados do dashboard
  const fetchDashboardData = React.useCallback(async (): Promise<DashboardData> => {
    if (!userId) throw new Error('User not authenticated');

    // Calcular datas para o mês atual
    const now = new Date();
    const firstDayOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);
    const lastDayOfMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    const startDate = firstDayOfMonth.toISOString().split('T')[0];
    const endDate = lastDayOfMonth.toISOString().split('T')[0];

    const [
      dashboardStats,
      expenses,
      monthly,
      latestForecast,
      billsSum,
      alertData
    ] = await Promise.all([
      // Combinar múltiplas chamadas em uma única query
      transactionsApi.getDashboardStats(userId, companyId, personId),
      transactionsApi.getCategoryExpenses(userId, startDate, endDate, companyId, personId),
      transactionsApi.getMonthlyData(userId, 6, { companyId, personId }),
      forecastsApi.getLatest(userId, companyId, personId),
      billsSummaryApi.getBillsSummary(userId, companyId, personId),
      notificationsApi.getUnread(userId).catch(() => [])
    ]);

    return {
      stats: dashboardStats,
      categoryExpenses: expenses,
      monthlyData: monthly,
      forecast: latestForecast,
      billsSummary: billsSum,
      alerts: alertData
    };
  }, [userId, companyId, personId]);

  // Cache de 2 minutos para dados do dashboard
  const { data, loading, error, refetch } = useCachedApi(
    `dashboard-${userId}-${companyId}-${personId}`,
    fetchDashboardData,
    2 * 60 * 1000, // 2 minutos TTL
    [userId, companyId, personId]
  );

  // Memoizar dados para evitar re-renders
  const memoizedData = React.useMemo(() => data, [data]);

  return {
    data: memoizedData,
    loading,
    error,
    refetch
  };
}
