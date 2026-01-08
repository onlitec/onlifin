-- ============================================================================
-- OnliFin - Fix Authentication (Enable RLS and Create Auth Functions)
-- ============================================================================
-- This script fixes 401 authentication errors by:
-- 1. Creating auth schema and helper functions
-- 2. Enabling Row Level Security (RLS) on all user tables
-- 3. Creating RLS policies for secure data access
-- ============================================================================

BEGIN;

-- ============================================================================
-- 1. Create auth schema and functions
-- ============================================================================

CREATE SCHEMA IF NOT EXISTS auth;

-- Function to extract user ID from JWT token
CREATE OR REPLACE FUNCTION auth.user_id()
RETURNS uuid
LANGUAGE sql
STABLE
AS $$
  SELECT NULLIF(current_setting('request.jwt.claims', true)::json->>'sub', '')::uuid;
$$;

-- Function to extract user role from JWT token
CREATE OR REPLACE FUNCTION auth.role()
RETURNS text
LANGUAGE sql
STABLE
AS $$
  SELECT NULLIF(current_setting('request.jwt.claims', true)::json->>'role', '')::text;
$$;

-- Function to check if current user is admin
CREATE OR REPLACE FUNCTION auth.is_admin()
RETURNS boolean
LANGUAGE sql
STABLE
AS $$
  SELECT EXISTS (
    SELECT 1 FROM profiles
    WHERE id = auth.user_id()
    AND role = 'admin'
  );
$$;

-- ============================================================================
-- 2. Enable RLS on all user tables
-- ============================================================================

ALTER TABLE profiles ENABLE ROW LEVEL SECURITY;
ALTER TABLE transactions ENABLE ROW LEVEL SECURITY;
ALTER TABLE accounts ENABLE ROW LEVEL SECURITY;
ALTER TABLE cards ENABLE ROW LEVEL SECURITY;
ALTER TABLE categories ENABLE ROW LEVEL SECURITY;
ALTER TABLE bills_to_pay ENABLE ROW LEVEL SECURITY;
ALTER TABLE bills_to_receive ENABLE ROW LEVEL SECURITY;
ALTER TABLE financial_forecasts ENABLE ROW LEVEL SECURITY;
ALTER TABLE import_history ENABLE ROW LEVEL SECURITY;
ALTER TABLE ai_chat_logs ENABLE ROW LEVEL SECURITY;

-- ============================================================================
-- 3. Create RLS policies for each table
-- ============================================================================

-- Profiles table policies
DROP POLICY IF EXISTS "Users can view own profile" ON profiles;
CREATE POLICY "Users can view own profile"
  ON profiles FOR SELECT
  USING (id = auth.user_id() OR auth.is_admin());

DROP POLICY IF EXISTS "Users can update own profile" ON profiles;
CREATE POLICY "Users can update own profile"
  ON profiles FOR UPDATE
  USING (id = auth.user_id())
  WITH CHECK (id = auth.user_id() AND role = (SELECT role FROM profiles WHERE id = auth.user_id()));

DROP POLICY IF EXISTS "Admins can insert profiles" ON profiles;
CREATE POLICY "Admins can insert profiles"
  ON profiles FOR INSERT
  WITH CHECK (auth.is_admin());

DROP POLICY IF EXISTS "Admins can delete profiles" ON profiles;
CREATE POLICY "Admins can delete profiles"
  ON profiles FOR DELETE
  USING (auth.is_admin());

-- Transactions table policies
DROP POLICY IF EXISTS "Users can view own transactions" ON transactions;
CREATE POLICY "Users can view own transactions"
  ON transactions FOR SELECT
  USING (user_id = auth.user_id() OR auth.is_admin());

DROP POLICY IF EXISTS "Users can insert own transactions" ON transactions;
CREATE POLICY "Users can insert own transactions"
  ON transactions FOR INSERT
  WITH CHECK (user_id = auth.user_id());

DROP POLICY IF EXISTS "Users can update own transactions" ON transactions;
CREATE POLICY "Users can update own transactions"
  ON transactions FOR UPDATE
  USING (user_id = auth.user_id())
  WITH CHECK (user_id = auth.user_id());

DROP POLICY IF EXISTS "Users can delete own transactions" ON transactions;
CREATE POLICY "Users can delete own transactions"
  ON transactions FOR DELETE
  USING (user_id = auth.user_id() OR auth.is_admin());

-- Accounts table policies
DROP POLICY IF EXISTS "Users can view own accounts" ON accounts;
CREATE POLICY "Users can view own accounts"
  ON accounts FOR SELECT
  USING (user_id = auth.user_id() OR auth.is_admin());

DROP POLICY IF EXISTS "Users can insert own accounts" ON accounts;
CREATE POLICY "Users can insert own accounts"
  ON accounts FOR INSERT
  WITH CHECK (user_id = auth.user_id());

DROP POLICY IF EXISTS "Users can update own accounts" ON accounts;
CREATE POLICY "Users can update own accounts"
  ON accounts FOR UPDATE
  USING (user_id = auth.user_id())
  WITH CHECK (user_id = auth.user_id());

DROP POLICY IF EXISTS "Users can delete own accounts" ON accounts;
CREATE POLICY "Users can delete own accounts"
  ON accounts FOR DELETE
  USING (user_id = auth.user_id() OR auth.is_admin());

-- Cards table policies
DROP POLICY IF EXISTS "Users can view own cards" ON cards;
CREATE POLICY "Users can view own cards"
  ON cards FOR SELECT
  USING (user_id = auth.user_id() OR auth.is_admin());

DROP POLICY IF EXISTS "Users can insert own cards" ON cards;
CREATE POLICY "Users can insert own cards"
  ON cards FOR INSERT
  WITH CHECK (user_id = auth.user_id());

DROP POLICY IF EXISTS "Users can update own cards" ON cards;
CREATE POLICY "Users can update own cards"
  ON cards FOR UPDATE
  USING (user_id = auth.user_id())
  WITH CHECK (user_id = auth.user_id());

DROP POLICY IF EXISTS "Users can delete own cards" ON cards;
CREATE POLICY "Users can delete own cards"
  ON cards FOR DELETE
  USING (user_id = auth.user_id() OR auth.is_admin());

-- Categories table policies
DROP POLICY IF EXISTS "Users can view own categories" ON categories;
CREATE POLICY "Users can view own categories"
  ON categories FOR SELECT
  USING (user_id = auth.user_id() OR auth.is_admin());

DROP POLICY IF EXISTS "Users can insert own categories" ON categories;
CREATE POLICY "Users can insert own categories"
  ON categories FOR INSERT
  WITH CHECK (user_id = auth.user_id());

DROP POLICY IF EXISTS "Users can update own categories" ON categories;
CREATE POLICY "Users can update own categories"
  ON categories FOR UPDATE
  USING (user_id = auth.user_id())
  WITH CHECK (user_id = auth.user_id());

DROP POLICY IF EXISTS "Users can delete own categories" ON categories;
CREATE POLICY "Users can delete own categories"
  ON categories FOR DELETE
  USING (user_id = auth.user_id() OR auth.is_admin());

-- Bills to pay table policies
DROP POLICY IF EXISTS "Users can view own bills to pay" ON bills_to_pay;
CREATE POLICY "Users can view own bills to pay"
  ON bills_to_pay FOR SELECT
  USING (user_id = auth.user_id() OR auth.is_admin());

DROP POLICY IF EXISTS "Users can insert own bills to pay" ON bills_to_pay;
CREATE POLICY "Users can insert own bills to pay"
  ON bills_to_pay FOR INSERT
  WITH CHECK (user_id = auth.user_id());

DROP POLICY IF EXISTS "Users can update own bills to pay" ON bills_to_pay;
CREATE POLICY "Users can update own bills to pay"
  ON bills_to_pay FOR UPDATE
  USING (user_id = auth.user_id())
  WITH CHECK (user_id = auth.user_id());

DROP POLICY IF EXISTS "Users can delete own bills to pay" ON bills_to_pay;
CREATE POLICY "Users can delete own bills to pay"
  ON bills_to_pay FOR DELETE
  USING (user_id = auth.user_id() OR auth.is_admin());

-- Bills to receive table policies
DROP POLICY IF EXISTS "Users can view own bills to receive" ON bills_to_receive;
CREATE POLICY "Users can view own bills to receive"
  ON bills_to_receive FOR SELECT
  USING (user_id = auth.user_id() OR auth.is_admin());

DROP POLICY IF EXISTS "Users can insert own bills to receive" ON bills_to_receive;
CREATE POLICY "Users can insert own bills to receive"
  ON bills_to_receive FOR INSERT
  WITH CHECK (user_id = auth.user_id());

DROP POLICY IF EXISTS "Users can update own bills to receive" ON bills_to_receive;
CREATE POLICY "Users can update own bills to receive"
  ON bills_to_receive FOR UPDATE
  USING (user_id = auth.user_id())
  WITH CHECK (user_id = auth.user_id());

DROP POLICY IF EXISTS "Users can delete own bills to receive" ON bills_to_receive;
CREATE POLICY "Users can delete own bills to receive"
  ON bills_to_receive FOR DELETE
  USING (user_id = auth.user_id() OR auth.is_admin());

-- Financial forecasts table policies
DROP POLICY IF EXISTS "Users can view own forecasts" ON financial_forecasts;
CREATE POLICY "Users can view own forecasts"
  ON financial_forecasts FOR SELECT
  USING (user_id = auth.user_id() OR auth.is_admin());

DROP POLICY IF EXISTS "Users can insert own forecasts" ON financial_forecasts;
CREATE POLICY "Users can insert own forecasts"
  ON financial_forecasts FOR INSERT
  WITH CHECK (user_id = auth.user_id());

DROP POLICY IF EXISTS "Users can update own forecasts" ON financial_forecasts;
CREATE POLICY "Users can update own forecasts"
  ON financial_forecasts FOR UPDATE
  USING (user_id = auth.user_id())
  WITH CHECK (user_id = auth.user_id());

DROP POLICY IF EXISTS "Users can delete own forecasts" ON financial_forecasts;
CREATE POLICY "Users can delete own forecasts"
  ON financial_forecasts FOR DELETE
  USING (user_id = auth.user_id() OR auth.is_admin());

-- Import history table policies
DROP POLICY IF EXISTS "Users can view own import history" ON import_history;
CREATE POLICY "Users can view own import history"
  ON import_history FOR SELECT
  USING (user_id = auth.user_id() OR auth.is_admin());

DROP POLICY IF EXISTS "Users can insert own import history" ON import_history;
CREATE POLICY "Users can insert own import history"
  ON import_history FOR INSERT
  WITH CHECK (user_id = auth.user_id());

DROP POLICY IF EXISTS "Users can update own import history" ON import_history;
CREATE POLICY "Users can update own import history"
  ON import_history FOR UPDATE
  USING (user_id = auth.user_id())
  WITH CHECK (user_id = auth.user_id());

DROP POLICY IF EXISTS "Users can delete own import history" ON import_history;
CREATE POLICY "Users can delete own import history"
  ON import_history FOR DELETE
  USING (user_id = auth.user_id() OR auth.is_admin());

-- AI chat logs table policies
DROP POLICY IF EXISTS "Users can view own chat logs" ON ai_chat_logs;
CREATE POLICY "Users can view own chat logs"
  ON ai_chat_logs FOR SELECT
  USING (user_id = auth.user_id() OR auth.is_admin());

DROP POLICY IF EXISTS "Users can insert own chat logs" ON ai_chat_logs;
CREATE POLICY "Users can insert own chat logs"
  ON ai_chat_logs FOR INSERT
  WITH CHECK (user_id = auth.user_id());

DROP POLICY IF EXISTS "Users can delete own chat logs" ON ai_chat_logs;
CREATE POLICY "Users can delete own chat logs"
  ON ai_chat_logs FOR DELETE
  USING (user_id = auth.user_id() OR auth.is_admin());

-- ============================================================================
-- 4. Grant necessary permissions
-- ============================================================================

-- Grant usage on auth schema
GRANT USAGE ON SCHEMA auth TO anon, authenticated;

-- Grant execute on auth functions
GRANT EXECUTE ON FUNCTION auth.user_id() TO anon, authenticated;
GRANT EXECUTE ON FUNCTION auth.role() TO anon, authenticated;
GRANT EXECUTE ON FUNCTION auth.is_admin() TO anon, authenticated;

COMMIT;

-- ============================================================================
-- Verification queries
-- ============================================================================

-- Check RLS status
SELECT tablename, rowsecurity 
FROM pg_tables 
WHERE schemaname = 'public' 
AND tablename IN ('profiles', 'transactions', 'accounts', 'cards', 'categories')
ORDER BY tablename;

-- Check policies
SELECT tablename, policyname, cmd
FROM pg_policies
WHERE tablename IN ('profiles', 'transactions', 'accounts')
ORDER BY tablename, policyname;

-- Check auth functions
SELECT routine_schema, routine_name, routine_type
FROM information_schema.routines
WHERE routine_schema = 'auth'
ORDER BY routine_name;
