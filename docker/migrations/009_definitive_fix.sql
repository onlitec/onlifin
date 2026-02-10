-- Migration: Definitive RLS and Visibility Fix (v3)
-- Description: Fixes 403/400 errors by identifying and removing recursive policies, 
-- and restores data visibility by reverting legacy data to NULL company_id/person_id.

BEGIN;

-- 1. NUCLEAR RLS RESET
DO $$ 
DECLARE
    t text;
    pol record;
BEGIN
    FOR t IN (SELECT unnest(ARRAY['accounts', 'transactions', 'cards', 'categories', 'people', 'profiles', 'companies', 'financial_forecasts', 'bills_to_pay', 'bills_to_receive', 'notifications', 'audit_logs'])) LOOP
        EXECUTE format('ALTER TABLE %I DISABLE ROW LEVEL SECURITY;', t);
        FOR pol IN (SELECT policyname FROM pg_policies WHERE tablename = t AND schemaname = 'public') LOOP
            EXECUTE format('DROP POLICY IF EXISTS %I ON %I;', pol.policyname, t);
        END LOOP;
        EXECUTE format('ALTER TABLE %I ENABLE ROW LEVEL SECURITY;', t);
    END LOOP;
END $$;

-- 2. SECURITY FUNCTIONS FIX
DROP FUNCTION IF EXISTS public.is_admin(UUID);
CREATE OR REPLACE FUNCTION public.is_admin(p_uid UUID) 
RETURNS BOOLEAN AS $$
BEGIN
    RETURN EXISTS (SELECT 1 FROM public.profiles WHERE id = p_uid AND role = 'admin');
END;
$$ LANGUAGE plpgsql SECURITY DEFINER SET search_path = public;

-- 3. RECREATE LEAN POLICIES
-- Profiles
CREATE POLICY "profiles_self_and_admin" ON public.profiles FOR ALL TO authenticated USING (id = auth.uid() OR is_admin(auth.uid()));

-- Companies
CREATE POLICY "companies_owner_and_admin" ON public.companies FOR ALL TO authenticated USING (user_id = auth.uid() OR is_admin(auth.uid()));

-- People
CREATE POLICY "people_owner_and_admin" ON public.people FOR ALL TO authenticated USING (user_id = auth.uid() OR is_admin(auth.uid()));

-- Standard Financial Tables with column safety
DO $$ 
DECLARE
    t text;
    has_company_id boolean;
    has_person_id boolean;
BEGIN
    FOR t IN (SELECT unnest(ARRAY['accounts', 'transactions', 'cards', 'categories', 'bills_to_pay', 'bills_to_receive', 'financial_forecasts', 'notifications'])) LOOP
        
        SELECT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = t AND column_name = 'company_id') INTO has_company_id;
        SELECT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = t AND column_name = 'person_id') INTO has_person_id;

        EXECUTE format('CREATE POLICY %I_policy ON %I FOR ALL TO authenticated 
            USING (
                user_id = auth.uid() OR 
                %s OR
                %s OR
                is_admin(auth.uid())
                %s
            )', 
            t, t, 
            CASE WHEN has_company_id THEN 'company_id IN (SELECT id FROM companies WHERE user_id = auth.uid())' ELSE 'false' END,
            CASE WHEN has_person_id THEN 'person_id IN (SELECT id FROM people WHERE user_id = auth.uid())' ELSE 'false' END,
            CASE WHEN t = 'categories' THEN 'OR is_system = true' ELSE '' END
        );
    END LOOP;
END $$;

-- 4. RESTORE DATA VISIBILITY
UPDATE transactions SET person_id = NULL WHERE company_id IS NULL;
UPDATE accounts SET person_id = NULL WHERE company_id IS NULL;
UPDATE cards SET person_id = NULL WHERE company_id IS NULL;
UPDATE categories SET person_id = NULL WHERE company_id IS NULL AND is_system = false;
UPDATE bills_to_pay SET person_id = NULL WHERE company_id IS NULL;
UPDATE bills_to_receive SET person_id = NULL WHERE company_id IS NULL;
UPDATE financial_forecasts SET person_id = NULL WHERE company_id IS NULL;

-- 5. RE-GRANT PERMISSIONS
GRANT USAGE ON SCHEMA public TO authenticated, anon;
GRANT USAGE ON SCHEMA auth TO authenticated, anon;
GRANT ALL ON ALL TABLES IN SCHEMA public TO authenticated;
GRANT ALL ON ALL SEQUENCES IN SCHEMA public TO authenticated;
GRANT SELECT ON ALL TABLES IN SCHEMA public TO anon;
GRANT EXECUTE ON ALL FUNCTIONS IN SCHEMA public TO authenticated, anon;
GRANT SELECT ON ALL TABLES IN SCHEMA auth TO authenticated;

COMMIT;
