-- Fix auth.login function to compatibility with Supabase auth.users schema
-- This addresses the issue where auth.login might be looking for 'password_hash' 
-- instead of 'encrypted_password' which causes errors during login.

CREATE OR REPLACE FUNCTION auth.login(p_email text, p_password text)
RETURNS uuid AS $$
DECLARE
    v_user_id uuid;
    v_password_hash text;
BEGIN
    -- Try to find credentials using standard Supabase column names first
    BEGIN
        SELECT id, encrypted_password INTO v_user_id, v_password_hash
        FROM auth.users
        WHERE email = p_email;
    EXCEPTION WHEN undefined_column THEN
        -- Fallback for standalone/local environment that might use password_hash
        SELECT id, password_hash INTO v_user_id, v_password_hash
        FROM auth.users
        WHERE email = p_email;
    END;
    
    IF v_user_id IS NULL THEN
        RETURN NULL;
    END IF;
    
    -- Verify password
    IF v_password_hash = crypt(p_password, v_password_hash) THEN
        RETURN v_user_id;
    ELSE
        RETURN NULL;
    END IF;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

GRANT EXECUTE ON FUNCTION auth.login(text, text) TO anon;

-- Ensure public.login exists and works as expected
CREATE OR REPLACE FUNCTION public.login(p_email text, p_password text)
RETURNS text AS $$
DECLARE
    v_user_id uuid;
    v_real_email text;
    v_role text;
    v_jwt_secret text;
    v_result text;
BEGIN
    -- 1. Try to find the correct email
    SELECT email INTO v_real_email FROM auth.users WHERE email = p_email;
    
    -- 2. If not found, check profiles (as alternative email/username)
    IF v_real_email IS NULL THEN
        SELECT u.email INTO v_real_email 
        FROM profiles p
        JOIN auth.users u ON u.id = p.id
        WHERE p.email = p_email OR p.username = p_email;
    END IF;
    
    -- 3. If still not found and no @, try default domain
    IF v_real_email IS NULL AND p_email NOT LIKE '%@%' THEN
        v_real_email := p_email || '@miaoda.com';
    END IF;

    IF v_real_email IS NULL THEN
        v_real_email := p_email;
    END IF;

    -- Validate user credentials
    v_user_id := auth.login(v_real_email, p_password);
    
    IF v_user_id IS NULL THEN
        RAISE EXCEPTION 'Credenciais inválidas';
    END IF;
    
    -- Get role
    SELECT role::text INTO v_role FROM profiles WHERE id = v_user_id;
    IF v_role IS NULL THEN
        v_role := 'user';
    END IF;
    
    -- Get secret
    v_jwt_secret := coalesce(
        current_setting('app.settings.jwt_secret', true),
        'super-secret-jwt-token-with-at-least-32-characters-long' -- Fallback for dev/safety
    );
    
    -- Generate JWT
    v_result := sign(
        json_build_object(
            'role', 'authenticated',
            'sub', v_user_id,
            'user_id', v_user_id,
            'email', v_real_email,
            'app_role', v_role,
            'exp', extract(epoch from now())::integer + 86400
        ),
        v_jwt_secret
    );
    
    RETURN v_result;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

GRANT EXECUTE ON FUNCTION public.login(text, text) TO anon;
