-- ============================================================================
-- OnliFin - Auth Functions & RLS Policies (FIXED)
-- ============================================================================

BEGIN;

-- Create auth schema if not exists
CREATE SCHEMA IF NOT EXISTS auth;

-- auth.role(): Get app_role from JWT claims
CREATE OR REPLACE FUNCTION auth.role()
RETURNS text
LANGUAGE sql
STABLE
AS $$
  SELECT NULLIF(current_setting('request.jwt.claims', true)::json->>'app_role', '');
$$;

-- auth.user_id(): Get user_id from JWT claims
CREATE OR REPLACE FUNCTION auth.user_id()
RETURNS uuid
LANGUAGE sql
STABLE
AS $$
  SELECT NULLIF(current_setting('request.jwt.claims', true)::json->>'sub', '')::uuid;
$$;

-- auth.is_admin(): Check if current user is admin (using JWT claim to avoid recursion)
CREATE OR REPLACE FUNCTION auth.is_admin()
RETURNS boolean
LANGUAGE sql
STABLE
AS $$
  SELECT auth.role() = 'admin';
$$;

-- Enable RLS on user tables
ALTER TABLE public.profiles ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.transactions ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.accounts ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.cards ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.categories ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.bills_to_pay ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.bills_to_receive ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.financial_forecasts ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.import_history ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.ai_chat_logs ENABLE ROW LEVEL SECURITY;

-- Profiles Policies
DROP POLICY IF EXISTS "Users can view own profile" ON public.profiles;
CREATE POLICY "Users can view own profile" ON public.profiles
    FOR SELECT USING (id = auth.user_id() OR auth.is_admin());

DROP POLICY IF EXISTS "Users can update own profile" ON public.profiles;
CREATE POLICY "Users can update own profile" ON public.profiles
    FOR UPDATE USING (id = auth.user_id())
    WITH CHECK (id = auth.user_id() AND (role = (SELECT role FROM public.profiles WHERE id = auth.user_id())));

DROP POLICY IF EXISTS "Admins can insert profiles" ON public.profiles;
CREATE POLICY "Admins can insert profiles" ON public.profiles
    FOR INSERT WITH CHECK (auth.is_admin());

DROP POLICY IF EXISTS "Admins can delete profiles" ON public.profiles;
CREATE POLICY "Admins can delete profiles" ON public.profiles
    FOR DELETE USING (auth.is_admin());

-- Transactions Policies
DROP POLICY IF EXISTS "Users can view own transactions" ON public.transactions;
CREATE POLICY "Users can view own transactions" ON public.transactions
    FOR SELECT USING (user_id = auth.user_id() OR auth.is_admin());

DROP POLICY IF EXISTS "Users can insert own transactions" ON public.transactions;
CREATE POLICY "Users can insert own transactions" ON public.transactions
    FOR INSERT WITH CHECK (user_id = auth.user_id());

DROP POLICY IF EXISTS "Users can update own transactions" ON public.transactions;
CREATE POLICY "Users can update own transactions" ON public.transactions
    FOR UPDATE USING (user_id = auth.user_id())
    WITH CHECK (user_id = auth.user_id());

DROP POLICY IF EXISTS "Users can delete own transactions" ON public.transactions;
CREATE POLICY "Users can delete own transactions" ON public.transactions
    FOR DELETE USING (user_id = auth.user_id() OR auth.is_admin());

-- Accounts Policies
DROP POLICY IF EXISTS "Users can view own accounts" ON public.accounts;
CREATE POLICY "Users can view own accounts" ON public.accounts
    FOR SELECT USING (user_id = auth.user_id() OR auth.is_admin());

DROP POLICY IF EXISTS "Users can insert own accounts" ON public.accounts;
CREATE POLICY "Users can insert own accounts" ON public.accounts
    FOR INSERT WITH CHECK (user_id = auth.user_id());

DROP POLICY IF EXISTS "Users can update own accounts" ON public.accounts;
CREATE POLICY "Users can update own accounts" ON public.accounts
    FOR UPDATE USING (user_id = auth.user_id())
    WITH CHECK (user_id = auth.user_id());

DROP POLICY IF EXISTS "Users can delete own accounts" ON public.accounts;
CREATE POLICY "Users can delete own accounts" ON public.accounts
    FOR DELETE USING (user_id = auth.user_id() OR auth.is_admin());

-- Cards Policies
DROP POLICY IF EXISTS "Users can view own cards" ON public.cards;
CREATE POLICY "Users can view own cards" ON public.cards
    FOR SELECT USING (user_id = auth.user_id() OR auth.is_admin());

DROP POLICY IF EXISTS "Users can insert own cards" ON public.cards;
CREATE POLICY "Users can insert own cards" ON public.cards
    FOR INSERT WITH CHECK (user_id = auth.user_id());

DROP POLICY IF EXISTS "Users can update own cards" ON public.cards;
CREATE POLICY "Users can update own cards" ON public.cards
    FOR UPDATE USING (user_id = auth.user_id())
    WITH CHECK (user_id = auth.user_id());

DROP POLICY IF EXISTS "Users can delete own cards" ON public.cards;
CREATE POLICY "Users can delete own cards" ON public.cards
    FOR DELETE USING (user_id = auth.user_id() OR auth.is_admin());

-- Financial Forecasts Policies
DROP POLICY IF EXISTS "Users can view own forecasts" ON public.financial_forecasts;
CREATE POLICY "Users can view own forecasts" ON public.financial_forecasts
    FOR SELECT USING (user_id = auth.user_id() OR auth.is_admin());

DROP POLICY IF EXISTS "Users can manage own forecasts" ON public.financial_forecasts;
CREATE POLICY "Users can manage own forecasts" ON public.financial_forecasts
    FOR ALL USING (user_id = auth.user_id())
    WITH CHECK (user_id = auth.user_id());

-- Grant Schema Access
GRANT USAGE ON SCHEMA auth TO anon, authenticated;
GRANT EXECUTE ON ALL FUNCTIONS IN SCHEMA auth TO anon, authenticated;

COMMIT;
