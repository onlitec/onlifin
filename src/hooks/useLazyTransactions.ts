import * as React from 'react';
import { getCurrentUser, supabase } from '@/db/client';

interface Transaction {
  id: string;
  date: string;
  description: string;
  amount: number;
  type: 'income' | 'expense';
}

interface UseLazyTransactionsOptions {
  accountId?: string;
  pageSize?: number;
  initialLoad?: number;
}

export function useLazyTransactions({
  accountId,
  pageSize = 50,
  initialLoad = 100
}: UseLazyTransactionsOptions) {
  const [transactions, setTransactions] = React.useState<Transaction[]>([]);
  const [loading, setLoading] = React.useState(false);
  const [hasMore, setHasMore] = React.useState(true);
  const [totalCount, setTotalCount] = React.useState(0);

  const loadTransactions = React.useCallback(async (page = 0) => {
    if (!accountId || loading) return;

    setLoading(true);
    try {
      const user = await getCurrentUser();
      if (!user) return;

      const from = page * pageSize;
      const to = from + pageSize - 1;

      const { data, error, count } = await supabase
        .from('transactions')
        .select('*', { count: 'exact' })
        .eq('account_id', accountId)
        .order('date', { ascending: false })
        .range(from, to);

      if (error) throw error;

      if (count !== null) {
        setTotalCount(count);
        setHasMore(from + pageSize < count);
      }

      if (page === 0) {
        setTransactions(data || []);
      } else {
        setTransactions(prev => [...prev, ...(data || [])]);
      }
    } catch (error) {
      console.error('Erro ao carregar transações:', error);
    } finally {
      setLoading(false);
    }
  }, [accountId, pageSize, loading]);

  const loadMore = React.useCallback(() => {
    const currentPage = Math.floor(transactions.length / pageSize);
    loadTransactions(currentPage);
  }, [transactions.length, pageSize, loadTransactions]);

  const refresh = React.useCallback(() => {
    setTransactions([]);
    setHasMore(true);
    loadTransactions(0);
  }, [loadTransactions]);

  // Carregar transações iniciais quando a conta muda
  React.useEffect(() => {
    if (accountId) {
      loadTransactions(0);
    }
  }, [accountId, loadTransactions]);

  return {
    transactions,
    loading,
    hasMore,
    totalCount,
    loadMore,
    refresh
  };
}
