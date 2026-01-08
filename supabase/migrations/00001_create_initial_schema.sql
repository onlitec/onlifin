/*
# Create Initial Schema for Financial Management Platform

## Plain English Explanation
This migration creates the foundational database structure for a personal financial management platform. It includes tables for user profiles with role-based access control, bank accounts, credit cards, transaction categories, financial transactions, AI configuration settings, AI interaction logs, and file import history.

## Table List & Column Descriptions

### 1. profiles
- `id` (uuid, primary key, references auth.users): User identifier
- `username` (text, unique, not null): Username for login
- `full_name` (text): User's full name
- `role` (user_role enum, default 'user'): User role (user, financeiro, admin)
- `created_at` (timestamptz, default now()): Profile creation timestamp

### 2. accounts
- `id` (uuid, primary key): Account identifier
- `user_id` (uuid, references profiles): Owner of the account
- `name` (text, not null): Account name
- `bank` (text): Bank name
- `agency` (text): Bank agency number
- `account_number` (text): Account number (encrypted)
- `currency` (text, default 'BRL'): Currency code
- `balance` (numeric, default 0): Current balance
- `created_at` (timestamptz, default now()): Creation timestamp
- `updated_at` (timestamptz, default now()): Last update timestamp

### 3. cards
- `id` (uuid, primary key): Card identifier
- `user_id` (uuid, references profiles): Owner of the card
- `account_id` (uuid, references accounts): Linked account
- `name` (text, not null): Card name
- `card_limit` (numeric, not null): Credit limit
- `closing_day` (integer): Statement closing day (1-31)
- `due_day` (integer): Payment due day (1-31)
- `created_at` (timestamptz, default now()): Creation timestamp
- `updated_at` (timestamptz, default now()): Last update timestamp

### 4. categories
- `id` (uuid, primary key): Category identifier
- `user_id` (uuid, references profiles): Owner (null for system categories)
- `name` (text, not null): Category name
- `type` (transaction_type enum): income or expense
- `icon` (text): Icon identifier
- `color` (text): Color code
- `created_at` (timestamptz, default now()): Creation timestamp

### 5. transactions
- `id` (uuid, primary key): Transaction identifier
- `user_id` (uuid, references profiles): Owner of the transaction
- `account_id` (uuid, references accounts): Related account
- `card_id` (uuid, references cards): Related card (optional)
- `category_id` (uuid, references categories): Transaction category
- `type` (transaction_type enum): income or expense
- `amount` (numeric, not null): Transaction amount
- `date` (date, not null): Transaction date
- `description` (text): Transaction description
- `tags` (text[]): Array of tags
- `is_recurring` (boolean, default false): Recurring transaction flag
- `recurrence_pattern` (text): Recurrence pattern (monthly, weekly, etc.)
- `installment_number` (integer): Current installment number
- `total_installments` (integer): Total number of installments
- `parent_transaction_id` (uuid): Parent transaction for installments
- `is_reconciled` (boolean, default false): Reconciliation status
- `created_at` (timestamptz, default now()): Creation timestamp
- `updated_at` (timestamptz, default now()): Last update timestamp

### 6. ai_configurations
- `id` (uuid, primary key): Configuration identifier
- `model_name` (text, not null): AI model name
- `endpoint` (text): API endpoint
- `permission_level` (ai_permission_level enum): read_aggregated, read_transactional, read_full
- `is_active` (boolean, default true): Active status
- `created_at` (timestamptz, default now()): Creation timestamp
- `updated_at` (timestamptz, default now()): Last update timestamp

### 7. ai_chat_logs
- `id` (uuid, primary key): Log identifier
- `user_id` (uuid, references profiles): User who interacted
- `message` (text, not null): User message
- `response` (text): AI response
- `data_accessed` (jsonb): Metadata about accessed data
- `permission_level` (ai_permission_level enum): Permission level used
- `created_at` (timestamptz, default now()): Interaction timestamp

### 8. import_history
- `id` (uuid, primary key): Import identifier
- `user_id` (uuid, references profiles): User who imported
- `filename` (text, not null): Original filename
- `format` (text, not null): File format (CSV, OFX, QIF)
- `status` (text, not null): Import status (success, failed, partial)
- `imported_count` (integer, default 0): Number of imported transactions
- `error_message` (text): Error details if failed
- `created_at` (timestamptz, default now()): Import timestamp

## Security Changes
- Enable RLS on all tables
- Create helper function `is_admin` to check admin role
- Create helper function `is_financeiro_or_admin` to check elevated permissions
- Policies for profiles: admins have full access, users can view/update their own
- Policies for financial tables: users can only access their own data, admins and financeiro can access all
- Public read access for system categories (where user_id is null)

## Notes
- First user to register will automatically become admin (handled in trigger)
- Account numbers should be encrypted at application level before storage
- AI chat logs retain full conversation history for audit purposes
- System categories (user_id = null) are visible to all users
*/

-- Create ENUM types
CREATE TYPE user_role AS ENUM ('user', 'financeiro', 'admin');
CREATE TYPE transaction_type AS ENUM ('income', 'expense');
CREATE TYPE ai_permission_level AS ENUM ('read_aggregated', 'read_transactional', 'read_full');

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
  created_at timestamptz DEFAULT now(),
  updated_at timestamptz DEFAULT now()
);

-- Create ai_configurations table
CREATE TABLE IF NOT EXISTS ai_configurations (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  model_name text NOT NULL,
  endpoint text,
  permission_level ai_permission_level DEFAULT 'read_aggregated'::ai_permission_level NOT NULL,
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

-- Enable RLS on all tables
ALTER TABLE profiles ENABLE ROW LEVEL SECURITY;
ALTER TABLE accounts ENABLE ROW LEVEL SECURITY;
ALTER TABLE cards ENABLE ROW LEVEL SECURITY;
ALTER TABLE categories ENABLE ROW LEVEL SECURITY;
ALTER TABLE transactions ENABLE ROW LEVEL SECURITY;
ALTER TABLE ai_configurations ENABLE ROW LEVEL SECURITY;
ALTER TABLE ai_chat_logs ENABLE ROW LEVEL SECURITY;
ALTER TABLE import_history ENABLE ROW LEVEL SECURITY;

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

-- Profiles policies
CREATE POLICY "Admins have full access to profiles" ON profiles
  FOR ALL TO authenticated USING (is_admin(auth.uid()));

CREATE POLICY "Users can view own profile" ON profiles
  FOR SELECT TO authenticated USING (auth.uid() = id);

CREATE POLICY "Users can update own profile without changing role" ON profiles
  FOR UPDATE TO authenticated USING (auth.uid() = id) 
  WITH CHECK (role IS NOT DISTINCT FROM (SELECT role FROM profiles WHERE id = auth.uid()));

-- Accounts policies
CREATE POLICY "Users can manage own accounts" ON accounts
  FOR ALL TO authenticated USING (user_id = auth.uid());

CREATE POLICY "Financeiro and admins can view all accounts" ON accounts
  FOR SELECT TO authenticated USING (is_financeiro_or_admin(auth.uid()));

-- Cards policies
CREATE POLICY "Users can manage own cards" ON cards
  FOR ALL TO authenticated USING (user_id = auth.uid());

CREATE POLICY "Financeiro and admins can view all cards" ON cards
  FOR SELECT TO authenticated USING (is_financeiro_or_admin(auth.uid()));

-- Categories policies
CREATE POLICY "Users can view all categories" ON categories
  FOR SELECT TO authenticated USING (true);

CREATE POLICY "Users can manage own categories" ON categories
  FOR ALL TO authenticated USING (user_id = auth.uid());

CREATE POLICY "System categories are visible to all" ON categories
  FOR SELECT TO authenticated USING (user_id IS NULL);

-- Transactions policies
CREATE POLICY "Users can manage own transactions" ON transactions
  FOR ALL TO authenticated USING (user_id = auth.uid());

CREATE POLICY "Financeiro and admins can view all transactions" ON transactions
  FOR SELECT TO authenticated USING (is_financeiro_or_admin(auth.uid()));

-- AI configurations policies (admin only)
CREATE POLICY "Admins can manage AI configurations" ON ai_configurations
  FOR ALL TO authenticated USING (is_admin(auth.uid()));

CREATE POLICY "All users can view active AI configuration" ON ai_configurations
  FOR SELECT TO authenticated USING (is_active = true);

-- AI chat logs policies
CREATE POLICY "Users can view own chat logs" ON ai_chat_logs
  FOR SELECT TO authenticated USING (user_id = auth.uid());

CREATE POLICY "Users can insert own chat logs" ON ai_chat_logs
  FOR INSERT TO authenticated WITH CHECK (user_id = auth.uid());

CREATE POLICY "Admins can view all chat logs" ON ai_chat_logs
  FOR SELECT TO authenticated USING (is_admin(auth.uid()));

-- Import history policies
CREATE POLICY "Users can view own import history" ON import_history
  FOR SELECT TO authenticated USING (user_id = auth.uid());

CREATE POLICY "Users can insert own import history" ON import_history
  FOR INSERT TO authenticated WITH CHECK (user_id = auth.uid());

CREATE POLICY "Admins can view all import history" ON import_history
  FOR SELECT TO authenticated USING (is_admin(auth.uid()));

-- Create trigger function for new user registration
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
  
  -- Buscar email diretamente da tabela auth.users se disponível, senão usar o placeholder
  INSERT INTO profiles (id, username, email, role)
  VALUES (
    NEW.id,
    extracted_username,
    NEW.email,
    CASE WHEN user_count = 0 THEN 'admin'::user_role ELSE 'user'::user_role END
  );
  RETURN NEW;
END;
$$;

-- Create trigger for new user profile creation
DROP TRIGGER IF EXISTS on_auth_user_created ON auth.users;
CREATE TRIGGER on_auth_user_created
  AFTER INSERT ON auth.users
  FOR EACH ROW
  EXECUTE FUNCTION handle_new_user();

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
  (NULL, 'Outros Gastos', 'expense', '💸', '#E74C3C');

-- Create indexes for better performance
CREATE INDEX idx_accounts_user_id ON accounts(user_id);
CREATE INDEX idx_cards_user_id ON cards(user_id);
CREATE INDEX idx_transactions_user_id ON transactions(user_id);
CREATE INDEX idx_transactions_date ON transactions(date);
CREATE INDEX idx_transactions_category_id ON transactions(category_id);
CREATE INDEX idx_ai_chat_logs_user_id ON ai_chat_logs(user_id);
CREATE INDEX idx_ai_chat_logs_created_at ON ai_chat_logs(created_at);
