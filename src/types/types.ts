export type UserRole = 'user' | 'financeiro' | 'admin';
export type TransactionType = 'income' | 'expense';
export type AIPermissionLevel = 'read_aggregated' | 'read_transactional' | 'read_full';

export interface Profile {
  id: string;
  username: string;
  full_name: string | null;
  role: UserRole;
  created_at: string;
}

export interface Account {
  id: string;
  user_id: string;
  name: string;
  bank: string | null;
  agency: string | null;
  account_number: string | null;
  currency: string;
  balance: number;
  created_at: string;
  updated_at: string;
}

export interface Card {
  id: string;
  user_id: string;
  account_id: string | null;
  name: string;
  card_limit: number;
  closing_day: number | null;
  due_day: number | null;
  created_at: string;
  updated_at: string;
}

export interface Category {
  id: string;
  user_id: string | null;
  name: string;
  type: TransactionType;
  icon: string | null;
  color: string | null;
  created_at: string;
}

export interface Transaction {
  id: string;
  user_id: string;
  account_id: string | null;
  card_id: string | null;
  category_id: string | null;
  type: TransactionType;
  amount: number;
  date: string;
  description: string | null;
  tags: string[] | null;
  is_recurring: boolean;
  recurrence_pattern: string | null;
  is_installment: boolean;
  installment_number: number | null;
  total_installments: number | null;
  parent_transaction_id: string | null;
  is_reconciled: boolean;
  created_at: string;
  updated_at: string;
}

export interface AIConfiguration {
  id: string;
  model_name: string;
  endpoint: string | null;
  permission_level: AIPermissionLevel;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

export interface AIChatLog {
  id: string;
  user_id: string;
  message: string;
  response: string | null;
  data_accessed: Record<string, unknown> | null;
  permission_level: AIPermissionLevel;
  created_at: string;
}

export interface ImportHistory {
  id: string;
  user_id: string;
  filename: string;
  format: string;
  status: string;
  imported_count: number;
  error_message: string | null;
  created_at: string;
}

export interface TransactionWithDetails extends Transaction {
  category?: Category;
  account?: Account;
  card?: Card;
}

export interface DashboardStats {
  totalBalance: number;
  monthlyIncome: number;
  monthlyExpenses: number;
  accountsCount: number;
  cardsCount: number;
}

export interface CategoryExpense {
  category: string;
  amount: number;
  color: string;
}

export interface MonthlyData {
  month: string;
  income: number;
  expenses: number;
}
