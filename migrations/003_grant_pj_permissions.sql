-- Migration: Grant permissions for PJ tables
-- Description: Grants SELECT, INSERT, UPDATE, DELETE permissions to the authenticated role for companies and related tables

-- Permissions for account_types
GRANT SELECT ON public.account_types TO authenticated;

-- Permissions for companies
GRANT SELECT, INSERT, UPDATE, DELETE ON public.companies TO authenticated;

-- Permissions for audit_logs
GRANT SELECT ON public.audit_logs TO authenticated;

-- Ensure RLS is still working (it is by default, but good to be explicit if needed)
-- Standard roles in Supabase/PostgREST need explicit grants to see the tables
GRANT USAGE ON SCHEMA public TO authenticated;
GRANT USAGE ON SCHEMA public TO anon;

-- Extra grants for financial tables just in case
GRANT SELECT, INSERT, UPDATE, DELETE ON public.accounts TO authenticated;
GRANT SELECT, INSERT, UPDATE, DELETE ON public.transactions TO authenticated;
GRANT SELECT, INSERT, UPDATE, DELETE ON public.categories TO authenticated;
GRANT SELECT, INSERT, UPDATE, DELETE ON public.cards TO authenticated;
GRANT SELECT, INSERT, UPDATE, DELETE ON public.bills_to_pay TO authenticated;
GRANT SELECT, INSERT, UPDATE, DELETE ON public.bills_to_receive TO authenticated;
