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
  tenant_id?: string | null;
  subscription_plan_id?: string | null;
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
  settings?: Record<string, unknown> | null;
  created_at: string;
  updated_at: string | null;
}

export interface Account {
  id: string;
  user_id: string;
  company_id: string | null;
  person_id: string | null;
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
  person_id: string | null;
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
  person_id: string | null;
  account_id: string | null;
  card_id: string | null;
  category_id: string | null;
  type: TransactionType;
  amount: number;
  date: string;
  description: string | null;
  notes?: string | null;
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

// ... (skipping unchanged types)

export type BillStatus = 'pending' | 'paid' | 'overdue' | 'received';
export type NotificationType = 'alert' | 'info' | 'warning' | 'success';
export type NotificationSeverity = 'low' | 'medium' | 'high';
export type DebtStatus = 'PENDENTE' | 'VENCIDO' | 'RENEGOCIADO' | 'PAGO' | 'CANCELADO';
export type DebtInterestType = 'SIMPLES' | 'COMPOSTO';
export type DebtPaymentMethod =
  | 'PIX'
  | 'BOLETO'
  | 'CARTAO_CREDITO'
  | 'CARTAO_DEBITO'
  | 'TRANSFERENCIA'
  | 'DINHEIRO'
  | 'DEBITO_AUTOMATICO'
  | 'OUTRO';
export type DebtAgreementStatus = 'ATIVO' | 'CONCLUIDO' | 'CANCELADO' | 'INADIMPLENTE';
export type DebtAbatementType = 'JUROS' | 'MULTA' | 'AMBOS' | 'VALOR_PRINCIPAL';

export interface Debt {
  id: string;
  user_id: string;
  company_id: string | null;
  person_id: string | null;
  description: string;
  creditor: string;
  original_amount: number;
  current_balance: number;
  interest_rate: number;
  interest_type: DebtInterestType;
  penalty_rate: number;
  due_date: string;
  status: DebtStatus;
  category: string | null;
  notes: string | null;
  total_paid: number;
  total_abated: number;
  created_at: string;
  updated_at: string | null;
}

export interface DebtPayment {
  id: string;
  debt_id: string;
  user_id: string;
  amount: number;
  payment_date: string;
  method: DebtPaymentMethod;
  reference: string | null;
  notes: string | null;
  created_at: string;
}

export interface DebtAgreement {
  id: string;
  debt_id: string;
  user_id: string;
  balance_at_agreement: number;
  agreed_amount: number;
  discount_applied: number;
  installments: number;
  installment_value: number;
  new_interest_rate: number;
  start_date: string;
  end_date: string | null;
  status: DebtAgreementStatus;
  terms: string | null;
  created_at: string;
  updated_at: string | null;
}

export interface DebtAbatement {
  id: string;
  debt_id: string;
  user_id: string;
  abatement_type: DebtAbatementType;
  amount: number;
  reason: string;
  applied_at: string;
}

export interface BillToPay {
  id: string;
  user_id: string;
  company_id: string | null;
  person_id: string | null;
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
  is_installment?: boolean;
  installment_number?: number | null;
  total_installments?: number | null;
  parent_bill_id?: string | null;
  next_occurrence_generated?: boolean;
}

export interface BillToReceive {
  id: string;
  user_id: string;
  company_id: string | null;
  person_id: string | null;
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
  is_installment?: boolean;
  installment_number?: number | null;
  total_installments?: number | null;
  parent_bill_id?: string | null;
  next_occurrence_generated?: boolean;
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

// Forecasts Types

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
  event_key: string;
  type: NotificationType;
  severity: NotificationSeverity | null;
  is_read: boolean;
  related_forecast_id: string | null;
  related_bill_id: string | null;
  related_bill_to_receive_id?: string | null;
  related_transaction_id?: string | null;
  person_id?: string | null;
  metadata?: Record<string, unknown>;
  action_url: string | null;
  updated_at?: string;
  created_at: string;
}

export interface AlertPreferences {
  id: string;
  user_id: string;
  days_before_due: number;
  days_before_overdue: number;
  alert_due_soon: boolean;
  alert_overdue: boolean;
  alert_paid: boolean;
  alert_received: boolean;
  system_critical_notifications: boolean;
  toast_notifications: boolean;
  database_notifications: boolean;
  email_notifications: boolean;
  whatsapp_notifications: boolean;
  push_notifications: boolean;
  quiet_hours_start: string;
  quiet_hours_end: string;
  weekend_notifications: boolean;
  created_at: string;
  updated_at: string;
}

export interface NotificationSettings {
  id: string;
  settings_key: string;
  is_active: boolean;
  toast_enabled: boolean;
  database_enabled: boolean;
  email_enabled: boolean;
  whatsapp_enabled: boolean;
  allow_user_channel_overrides: boolean;
  days_before_due_default: number;
  days_before_overdue_default: number;
  quiet_hours_start_default: string;
  quiet_hours_end_default: string;
  weekend_notifications_default: boolean;
  alert_due_soon_default: boolean;
  alert_overdue_default: boolean;
  alert_paid_default: boolean;
  alert_received_default: boolean;
  system_critical_default: boolean;
  email_from_name: string | null;
  email_from_address: string | null;
  email_test_destination: string | null;
  whatsapp_test_destination: string | null;
  created_at: string;
  updated_at: string;
}

export interface NotificationTemplate {
  id: string;
  event_key: string;
  channel: 'toast' | 'email' | 'whatsapp';
  title_template: string;
  subject_template: string | null;
  body_template: string;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

export interface NotificationDeliveryQueueItem {
  id: string;
  notification_id: string | null;
  user_id: string;
  channel: 'email' | 'whatsapp';
  destination: string;
  subject: string | null;
  content: string;
  template_id: string | null;
  payload: Record<string, unknown>;
  status: 'pending' | 'processing' | 'retrying' | 'sent' | 'failed';
  attempts: number;
  max_attempts: number;
  next_attempt_at: string;
  last_error: string | null;
  provider_response: Record<string, unknown>;
  sent_at: string | null;
  created_at: string;
  updated_at: string;
}

export interface NotificationDelivery {
  id: string;
  queue_id: string | null;
  notification_id: string | null;
  user_id: string | null;
  channel: 'email' | 'whatsapp';
  destination: string;
  provider: string;
  status: 'sent' | 'failed';
  error_message: string | null;
  provider_response: Record<string, unknown>;
  attempted_at: string;
}

export interface NotificationWorkerCommand {
  id: string;
  command: 'process_queue' | 'generate_notifications';
  status: 'pending' | 'processing' | 'completed' | 'failed';
  requested_by: string | null;
  payload: Record<string, unknown>;
  result: Record<string, unknown>;
  error_message: string | null;
  requested_at: string;
  started_at: string | null;
  completed_at: string | null;
  updated_at: string;
}
