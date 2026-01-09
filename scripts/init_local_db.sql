-- ====================================================================================
-- Migração Adaptada para PostgreSQL Nativo (sem Supabase)
-- ====================================================================================
-- Este script adapta as migrações do Supabase para PostgreSQL nativo,
-- criando um sistema de autenticação simplificado.
-- ====================================================================================

-- Criar schema auth para compatibilidade
CREATE SCHEMA IF NOT EXISTS auth;

-- Criar tabela de usuários no schema auth (compatibilidade Supabase)
CREATE TABLE IF NOT EXISTS auth.users (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    email text UNIQUE NOT NULL,
    encrypted_password text NOT NULL,
    confirmed_at timestamptz,
    created_at timestamptz DEFAULT now(),
    updated_at timestamptz DEFAULT now()
);

-- Criar função para simular auth.uid() para compatibilidade
-- Em produção real, isso viria de um JWT ou sessão
CREATE OR REPLACE FUNCTION auth.uid()
RETURNS uuid
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
BEGIN
    -- Retorna o user_id da sessão atual (definido via SET LOCAL)
    RETURN NULLIF(current_setting('app.current_user_id', true), '')::uuid;
END;
$$;

-- Criar extensão pgcrypto para encriptação de senhas
CREATE EXTENSION IF NOT EXISTS pgcrypto;

-- Função para registrar usuário
CREATE OR REPLACE FUNCTION auth.register_user(
    p_email text,
    p_password text
)
RETURNS uuid
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
DECLARE
    v_user_id uuid;
BEGIN
    INSERT INTO auth.users (email, encrypted_password, confirmed_at)
    VALUES (p_email, crypt(p_password, gen_salt('bf')), now())
    RETURNING id INTO v_user_id;
    
    RETURN v_user_id;
END;
$$;

-- Função para autenticar usuário
CREATE OR REPLACE FUNCTION auth.authenticate_user(
    p_email text,
    p_password text
)
RETURNS uuid
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
DECLARE
    v_user auth.users%ROWTYPE;
BEGIN
    SELECT * INTO v_user FROM auth.users WHERE email = p_email;
    
    IF v_user.id IS NULL THEN
        RETURN NULL;
    END IF;
    
    IF v_user.encrypted_password = crypt(p_password, v_user.encrypted_password) THEN
        RETURN v_user.id;
    ELSE
        RETURN NULL;
    END IF;
END;
$$;

-- ====================================================================================
-- AGORA CRIAR O SCHEMA DA APLICAÇÃO
-- ====================================================================================

-- Create ENUM types
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'user_role') THEN
        CREATE TYPE user_role AS ENUM ('user', 'financeiro', 'admin');
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'transaction_type') THEN
        CREATE TYPE transaction_type AS ENUM ('income', 'expense');
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'ai_permission_level') THEN
        CREATE TYPE ai_permission_level AS ENUM ('read_aggregated', 'read_transactional', 'read_full');
    END IF;
END$$;

-- Create profiles table
CREATE TABLE IF NOT EXISTS profiles (
  id uuid PRIMARY KEY REFERENCES auth.users(id) ON DELETE CASCADE,
  username text UNIQUE NOT NULL,
  full_name text,
  role user_role DEFAULT 'user'::user_role NOT NULL,
  created_at timestamptz DEFAULT now()
);

-- Create accounts table
CREATE TABLE IF NOT EXISTS accounts (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id uuid REFERENCES profiles(id) ON DELETE CASCADE NOT NULL,
  name text NOT NULL,
  bank text,
  agency text,
  account_number text,
  currency text DEFAULT 'BRL' NOT NULL,
  balance numeric DEFAULT 0 NOT NULL,
  created_at timestamptz DEFAULT now(),
  updated_at timestamptz DEFAULT now()
);

-- Create cards table
CREATE TABLE IF NOT EXISTS cards (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id uuid REFERENCES profiles(id) ON DELETE CASCADE NOT NULL,
  account_id uuid REFERENCES accounts(id) ON DELETE SET NULL,
  name text NOT NULL,
  card_limit numeric NOT NULL,
  available_limit numeric,
  closing_day integer CHECK (closing_day >= 1 AND closing_day <= 31),
  due_day integer CHECK (due_day >= 1 AND due_day <= 31),
  created_at timestamptz DEFAULT now(),
  updated_at timestamptz DEFAULT now()
);

-- Create categories table
CREATE TABLE IF NOT EXISTS categories (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id uuid REFERENCES profiles(id) ON DELETE CASCADE,
  name text NOT NULL,
  type transaction_type NOT NULL,
  icon text,
  color text,
  created_at timestamptz DEFAULT now()
);

-- Create transactions table
CREATE TABLE IF NOT EXISTS transactions (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id uuid REFERENCES profiles(id) ON DELETE CASCADE NOT NULL,
  account_id uuid REFERENCES accounts(id) ON DELETE SET NULL,
  card_id uuid REFERENCES cards(id) ON DELETE SET NULL,
  category_id uuid REFERENCES categories(id) ON DELETE SET NULL,
  type transaction_type NOT NULL,
  amount numeric NOT NULL,
  date date NOT NULL,
  description text,
  tags text[],
  is_recurring boolean DEFAULT false,
  recurrence_pattern text,
  installment_number integer,
  total_installments integer,
  parent_transaction_id uuid REFERENCES transactions(id) ON DELETE CASCADE,
  is_reconciled boolean DEFAULT false,
  is_installment boolean DEFAULT false,
  created_at timestamptz DEFAULT now(),
  updated_at timestamptz DEFAULT now()
);

-- Create ai_configurations table
CREATE TABLE IF NOT EXISTS ai_configurations (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  model_name text NOT NULL,
  endpoint text,
  permission_level ai_permission_level DEFAULT 'read_aggregated'::ai_permission_level NOT NULL,
  can_write_transactions boolean DEFAULT false,
  is_active boolean DEFAULT true,
  created_at timestamptz DEFAULT now(),
  updated_at timestamptz DEFAULT now()
);

-- Create ai_chat_logs table
CREATE TABLE IF NOT EXISTS ai_chat_logs (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id uuid REFERENCES profiles(id) ON DELETE CASCADE NOT NULL,
  message text NOT NULL,
  response text,
  data_accessed jsonb,
  permission_level ai_permission_level NOT NULL,
  action_type text,
  created_transaction_id uuid,
  created_at timestamptz DEFAULT now()
);

-- Create import_history table
CREATE TABLE IF NOT EXISTS import_history (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id uuid REFERENCES profiles(id) ON DELETE CASCADE NOT NULL,
  filename text NOT NULL,
  format text NOT NULL,
  status text NOT NULL,
  imported_count integer DEFAULT 0,
  error_message text,
  created_at timestamptz DEFAULT now()
);

-- Create helper functions
CREATE OR REPLACE FUNCTION is_admin(uid uuid)
RETURNS boolean LANGUAGE sql SECURITY DEFINER AS $$
  SELECT EXISTS (
    SELECT 1 FROM profiles p
    WHERE p.id = uid AND p.role = 'admin'::user_role
  );
$$;

CREATE OR REPLACE FUNCTION is_financeiro_or_admin(uid uuid)
RETURNS boolean LANGUAGE sql SECURITY DEFINER AS $$
  SELECT EXISTS (
    SELECT 1 FROM profiles p
    WHERE p.id = uid AND p.role IN ('financeiro'::user_role, 'admin'::user_role)
  );
$$;

-- Trigger function for new user registration
CREATE OR REPLACE FUNCTION handle_new_user()
RETURNS trigger
LANGUAGE plpgsql
SECURITY DEFINER SET search_path = public
AS $$
DECLARE
  user_count int;
  extracted_username text;
BEGIN
  SELECT COUNT(*) INTO user_count FROM profiles;
  
  -- Extract username from email (before @)
  extracted_username := split_part(NEW.email, '@', 1);
  
  INSERT INTO profiles (id, username, role)
  VALUES (
    NEW.id,
    extracted_username,
    CASE WHEN user_count = 0 THEN 'admin'::user_role ELSE 'user'::user_role END
  );
  RETURN NEW;
END;
$$;

-- Create trigger for user creation
DROP TRIGGER IF EXISTS on_auth_user_created ON auth.users;
CREATE TRIGGER on_auth_user_created
  AFTER INSERT ON auth.users
  FOR EACH ROW
  EXECUTE FUNCTION handle_new_user();

-- Trigger para atualização de saldos
CREATE OR REPLACE FUNCTION update_account_balance_on_transaction()
RETURNS TRIGGER AS $$
DECLARE
    balance_change numeric;
BEGIN
    -- Handle INSERT
    IF TG_OP = 'INSERT' THEN
        IF NEW.account_id IS NOT NULL THEN
            -- Calculate balance change
            IF NEW.type = 'income' THEN
                balance_change := NEW.amount;
            ELSE
                balance_change := -NEW.amount;
            END IF;
            
            -- Update account balance
            UPDATE accounts
            SET balance = balance + balance_change,
                updated_at = NOW()
            WHERE id = NEW.account_id;
        END IF;
        RETURN NEW;
        
    -- Handle UPDATE
    ELSIF TG_OP = 'UPDATE' THEN
        -- Revert old transaction effect
        IF OLD.account_id IS NOT NULL THEN
            IF OLD.type = 'income' THEN
                balance_change := -OLD.amount;
            ELSE
                balance_change := OLD.amount;
            END IF;
            
            UPDATE accounts
            SET balance = balance + balance_change,
                updated_at = NOW()
            WHERE id = OLD.account_id;
        END IF;
        
        -- Apply new transaction effect
        IF NEW.account_id IS NOT NULL THEN
            IF NEW.type = 'income' THEN
                balance_change := NEW.amount;
            ELSE
                balance_change := -NEW.amount;
            END IF;
            
            UPDATE accounts
            SET balance = balance + balance_change,
                updated_at = NOW()
            WHERE id = NEW.account_id;
        END IF;
        RETURN NEW;
        
    -- Handle DELETE
    ELSIF TG_OP = 'DELETE' THEN
        IF OLD.account_id IS NOT NULL THEN
            IF OLD.type = 'income' THEN
                balance_change := -OLD.amount;
            ELSE
                balance_change := OLD.amount;
            END IF;
            
            UPDATE accounts
            SET balance = balance + balance_change,
                updated_at = NOW()
            WHERE id = OLD.account_id;
        END IF;
        RETURN OLD;
    END IF;
    
    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

-- Create trigger for transaction changes
DROP TRIGGER IF EXISTS update_balance_on_transaction ON transactions;
CREATE TRIGGER update_balance_on_transaction
    AFTER INSERT OR UPDATE OR DELETE ON transactions
    FOR EACH ROW
    EXECUTE FUNCTION update_account_balance_on_transaction();

-- Function to recalculate account balance
CREATE OR REPLACE FUNCTION recalculate_account_balance(target_account_id uuid)
RETURNS numeric AS $$
DECLARE
    new_balance numeric;
BEGIN
    SELECT COALESCE(
        SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END),
        0
    ) INTO new_balance
    FROM transactions
    WHERE account_id = target_account_id;
    
    UPDATE accounts
    SET balance = new_balance,
        updated_at = NOW()
    WHERE id = target_account_id;
    
    RETURN new_balance;
END;
$$ LANGUAGE plpgsql;

-- Insert default system categories
INSERT INTO categories (user_id, name, type, icon, color) VALUES
  (NULL, 'Salário', 'income', '💰', '#27AE60'),
  (NULL, 'Freelance', 'income', '💼', '#27AE60'),
  (NULL, 'Investimentos', 'income', '📈', '#27AE60'),
  (NULL, 'Outros Rendimentos', 'income', '💵', '#27AE60'),
  (NULL, 'Alimentação', 'expense', '🍔', '#E74C3C'),
  (NULL, 'Transporte', 'expense', '🚗', '#E74C3C'),
  (NULL, 'Moradia', 'expense', '🏠', '#E74C3C'),
  (NULL, 'Saúde', 'expense', '🏥', '#E74C3C'),
  (NULL, 'Educação', 'expense', '📚', '#E74C3C'),
  (NULL, 'Lazer', 'expense', '🎮', '#E74C3C'),
  (NULL, 'Compras', 'expense', '🛒', '#E74C3C'),
  (NULL, 'Contas', 'expense', '📄', '#E74C3C'),
  (NULL, 'Outros Gastos', 'expense', '💸', '#E74C3C')
ON CONFLICT DO NOTHING;

-- Default AI configuration
INSERT INTO ai_configurations (model_name, endpoint, permission_level, can_write_transactions, is_active)
VALUES ('gemini-2.5-flash', 'https://generativelanguage.googleapis.com/v1beta', 'read_full', true, true)
ON CONFLICT DO NOTHING;

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_accounts_user_id ON accounts(user_id);
CREATE INDEX IF NOT EXISTS idx_cards_user_id ON cards(user_id);
CREATE INDEX IF NOT EXISTS idx_transactions_user_id ON transactions(user_id);
CREATE INDEX IF NOT EXISTS idx_transactions_date ON transactions(date);
CREATE INDEX IF NOT EXISTS idx_transactions_category_id ON transactions(category_id);
CREATE INDEX IF NOT EXISTS idx_ai_chat_logs_user_id ON ai_chat_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_ai_chat_logs_created_at ON ai_chat_logs(created_at);

-- ====================================================================================
-- CRIAR USUÁRIO ADMIN PADRÃO
-- ====================================================================================
DO $$
DECLARE
    admin_id uuid;
BEGIN
    -- Registrar usuário admin
    admin_id := auth.register_user('admin@financeiro.com', 'admin123');
    
    -- Atualizar role para admin (o trigger já cria como admin se for o primeiro)
    UPDATE profiles SET role = 'admin' WHERE id = admin_id;
    
    RAISE NOTICE 'Admin criado com sucesso! ID: %', admin_id;
EXCEPTION
    WHEN unique_violation THEN
        RAISE NOTICE 'Usuário admin já existe.';
END$$;

GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO onlifin_user;
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA auth TO onlifin_user;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO onlifin_user;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA auth TO onlifin_user;
GRANT USAGE ON SCHEMA auth TO onlifin_user;
/*
# Create Financial Forecast System

## Plain English Explanation
This migration creates the infrastructure for AI-powered financial forecasting, including tables for bills to pay/receive, financial forecasts with predictions and alerts, and a notifications system. The system will automatically analyze transaction patterns and generate predictions for future cash flow.

## Table List & Column Descriptions

### 1. bills_to_pay (Contas a Pagar)
- `id` (uuid, primary key): Bill identifier
- `user_id` (uuid, references profiles): Owner of the bill
- `description` (text, not null): Bill description
- `amount` (numeric, not null): Bill amount
- `due_date` (date, not null): Payment due date
- `category_id` (uuid, references categories): Bill category
- `status` (text, not null): Status (pending, paid, overdue)
- `is_recurring` (boolean, default false): Recurring bill flag
- `recurrence_pattern` (text): Recurrence pattern (monthly, weekly, yearly)
- `account_id` (uuid, references accounts): Account for payment
- `paid_date` (date): Actual payment date
- `notes` (text): Additional notes
- `created_at` (timestamptz, default now()): Creation timestamp
- `updated_at` (timestamptz, default now()): Last update timestamp

### 2. bills_to_receive (Contas a Receber)
- `id` (uuid, primary key): Bill identifier
- `user_id` (uuid, references profiles): Owner of the bill
- `description` (text, not null): Bill description
- `amount` (numeric, not null): Bill amount
- `due_date` (date, not null): Expected receipt date
- `category_id` (uuid, references categories): Bill category
- `status` (text, not null): Status (pending, received, overdue)
- `is_recurring` (boolean, default false): Recurring bill flag
- `recurrence_pattern` (text): Recurrence pattern
- `account_id` (uuid, references accounts): Account for receipt
- `received_date` (date): Actual receipt date
- `notes` (text): Additional notes
- `created_at` (timestamptz, default now()): Creation timestamp
- `updated_at` (timestamptz, default now()): Last update timestamp

### 3. financial_forecasts (Previsões Financeiras)
- `id` (uuid, primary key): Forecast identifier
- `user_id` (uuid, references profiles): Owner of the forecast
- `calculation_date` (timestamptz, not null): When forecast was calculated
- `initial_balance` (numeric, not null): Starting balance
- `forecast_daily` (jsonb, not null): Daily predictions for 30 days
- `forecast_weekly` (jsonb, not null): Weekly predictions for 12 weeks
- `forecast_monthly` (jsonb, not null): Monthly predictions for 6 months
- `insights` (jsonb, not null): AI-generated insights array
- `alerts` (jsonb, not null): Risk alerts array
- `risk_negative` (boolean, default false): Negative balance risk flag
- `risk_date` (date): Date when negative balance is predicted
- `spending_patterns` (jsonb): Detected spending patterns
- `created_at` (timestamptz, default now()): Creation timestamp

### 4. notifications (Notificações)
- `id` (uuid, primary key): Notification identifier
- `user_id` (uuid, references profiles): Recipient user
- `title` (text, not null): Notification title
- `message` (text, not null): Notification message
- `type` (text, not null): Type (alert, info, warning, success)
- `severity` (text): Severity level (low, medium, high)
- `is_read` (boolean, default false): Read status
- `related_forecast_id` (uuid, references financial_forecasts): Related forecast
- `related_bill_id` (uuid): Related bill (generic reference)
- `action_url` (text): URL for action button
- `created_at` (timestamptz, default now()): Creation timestamp

## Security Changes
- Enable RLS on all new tables
- Users can only access their own bills, forecasts, and notifications
- Admins have full access to all data
- Create policies for read/write operations

## Notes
- Bills status automatically updates based on due dates
- Forecasts are generated daily by scheduled job
- Notifications are created when risks are detected
- All monetary values use numeric type for precision
*/

-- Create bills_to_pay table
CREATE TABLE IF NOT EXISTS bills_to_pay (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id uuid NOT NULL REFERENCES profiles(id) ON DELETE CASCADE,
  description text NOT NULL,
  amount numeric NOT NULL CHECK (amount > 0),
  due_date date NOT NULL,
  category_id uuid REFERENCES categories(id) ON DELETE SET NULL,
  status text NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'paid', 'overdue')),
  is_recurring boolean DEFAULT false,
  recurrence_pattern text,
  account_id uuid REFERENCES accounts(id) ON DELETE SET NULL,
  paid_date date,
  notes text,
  created_at timestamptz DEFAULT now(),
  updated_at timestamptz DEFAULT now()
);

-- Create bills_to_receive table
CREATE TABLE IF NOT EXISTS bills_to_receive (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id uuid NOT NULL REFERENCES profiles(id) ON DELETE CASCADE,
  description text NOT NULL,
  amount numeric NOT NULL CHECK (amount > 0),
  due_date date NOT NULL,
  category_id uuid REFERENCES categories(id) ON DELETE SET NULL,
  status text NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'received', 'overdue')),
  is_recurring boolean DEFAULT false,
  recurrence_pattern text,
  account_id uuid REFERENCES accounts(id) ON DELETE SET NULL,
  received_date date,
  notes text,
  created_at timestamptz DEFAULT now(),
  updated_at timestamptz DEFAULT now()
);

-- Create financial_forecasts table
CREATE TABLE IF NOT EXISTS financial_forecasts (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id uuid NOT NULL REFERENCES profiles(id) ON DELETE CASCADE,
  calculation_date timestamptz NOT NULL DEFAULT now(),
  initial_balance numeric NOT NULL,
  forecast_daily jsonb NOT NULL DEFAULT '{}',
  forecast_weekly jsonb NOT NULL DEFAULT '{}',
  forecast_monthly jsonb NOT NULL DEFAULT '{}',
  insights jsonb NOT NULL DEFAULT '[]',
  alerts jsonb NOT NULL DEFAULT '[]',
  risk_negative boolean DEFAULT false,
  risk_date date,
  spending_patterns jsonb DEFAULT '{}',
  created_at timestamptz DEFAULT now()
);

-- Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id uuid NOT NULL REFERENCES profiles(id) ON DELETE CASCADE,
  title text NOT NULL,
  message text NOT NULL,
  type text NOT NULL CHECK (type IN ('alert', 'info', 'warning', 'success')),
  severity text CHECK (severity IN ('low', 'medium', 'high')),
  is_read boolean DEFAULT false,
  related_forecast_id uuid REFERENCES financial_forecasts(id) ON DELETE SET NULL,
  related_bill_id uuid,
  action_url text,
  created_at timestamptz DEFAULT now()
);

-- Create indexes for performance
CREATE INDEX idx_bills_to_pay_user_id ON bills_to_pay(user_id);
CREATE INDEX idx_bills_to_pay_due_date ON bills_to_pay(due_date);
CREATE INDEX idx_bills_to_pay_status ON bills_to_pay(status);

CREATE INDEX idx_bills_to_receive_user_id ON bills_to_receive(user_id);
CREATE INDEX idx_bills_to_receive_due_date ON bills_to_receive(due_date);
CREATE INDEX idx_bills_to_receive_status ON bills_to_receive(status);

CREATE INDEX idx_financial_forecasts_user_id ON financial_forecasts(user_id);
CREATE INDEX idx_financial_forecasts_calculation_date ON financial_forecasts(calculation_date DESC);

CREATE INDEX idx_notifications_user_id ON notifications(user_id);
CREATE INDEX idx_notifications_is_read ON notifications(is_read);
CREATE INDEX idx_notifications_created_at ON notifications(created_at DESC);

-- Enable RLS
ALTER TABLE bills_to_pay ENABLE ROW LEVEL SECURITY;
ALTER TABLE bills_to_receive ENABLE ROW LEVEL SECURITY;
ALTER TABLE financial_forecasts ENABLE ROW LEVEL SECURITY;
ALTER TABLE notifications ENABLE ROW LEVEL SECURITY;

-- RLS Policies for bills_to_pay
CREATE POLICY "Users can view own bills to pay" ON bills_to_pay
  FOR SELECT USING (auth.uid() = user_id);

CREATE POLICY "Users can create own bills to pay" ON bills_to_pay
  FOR INSERT WITH CHECK (auth.uid() = user_id);

CREATE POLICY "Users can update own bills to pay" ON bills_to_pay
  FOR UPDATE USING (auth.uid() = user_id);

CREATE POLICY "Users can delete own bills to pay" ON bills_to_pay
  FOR DELETE USING (auth.uid() = user_id);

CREATE POLICY "Admins have full access to bills to pay" ON bills_to_pay
  FOR ALL USING (is_admin(auth.uid()));

-- RLS Policies for bills_to_receive
CREATE POLICY "Users can view own bills to receive" ON bills_to_receive
  FOR SELECT USING (auth.uid() = user_id);

CREATE POLICY "Users can create own bills to receive" ON bills_to_receive
  FOR INSERT WITH CHECK (auth.uid() = user_id);

CREATE POLICY "Users can update own bills to receive" ON bills_to_receive
  FOR UPDATE USING (auth.uid() = user_id);

CREATE POLICY "Users can delete own bills to receive" ON bills_to_receive
  FOR DELETE USING (auth.uid() = user_id);

CREATE POLICY "Admins have full access to bills to receive" ON bills_to_receive
  FOR ALL USING (is_admin(auth.uid()));

-- RLS Policies for financial_forecasts
CREATE POLICY "Users can view own forecasts" ON financial_forecasts
  FOR SELECT USING (auth.uid() = user_id);

CREATE POLICY "Admins have full access to forecasts" ON financial_forecasts
  FOR ALL USING (is_admin(auth.uid()));

-- RLS Policies for notifications
CREATE POLICY "Users can view own notifications" ON notifications
  FOR SELECT USING (auth.uid() = user_id);

CREATE POLICY "Users can update own notifications" ON notifications
  FOR UPDATE USING (auth.uid() = user_id);

CREATE POLICY "Admins have full access to notifications" ON notifications
  FOR ALL USING (is_admin(auth.uid()));

-- Function to automatically update bills status based on due date
CREATE OR REPLACE FUNCTION update_bills_status()
RETURNS void
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
BEGIN
  -- Update bills_to_pay status to overdue
  UPDATE bills_to_pay
  SET status = 'overdue', updated_at = now()
  WHERE status = 'pending' 
    AND due_date < CURRENT_DATE;

  -- Update bills_to_receive status to overdue
  UPDATE bills_to_receive
  SET status = 'overdue', updated_at = now()
  WHERE status = 'pending' 
    AND due_date < CURRENT_DATE;
END;
$$;

-- Function to get user's total current balance
CREATE OR REPLACE FUNCTION get_user_total_balance(p_user_id uuid)
RETURNS numeric
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
DECLARE
  v_total_balance numeric;
BEGIN
  SELECT COALESCE(SUM(balance), 0)
  INTO v_total_balance
  FROM accounts
  WHERE user_id = p_user_id;
  
  RETURN v_total_balance;
END;
$$;

-- Function to create notification
CREATE OR REPLACE FUNCTION create_notification(
  p_user_id uuid,
  p_title text,
  p_message text,
  p_type text,
  p_severity text DEFAULT NULL,
  p_related_forecast_id uuid DEFAULT NULL,
  p_related_bill_id uuid DEFAULT NULL,
  p_action_url text DEFAULT NULL
)
RETURNS uuid
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
DECLARE
  v_notification_id uuid;
BEGIN
  INSERT INTO notifications (
    user_id,
    title,
    message,
    type,
    severity,
    related_forecast_id,
    related_bill_id,
    action_url
  ) VALUES (
    p_user_id,
    p_title,
    p_message,
    p_type,
    p_severity,
    p_related_forecast_id,
    p_related_bill_id,
    p_action_url
  )
  RETURNING id INTO v_notification_id;
  
  RETURN v_notification_id;
END;
$$;
