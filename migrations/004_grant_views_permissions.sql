-- Migration: Grant access to views and helper functions
-- Description: Ensures the authenticated role can access views and execute PJ functions

-- Grant SELECT on views
GRANT SELECT ON public.v_company_summary TO authenticated;
GRANT SELECT ON public.v_user_consolidated_dashboard TO authenticated;

-- Grant EXECUTE on PJ functions
GRANT EXECUTE ON FUNCTION public.get_user_companies() TO authenticated;
GRANT EXECUTE ON FUNCTION public.set_default_company(UUID) TO authenticated;
GRANT EXECUTE ON FUNCTION public.calculate_company_balance(UUID) TO authenticated;
GRANT EXECUTE ON FUNCTION public.validate_cnpj(VARCHAR) TO authenticated;
GRANT EXECUTE ON FUNCTION public.validate_cpf(VARCHAR) TO authenticated;

-- Ensure usage on schemas
GRANT USAGE ON SCHEMA public TO authenticated;
GRANT USAGE ON SCHEMA auth TO authenticated;

-- Confirmation log
DO $$ 
BEGIN 
    RAISE NOTICE 'Permissions granted for PJ views and functions';
END $$;
