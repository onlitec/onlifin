-- ===========================================
-- 02 - Schema Principal do Onlifin
-- ===========================================
-- Baseado no schema original do Supabase

-- ENUM types
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
END;
$$;

-- Profiles table
CREATE TABLE IF NOT EXISTS profiles (
    id uuid PRIMARY KEY REFERENCES auth.users(id) ON DELETE CASCADE,
    username text UNIQUE NOT NULL,
    full_name text,
    role user_role DEFAULT 'user'::user_role NOT NULL,
    created_at timestamptz DEFAULT now()
);

-- Accounts table
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

-- Cards table
CREATE TABLE IF NOT EXISTS cards (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id uuid REFERENCES profiles(id) ON DELETE CASCADE NOT NULL,
    account_id uuid REFERENCES accounts(id) ON DELETE SET NULL,
    name text NOT NULL,
    card_limit numeric NOT NULL,
    available_limit numeric DEFAULT 0,
    closing_day integer CHECK (closing_day >= 1 AND closing_day <= 31),
    due_day integer CHECK (due_day >= 1 AND due_day <= 31),
    created_at timestamptz DEFAULT now(),
    updated_at timestamptz DEFAULT now()
);

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id uuid REFERENCES profiles(id) ON DELETE CASCADE,
    name text NOT NULL,
    type transaction_type NOT NULL,
    icon text,
    color text,
    created_at timestamptz DEFAULT now()
);

-- Transactions table
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
    is_installment boolean DEFAULT false,
    installment_number integer,
    total_installments integer,
    parent_transaction_id uuid REFERENCES transactions(id) ON DELETE CASCADE,
    is_reconciled boolean DEFAULT false,
    created_at timestamptz DEFAULT now(),
    updated_at timestamptz DEFAULT now()
);

-- AI configurations table
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

-- AI chat logs table
CREATE TABLE IF NOT EXISTS ai_chat_logs (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id uuid REFERENCES profiles(id) ON DELETE CASCADE NOT NULL,
    message text NOT NULL,
    response text,
    data_accessed jsonb,
    permission_level ai_permission_level NOT NULL,
    created_at timestamptz DEFAULT now()
);

-- Import history table
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

-- Indexes
CREATE INDEX IF NOT EXISTS idx_accounts_user_id ON accounts(user_id);
CREATE INDEX IF NOT EXISTS idx_cards_user_id ON cards(user_id);
CREATE INDEX IF NOT EXISTS idx_transactions_user_id ON transactions(user_id);
CREATE INDEX IF NOT EXISTS idx_transactions_date ON transactions(date);
CREATE INDEX IF NOT EXISTS idx_transactions_category_id ON transactions(category_id);
CREATE INDEX IF NOT EXISTS idx_ai_chat_logs_user_id ON ai_chat_logs(user_id);

-- Permissões para PostgREST
GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO authenticated;
GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO authenticated;
GRANT SELECT ON categories TO anon;
