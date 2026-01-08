-- Fix public.login to return ONLY the JWT token (not JSON)
-- This is required for compatibility with the frontend that expects just the token text

DROP FUNCTION IF EXISTS public.login(text, text);

CREATE OR REPLACE FUNCTION public.login(p_email text, p_password text)
RETURNS text
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
DECLARE
    v_auth_result json;
    v_token text;
BEGIN
    -- Call auth.login which returns JSON
    v_auth_result := auth.login(p_email, p_password);
    
    -- Check if login was successful
    IF (v_auth_result->>'success')::boolean = true THEN
        -- Return ONLY the token for frontend compatibility
        v_token := v_auth_result->>'token';
        RETURN v_token;
    ELSE
        -- Return NULL on failure (frontend expects this)
        RAISE EXCEPTION 'Credenciais inválidas';
    END IF;
END;
$$;

-- Grant permissions
GRANT EXECUTE ON FUNCTION public.login(text, text) TO anon, authenticated, web_anon;

-- Test - should return ONLY the JWT token
SELECT public.login('admin@onlifin.com', 'admin123');
