BEGIN;

CREATE OR REPLACE FUNCTION public.current_app_role()
RETURNS text
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  v_claims jsonb := COALESCE(NULLIF(current_setting('request.jwt.claims', true), ''), '{}')::jsonb;
BEGIN
  RETURN COALESCE(
    NULLIF(v_claims ->> 'app_role', ''),
    NULLIF(v_claims -> 'app_metadata' ->> 'role', ''),
    NULLIF(v_claims -> 'user_metadata' ->> 'role', ''),
    NULLIF(v_claims ->> 'role', ''),
    'user'
  );
END;
$$;

COMMENT ON FUNCTION public.current_app_role() IS 'Resolve o papel aplicacional a partir das claims JWT, priorizando app_role para o auth customizado.';

COMMIT;
