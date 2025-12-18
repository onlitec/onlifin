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
