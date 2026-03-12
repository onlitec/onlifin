import * as React from 'react';
import { supabase } from '@/db/client';
import { 
  transactionsApi, 
  forecastsApi, 
  billsSummaryApi,
  notificationsApi
} from '@/db/api';
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

export function useDashboardDataSafe(month?: number, year?: number) {
  const { companyId, personId } = useFinanceScope();
  const [userId, setUserId] = React.useState<string | null>(null);
  const [data, setData] = React.useState<DashboardData | null>(null);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<Error | null>(null);
  const lastFetchRef = React.useRef<number>(0);

  // Mês e ano alvo
  const targetMonth = month !== undefined ? month : new Date().getMonth();
  const targetYear = year !== undefined ? year : new Date().getFullYear();

  React.useEffect(() => {
    const initUser = async () => {
      try {
        const { data: { user } } = await supabase.auth.getUser();
        if (user) {
          setUserId(user.id);
        }
      } catch (error) {
        console.error('Erro ao obter usuário:', error);
      }
    };
    initUser();
  }, []);

  const fetchData = React.useCallback(async () => {
    if (!userId) return;
    
    setLoading(true);
    setError(null);

    try {
      // Calcular datas baseadas no mês selecionado
      const firstDayOfMonth = new Date(targetYear, targetMonth, 1);
      const lastDayOfMonth = new Date(targetYear, targetMonth + 1, 0);
      const startDate = firstDayOfMonth.toISOString().split('T')[0];
      const endDate = lastDayOfMonth.toISOString().split('T')[0];

      // Buscar todos os dados em paralelo para máxima performance
      const [
        dashboardStats, 
        expenses, 
        monthly, 
        latestForecast, 
        billsSum, 
        alertData
      ] = await Promise.all([
      transactionsApi.getDashboardStats(userId, companyId, personId, targetMonth, targetYear),
      transactionsApi.getCategoryExpenses(userId, startDate, endDate, companyId, personId),
      transactionsApi.getMonthlyData(userId, 6, { companyId, personId }),
      forecastsApi.getLatest(userId, companyId, personId).catch(() => null),
      billsSummaryApi.getBillsSummary(userId, companyId, personId),
      notificationsApi.getUnread(userId).catch(() => [])
      ]);

      const result = {
        stats: dashboardStats,
        categoryExpenses: expenses,
        monthlyData: monthly,
        forecast: latestForecast,
        billsSummary: billsSum,
        alerts: alertData
      };

      setData(result);
    } catch (error) {
      console.error('Erro ao buscar dados do dashboard:', error);
      setError(error instanceof Error ? error : new Error('Unknown error'));
      
      setData({
        stats: null,
        categoryExpenses: [],
        monthlyData: [],
        forecast: null,
        billsSummary: { 
          toPay: { total: 0, count: 0, dueToday: 0, overdue: 0, dueThisWeek: 0 }, 
          toReceive: { total: 0, count: 0, dueToday: 0, dueThisWeek: 0 } 
        },
        alerts: []
      } as DashboardData);
    } finally {
      setLoading(false);
    }
  }, [userId, companyId, personId, targetMonth, targetYear]);

  // useEffect com debounce para evitar chamadas excessivas
  React.useEffect(() => {
    const timeoutId = setTimeout(() => {
      fetchData();
    }, 500); // 500ms de debounce

    return () => clearTimeout(timeoutId);
  }, [fetchData]);

  const refetch = React.useCallback(() => {
    lastFetchRef.current = 0; // Reset para permitir próxima requisição imediata
    fetchData();
  }, [fetchData]);

  return {
    data,
    loading,
    error,
    refetch
  };
}
