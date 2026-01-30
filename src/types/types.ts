export type UserRole = 'user' | 'financeiro' | 'admin';
export type TransactionType = 'income' | 'expense' | 'transfer';
export type CategoryType = 'income' | 'expense';
export type AIPermissionLevel = 'read_aggregated' | 'read_transactional' | 'read_full';
export type UserStatus = 'active' | 'suspended' | 'inactive';

export interface Profile {
  id: string;
  username: string;
  full_name: string | null;
  email: string | null;
  phone: string | null;
  whatsapp: string | null;
  document: string | null; // CPF/CNPJ
  birth_date: string | null;
  address: string | null;
  city: string | null;
  state: string | null;
  postal_code: string | null;
  avatar_url: string | null;
  role: UserRole;
  status: UserStatus;
  force_password_change?: boolean;
  last_login_at: string | null;
  admin_notes: string | null;
  created_at: string;
  updated_at: string | null;
}

export interface Account {
  id: string;
  user_id: string;
  company_id: string | null;
  name: string;
  bank: string | null;
  agency: string | null;
  account_number: string | null;
  currency: string;
  balance: number;
  initial_balance: number;
  icon: string | null;
  created_at: string;
  updated_at: string;
}

export interface Card {
  id: string;
  user_id: string;
  company_id: string | null;
  account_id: string | null;
  name: string;
  card_limit: number;
  closing_day: number | null;
  due_day: number | null;
  icon: string | null;
  brand: string | null;
  created_at: string;
  updated_at: string;
}

export interface Category {
  id: string;
  user_id: string | null;
  company_id: string | null;
  name: string;
  type: CategoryType;
  icon: string | null;
  color: string | null;
  created_at: string;
}

export interface Transaction {
  id: string;
  user_id: string;
  company_id: string | null;
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
  is_transfer: boolean;
  transfer_destination_account_id: string | null;
  created_at: string;
  updated_at: string;
}

export interface AIConfiguration {
  id: string;
  model_name: string;
  endpoint: string | null;
  permission_level: AIPermissionLevel;
  can_write_transactions: boolean;
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
  action_type: string;
  created_transaction_id: string | null;
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

// Bills and Forecasts Types
export type BillStatus = 'pending' | 'paid' | 'overdue' | 'received';
export type NotificationType = 'alert' | 'info' | 'warning' | 'success';
export type NotificationSeverity = 'low' | 'medium' | 'high';

export interface BillToPay {
  id: string;
  user_id: string;
  company_id: string | null;
  description: string;
  amount: number;
  due_date: string;
  category_id: string | null;
  status: BillStatus;
  is_recurring: boolean;
  recurrence_pattern: string | null;
  account_id: string | null;
  paid_date: string | null;
  transaction_id: string | null;
  notes: string | null;
  created_at: string;
  updated_at: string;
}

export interface BillToReceive {
  id: string;
  user_id: string;
  company_id: string | null;
  description: string;
  amount: number;
  due_date: string;
  category_id: string | null;
  status: BillStatus;
  is_recurring: boolean;
  recurrence_pattern: string | null;
  account_id: string | null;
  received_date: string | null;
  transaction_id: string | null;
  notes: string | null;
  created_at: string;
  updated_at: string;
}

export interface ForecastAlert {
  tipo: string;
  descricao: string;
  gravidade: 'baixa' | 'media' | 'alta';
}

export interface FinancialForecast {
  id: string;
  user_id: string;
  calculation_date: string;
  initial_balance: number;
  forecast_daily: Record<string, number>;
  forecast_weekly: Record<string, number>;
  forecast_monthly: Record<string, number>;
  insights: string[];
  alerts: ForecastAlert[];
  risk_negative: boolean;
  risk_date: string | null;
  spending_patterns: Record<string, unknown>;
  created_at: string;
}

export interface Notification {
  id: string;
  user_id: string;
  title: string;
  message: string;
  type: NotificationType;
  severity: NotificationSeverity | null;
  is_read: boolean;
  related_forecast_id: string | null;
  related_bill_id: string | null;
  action_url: string | null;
  created_at: string;
}
