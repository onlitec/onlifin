import { supabase } from './supabase';
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
  MonthlyData
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
        account:accounts(*),
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
        account:accounts(*),
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
        .select('type, amount')
        .eq('user_id', userId)
        .gte('date', firstDayOfMonth)
        .lte('date', lastDayOfMonth)
    ]);

    const totalBalance = (accountsData.data || []).reduce((sum, acc) => sum + Number(acc.balance), 0);
    const monthlyIncome = (transactionsData.data || [])
      .filter(t => t.type === 'income')
      .reduce((sum, t) => sum + Number(t.amount), 0);
    const monthlyExpenses = (transactionsData.data || [])
      .filter(t => t.type === 'expense')
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
        .select('type, amount')
        .eq('user_id', userId)
        .gte('date', firstDay)
        .lte('date', lastDay);

      const income = (data || [])
        .filter(t => t.type === 'income')
        .reduce((sum, t) => sum + Number(t.amount), 0);
      
      const expenses = (data || [])
        .filter(t => t.type === 'expense')
        .reduce((sum, t) => sum + Number(t.amount), 0);

      result.push({
        month: date.toLocaleDateString('pt-BR', { month: 'short', year: 'numeric' }),
        income,
        expenses
      });
    }

    return result;
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
