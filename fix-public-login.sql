-- Fix public.login to work with new auth.login that returns JSON

DROP FUNCTION IF EXISTS public.login(text, text);

CREATE OR REPLACE FUNCTION public.login(p_email text, p_password text)
RETURNS json
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
DECLARE
    v_auth_result json;
    v_user_id uuid;
    v_role text;
    v_token text;
BEGIN
    -- Call auth.login which now returns JSON
    v_auth_result := auth.login(p_email, p_password);
    
    -- Check if login was successful
    IF (v_auth_result->>'success')::boolean = true THEN
        -- Return the already-formed response from auth.login
        RETURN v_auth_result;
    ELSE
        -- Return error from auth.login
        RETURN v_auth_result;
    END IF;
END;
$$;

-- Grant permissions
GRANT EXECUTE ON FUNCTION public.login(text, text) TO anon, authenticated, web_anon;

-- Test
SELECT public.login('admin@onlifin.com', 'admin123');
