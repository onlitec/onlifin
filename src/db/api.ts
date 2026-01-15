import { supabase } from './client';
import type {
  Profile,
  Account,
  Card,
  Category,
  Transaction,
  AIConfiguration,
  AIChatLog,
  ImportHistory,
  TransactionWithDetails,
  DashboardStats,
  CategoryExpense,
  MonthlyData,
  BillToPay,
  BillToReceive,
  FinancialForecast,
  Notification
} from '@/types/types';

export const profilesApi = {
  async getProfile(userId: string): Promise<Profile | null> {
    const { data, error } = await supabase
      .from('profiles')
      .select('*')
      .eq('id', userId)
      .maybeSingle();

    if (error) throw error;
    return data;
  },

  async getAllProfiles(): Promise<Profile[]> {
    const { data, error } = await supabase
      .from('profiles')
      .select('*')
      .order('created_at', { ascending: false });

    if (error) throw error;
    return Array.isArray(data) ? data : [];
  },

  async updateProfile(userId: string, updates: Partial<Profile>): Promise<Profile | null> {
    const { data, error } = await supabase
      .from('profiles')
      .update(updates)
      .eq('id', userId)
      .select()
      .maybeSingle();

    if (error) throw error;
    return data;
  },

  async updateUserRole(userId: string, role: string): Promise<Profile | null> {
    const { data, error } = await supabase
      .from('profiles')
      .update({ role })
      .eq('id', userId)
      .select()
      .maybeSingle();

    if (error) throw error;
    return data;
  },

  async createUser(username: string, password: string, role: string = 'user'): Promise<{ userId: string | null; error: any }> {
    try {
      const email = `${username}@miaoda.com`;

      // Create auth user
      const { data: authData, error: authError } = await supabase.auth.signUp({
        email,
        password
      });

      if (authError) throw authError;
      if (!authData.user) throw new Error('Falha ao criar usuário');

      // Update profile role if not default
      if (role !== 'user') {
        await supabase
          .from('profiles')
          .update({ role })
          .eq('id', authData.user.id);
      }

      return { userId: authData.user.id, error: null };
    } catch (error: any) {
      return { userId: null, error };
    }
  },

  async deleteUser(userId: string): Promise<void> {
    // Delete user profile (cascade will handle related data)
    const { error } = await supabase
      .from('profiles')
      .delete()
      .eq('id', userId);

    if (error) throw error;
  }
};

export const accountsApi = {
  async getAccounts(userId: string): Promise<Account[]> {
    const { data, error } = await supabase
      .from('accounts')
      .select('*')
      .eq('user_id', userId)
      .order('created_at', { ascending: false });

    if (error) throw error;
    return Array.isArray(data) ? data : [];
  },

  async getAccount(id: string): Promise<Account | null> {
    const { data, error } = await supabase
      .from('accounts')
      .select('*')
      .eq('id', id)
      .maybeSingle();

    if (error) throw error;
    return data;
  },

  async createAccount(account: Omit<Account, 'id' | 'created_at' | 'updated_at'>): Promise<Account | null> {
    const { data, error } = await supabase
      .from('accounts')
      .insert(account)
      .select()
      .maybeSingle();

    if (error) throw error;
    return data;
  },

  async updateAccount(id: string, updates: Partial<Account>): Promise<Account | null> {
    const { data, error } = await supabase
      .from('accounts')
      .update({ ...updates, updated_at: new Date().toISOString() })
      .eq('id', id)
      .select()
      .maybeSingle();

    if (error) throw error;
    return data;
  },

  async deleteAccount(id: string): Promise<void> {
    const { error } = await supabase
      .from('accounts')
      .delete()
      .eq('id', id);

    if (error) throw error;
  },

  async recalculateAccountBalance(accountId: string): Promise<number> {
    const { data, error } = await supabase.rpc('recalculate_account_balance', {
      account_uuid: accountId
    });

    if (error) throw error;
    return data || 0;
  },

  async recalculateAllAccountBalances(userId: string): Promise<Array<{ account_id: string; old_balance: number; new_balance: number }>> {
    const { data, error } = await supabase.rpc('recalculate_all_account_balances', {
      user_uuid: userId
    });

    if (error) throw error;
    return Array.isArray(data) ? data : [];
  }
};

export const cardsApi = {
  async getCards(userId: string): Promise<Card[]> {
    const { data, error } = await supabase
      .from('cards')
      .select('*')
      .eq('user_id', userId)
      .order('created_at', { ascending: false });

    if (error) throw error;
    return Array.isArray(data) ? data : [];
  },

  async getCard(id: string): Promise<Card | null> {
    const { data, error } = await supabase
      .from('cards')
      .select('*')
      .eq('id', id)
      .maybeSingle();

    if (error) throw error;
    return data;
  },

  async createCard(card: Omit<Card, 'id' | 'created_at' | 'updated_at'>): Promise<Card | null> {
    const { data, error } = await supabase
      .from('cards')
      .insert(card)
      .select()
      .maybeSingle();

    if (error) throw error;
    return data;
  },

  async updateCard(id: string, updates: Partial<Card>): Promise<Card | null> {
    const { data, error } = await supabase
      .from('cards')
      .update({ ...updates, updated_at: new Date().toISOString() })
      .eq('id', id)
      .select()
      .maybeSingle();

    if (error) throw error;
    return data;
  },

  async deleteCard(id: string): Promise<void> {
    const { error } = await supabase
      .from('cards')
      .delete()
      .eq('id', id);

    if (error) throw error;
  }
};

export const categoriesApi = {
  async getCategories(): Promise<Category[]> {
    const { data, error } = await supabase
      .from('categories')
      .select('*')
      .order('name', { ascending: true });

    if (error) throw error;
    return Array.isArray(data) ? data : [];
  },

  async getCategory(id: string): Promise<Category | null> {
    const { data, error } = await supabase
      .from('categories')
      .select('*')
      .eq('id', id)
      .maybeSingle();

    if (error) throw error;
    return data;
  },

  async createCategory(category: Omit<Category, 'id' | 'created_at'>): Promise<Category | null> {
    const { data, error } = await supabase
      .from('categories')
      .insert(category)
      .select()
      .maybeSingle();

    if (error) throw error;
    return data;
  },

  async updateCategory(id: string, updates: Partial<Category>): Promise<Category | null> {
    const { data, error } = await supabase
      .from('categories')
      .update(updates)
      .eq('id', id)
      .select()
      .maybeSingle();

    if (error) throw error;
    return data;
  },

  async deleteCategory(id: string): Promise<void> {
    const { error } = await supabase
      .from('categories')
      .delete()
      .eq('id', id);

    if (error) throw error;
  }
};

export const transactionsApi = {
  async getTransactions(userId: string, filters?: {
    startDate?: string;
    endDate?: string;
    type?: string;
    categoryId?: string;
  }): Promise<TransactionWithDetails[]> {
    let query = supabase
      .from('transactions')
      .select(`
        *,
        category:categories(*),
        account:accounts!account_id(*),
        card:cards(*)
      `)
      .eq('user_id', userId);

    if (filters?.startDate) {
      query = query.gte('date', filters.startDate);
    }
    if (filters?.endDate) {
      query = query.lte('date', filters.endDate);
    }
    if (filters?.type) {
      query = query.eq('type', filters.type);
    }
    if (filters?.categoryId) {
      query = query.eq('category_id', filters.categoryId);
    }

    const { data, error } = await query.order('date', { ascending: false });

    if (error) throw error;
    return Array.isArray(data) ? data : [];
  },

  async getTransaction(id: string): Promise<TransactionWithDetails | null> {
    const { data, error } = await supabase
      .from('transactions')
      .select(`
        *,
        category:categories(*),
        account:accounts!account_id(*),
        card:cards(*)
      `)
      .eq('id', id)
      .maybeSingle();

    if (error) throw error;
    return data;
  },

  async createTransaction(transaction: Omit<Transaction, 'id' | 'created_at' | 'updated_at'>): Promise<Transaction | null> {
    const { data, error } = await supabase
      .from('transactions')
      .insert(transaction)
      .select()
      .maybeSingle();

    if (error) throw error;
    return data;
  },

  async updateTransaction(id: string, updates: Partial<Transaction>): Promise<Transaction | null> {
    const { data, error } = await supabase
      .from('transactions')
      .update({ ...updates, updated_at: new Date().toISOString() })
      .eq('id', id)
      .select()
      .maybeSingle();

    if (error) throw error;
    return data;
  },

  async deleteTransaction(id: string): Promise<void> {
    const { error } = await supabase
      .from('transactions')
      .delete()
      .eq('id', id);

    if (error) throw error;
  },

  async getDashboardStats(userId: string): Promise<DashboardStats> {
    const now = new Date();
    const firstDayOfMonth = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
    const lastDayOfMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().split('T')[0];

    const [accountsData, cardsData, transactionsData] = await Promise.all([
      supabase.from('accounts').select('balance').eq('user_id', userId),
      supabase.from('cards').select('id').eq('user_id', userId),
      supabase.from('transactions')
        .select('type, amount, is_transfer')
        .eq('user_id', userId)
        .gte('date', firstDayOfMonth)
        .lte('date', lastDayOfMonth)
    ]);

    const totalBalance = (accountsData.data || []).reduce((sum, acc) => sum + Number(acc.balance), 0);

    // Filtrar transferências internas - não devem contar como receita/despesa real
    const monthlyIncome = (transactionsData.data || [])
      .filter(t => t.type === 'income' && !t.is_transfer)
      .reduce((sum, t) => sum + Number(t.amount), 0);
    const monthlyExpenses = (transactionsData.data || [])
      .filter(t => t.type === 'expense' && !t.is_transfer)
      .reduce((sum, t) => sum + Number(t.amount), 0);

    return {
      totalBalance,
      monthlyIncome,
      monthlyExpenses,
      accountsCount: accountsData.data?.length || 0,
      cardsCount: cardsData.data?.length || 0
    };
  },

  async getCategoryExpenses(userId: string, startDate: string, endDate: string): Promise<CategoryExpense[]> {
    const { data, error } = await supabase
      .from('transactions')
      .select(`
        amount,
        category:categories(name, color)
      `)
      .eq('user_id', userId)
      .eq('type', 'expense')
      .gte('date', startDate)
      .lte('date', endDate);

    if (error) throw error;

    const categoryMap = new Map<string, { amount: number; color: string }>();

    (data || []).forEach((item: any) => {
      const categoryName = item.category?.name || 'Sem Categoria';
      const categoryColor = item.category?.color || '#999999';
      const current = categoryMap.get(categoryName) || { amount: 0, color: categoryColor };
      categoryMap.set(categoryName, {
        amount: current.amount + Number(item.amount),
        color: categoryColor
      });
    });

    return Array.from(categoryMap.entries()).map(([category, data]) => ({
      category,
      amount: data.amount,
      color: data.color
    }));
  },

  async getMonthlyData(userId: string, months: number = 6): Promise<MonthlyData[]> {
    const result: MonthlyData[] = [];
    const now = new Date();

    for (let i = months - 1; i >= 0; i--) {
      const date = new Date(now.getFullYear(), now.getMonth() - i, 1);
      const firstDay = new Date(date.getFullYear(), date.getMonth(), 1).toISOString().split('T')[0];
      const lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0).toISOString().split('T')[0];

      const { data } = await supabase
        .from('transactions')
        .select('type, amount, is_transfer')
        .eq('user_id', userId)
        .gte('date', firstDay)
        .lte('date', lastDay);

      // Filtrar transferências internas
      const income = (data || [])
        .filter(t => t.type === 'income' && !t.is_transfer)
        .reduce((sum, t) => sum + Number(t.amount), 0);

      const expenses = (data || [])
        .filter(t => t.type === 'expense' && !t.is_transfer)
        .reduce((sum, t) => sum + Number(t.amount), 0);

      result.push({
        month: date.toLocaleDateString('pt-BR', { month: 'short', year: 'numeric' }),
        income,
        expenses
      });
    }

    return result;
  },

  async createTransfer(params: {
    userId: string;
    sourceAccountId: string;
    destinationAccountId: string;
    amount: number;
    date: string;
    description: string;
  }): Promise<{ success: boolean; sourceTransactionId: string; destinationTransactionId: string }> {
    const { data, error } = await supabase.rpc('create_transfer', {
      p_user_id: params.userId,
      p_source_account_id: params.sourceAccountId,
      p_destination_account_id: params.destinationAccountId,
      p_amount: params.amount,
      p_date: params.date,
      p_description: params.description
    });

    if (error) throw error;

    return {
      success: data.success,
      sourceTransactionId: data.source_transaction_id,
      destinationTransactionId: data.destination_transaction_id
    };
  },

  async getTransferPair(transactionId: string): Promise<{
    sourceTransactionId: string;
    destinationTransactionId: string;
    sourceAccountId: string;
    destinationAccountId: string;
    amount: number;
    date: string;
    description: string;
  } | null> {
    const { data, error } = await supabase.rpc('get_transfer_pair', {
      p_transaction_id: transactionId
    });

    if (error) throw error;
    return data && data.length > 0 ? data[0] : null;
  }
};

export const aiConfigApi = {
  async getActiveConfig(): Promise<AIConfiguration | null> {
    const { data, error } = await supabase
      .from('ai_configurations')
      .select('*')
      .eq('is_active', true)
      .order('created_at', { ascending: false })
      .limit(1)
      .maybeSingle();

    if (error) throw error;
    return data;
  },

  async getAllConfigs(): Promise<AIConfiguration[]> {
    const { data, error } = await supabase
      .from('ai_configurations')
      .select('*')
      .order('created_at', { ascending: false });

    if (error) throw error;
    return Array.isArray(data) ? data : [];
  },

  async createConfig(config: Omit<AIConfiguration, 'id' | 'created_at' | 'updated_at'>): Promise<AIConfiguration | null> {
    const { data, error } = await supabase
      .from('ai_configurations')
      .insert(config)
      .select()
      .maybeSingle();

    if (error) throw error;
    return data;
  },

  async updateConfig(id: string, updates: Partial<AIConfiguration>): Promise<AIConfiguration | null> {
    const { data, error } = await supabase
      .from('ai_configurations')
      .update({ ...updates, updated_at: new Date().toISOString() })
      .eq('id', id)
      .select()
      .maybeSingle();

    if (error) throw error;
    return data;
  },

  async deleteConfig(id: string): Promise<void> {
    const { error } = await supabase
      .from('ai_configurations')
      .delete()
      .eq('id', id);

    if (error) throw error;
  }
};

export const aiChatLogsApi = {
  async getChatLogs(userId?: string, limit: number = 50): Promise<AIChatLog[]> {
    let query = supabase
      .from('ai_chat_logs')
      .select('*')
      .order('created_at', { ascending: false })
      .limit(limit);

    if (userId) {
      query = query.eq('user_id', userId);
    }

    const { data, error } = await query;

    if (error) throw error;
    return Array.isArray(data) ? data : [];
  },

  async getAllChatLogs(limit: number = 100): Promise<AIChatLog[]> {
    const { data, error } = await supabase
      .from('ai_chat_logs')
      .select('*')
      .order('created_at', { ascending: false })
      .limit(limit);

    if (error) throw error;
    return Array.isArray(data) ? data : [];
  },

  async createChatLog(log: Omit<AIChatLog, 'id' | 'created_at'>): Promise<AIChatLog | null> {
    const { data, error } = await supabase
      .from('ai_chat_logs')
      .insert(log)
      .select()
      .maybeSingle();

    if (error) throw error;
    return data;
  },

  async deleteChatLog(id: string): Promise<void> {
    const { error } = await supabase
      .from('ai_chat_logs')
      .delete()
      .eq('id', id);

    if (error) throw error;
  },

  async deleteChatLogs(userId: string): Promise<void> {
    const { error } = await supabase
      .from('ai_chat_logs')
      .delete()
      .eq('user_id', userId);

    if (error) throw error;
  }
};

export const importHistoryApi = {
  async getImportHistory(userId: string): Promise<ImportHistory[]> {
    const { data, error } = await supabase
      .from('import_history')
      .select('*')
      .eq('user_id', userId)
      .order('created_at', { ascending: false });

    if (error) throw error;
    return Array.isArray(data) ? data : [];
  },

  async createImportHistory(history: Omit<ImportHistory, 'id' | 'created_at'>): Promise<ImportHistory | null> {
    const { data, error } = await supabase
      .from('import_history')
      .insert(history)
      .select()
      .maybeSingle();

    if (error) throw error;
    return data;
  }
};

// Bills To Pay API
export const billsToPayApi = {
  async getAll(userId: string): Promise<BillToPay[]> {
    const { data, error } = await supabase
      .from('bills_to_pay')
      .select('*')
      .eq('user_id', userId)
      .order('due_date', { ascending: true });

    if (error) throw error;
    return Array.isArray(data) ? data : [];
  },

  async getById(id: string): Promise<BillToPay | null> {
    const { data, error } = await supabase
      .from('bills_to_pay')
      .select('*')
      .eq('id', id)
      .maybeSingle();

    if (error) throw error;
    return data;
  },

  async getPending(userId: string): Promise<BillToPay[]> {
    const { data, error } = await supabase
      .from('bills_to_pay')
      .select('*')
      .eq('user_id', userId)
      .eq('status', 'pending')
      .order('due_date', { ascending: true });

    if (error) throw error;
    return Array.isArray(data) ? data : [];
  },

  async getOverdue(userId: string): Promise<BillToPay[]> {
    const { data, error } = await supabase
      .from('bills_to_pay')
      .select('*')
      .eq('user_id', userId)
      .eq('status', 'overdue')
      .order('due_date', { ascending: true });

    if (error) throw error;
    return Array.isArray(data) ? data : [];
  },

  async create(bill: Omit<BillToPay, 'id' | 'created_at' | 'updated_at'>): Promise<BillToPay | null> {
    const { data, error } = await supabase
      .from('bills_to_pay')
      .insert(bill)
      .select()
      .maybeSingle();

    if (error) throw error;
    return data;
  },

  async update(id: string, updates: Partial<BillToPay>): Promise<BillToPay | null> {
    const { data, error } = await supabase
      .from('bills_to_pay')
      .update({ ...updates, updated_at: new Date().toISOString() })
      .eq('id', id)
      .select()
      .maybeSingle();

    if (error) throw error;
    return data;
  },

  async markAsPaid(id: string, paidDate: string): Promise<BillToPay | null> {
    const { data, error } = await supabase
      .from('bills_to_pay')
      .update({
        status: 'paid',
        paid_date: paidDate,
        updated_at: new Date().toISOString()
      })
      .eq('id', id)
      .select()
      .maybeSingle();

    if (error) throw error;
    return data;
  },

  async delete(id: string): Promise<void> {
    const { error } = await supabase
      .from('bills_to_pay')
      .delete()
      .eq('id', id);

    if (error) throw error;
  }
};

// Bills To Receive API
export const billsToReceiveApi = {
  async getAll(userId: string): Promise<BillToReceive[]> {
    const { data, error } = await supabase
      .from('bills_to_receive')
      .select('*')
      .eq('user_id', userId)
      .order('due_date', { ascending: true });

    if (error) throw error;
    return Array.isArray(data) ? data : [];
  },

  async getById(id: string): Promise<BillToReceive | null> {
    const { data, error } = await supabase
      .from('bills_to_receive')
      .select('*')
      .eq('id', id)
      .maybeSingle();

    if (error) throw error;
    return data;
  },

  async getPending(userId: string): Promise<BillToReceive[]> {
    const { data, error } = await supabase
      .from('bills_to_receive')
      .select('*')
      .eq('user_id', userId)
      .eq('status', 'pending')
      .order('due_date', { ascending: true });

    if (error) throw error;
    return Array.isArray(data) ? data : [];
  },

  async getOverdue(userId: string): Promise<BillToReceive[]> {
    const { data, error } = await supabase
      .from('bills_to_receive')
      .select('*')
      .eq('user_id', userId)
      .eq('status', 'overdue')
      .order('due_date', { ascending: true });

    if (error) throw error;
    return Array.isArray(data) ? data : [];
  },

  async create(bill: Omit<BillToReceive, 'id' | 'created_at' | 'updated_at'>): Promise<BillToReceive | null> {
    const { data, error } = await supabase
      .from('bills_to_receive')
      .insert(bill)
      .select()
      .maybeSingle();

    if (error) throw error;
    return data;
  },

  async update(id: string, updates: Partial<BillToReceive>): Promise<BillToReceive | null> {
    const { data, error } = await supabase
      .from('bills_to_receive')
      .update({ ...updates, updated_at: new Date().toISOString() })
      .eq('id', id)
      .select()
      .maybeSingle();

    if (error) throw error;
    return data;
  },

  async markAsReceived(id: string, receivedDate: string): Promise<BillToReceive | null> {
    const { data, error } = await supabase
      .from('bills_to_receive')
      .update({
        status: 'received',
        received_date: receivedDate,
        updated_at: new Date().toISOString()
      })
      .eq('id', id)
      .select()
      .maybeSingle();

    if (error) throw error;
    return data;
  },

  async delete(id: string): Promise<void> {
    const { error } = await supabase
      .from('bills_to_receive')
      .delete()
      .eq('id', id);

    if (error) throw error;
  }
};

// Financial Forecasts API
export const forecastsApi = {
  async getLatest(userId: string): Promise<FinancialForecast | null> {
    const { data, error } = await supabase
      .from('financial_forecasts')
      .select('*')
      .eq('user_id', userId)
      .order('calculation_date', { ascending: false })
      .limit(1)
      .maybeSingle();

    if (error) throw error;
    return data;
  },

  async getAll(userId: string): Promise<FinancialForecast[]> {
    const { data, error } = await supabase
      .from('financial_forecasts')
      .select('*')
      .eq('user_id', userId)
      .order('calculation_date', { ascending: false });

    if (error) throw error;
    return Array.isArray(data) ? data : [];
  },

  async getById(id: string): Promise<FinancialForecast | null> {
    const { data, error } = await supabase
      .from('financial_forecasts')
      .select('*')
      .eq('id', id)
      .maybeSingle();

    if (error) throw error;
    return data;
  },

  async triggerGeneration(userId: string): Promise<void> {
    // Chama a Edge Function para gerar previsão
    const { error } = await supabase.functions.invoke('financial-forecast', {
      body: { user_id: userId }
    });

    if (error) throw error;
  }
};

// Notifications API
export const notificationsApi = {
  async getAll(userId: string): Promise<Notification[]> {
    const { data, error } = await supabase
      .from('notifications')
      .select('*')
      .eq('user_id', userId)
      .order('created_at', { ascending: false });

    if (error) throw error;
    return Array.isArray(data) ? data : [];
  },

  async getUnread(userId: string): Promise<Notification[]> {
    const { data, error } = await supabase
      .from('notifications')
      .select('*')
      .eq('user_id', userId)
      .eq('is_read', false)
      .order('created_at', { ascending: false });

    if (error) throw error;
    return Array.isArray(data) ? data : [];
  },

  async getUnreadCount(userId: string): Promise<number> {
    const { count, error } = await supabase
      .from('notifications')
      .select('*', { count: 'exact', head: true })
      .eq('user_id', userId)
      .eq('is_read', false);

    if (error) throw error;
    return count || 0;
  },

  async markAsRead(id: string): Promise<Notification | null> {
    const { data, error } = await supabase
      .from('notifications')
      .update({ is_read: true })
      .eq('id', id)
      .select()
      .maybeSingle();

    if (error) throw error;
    return data;
  },

  async markAllAsRead(userId: string): Promise<void> {
    const { error } = await supabase
      .from('notifications')
      .update({ is_read: true })
      .eq('user_id', userId)
      .eq('is_read', false);

    if (error) throw error;
  },

  async delete(id: string): Promise<void> {
    const { error } = await supabase
      .from('notifications')
      .delete()
      .eq('id', id);

    if (error) throw error;
  }
};
