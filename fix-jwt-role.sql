-- Fix JWT generation to include user_id and email for frontend compatibility

DROP FUNCTION IF EXISTS auth.generate_jwt(uuid, text, integer);

CREATE OR REPLACE FUNCTION auth.generate_jwt(
  p_user_id uuid,
  p_email text,
  p_app_role text DEFAULT 'user',
  p_exp_hours integer DEFAULT 24
)
RETURNS text
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
DECLARE
  v_header json;
  v_payload json;
  v_signature text;
  v_secret text;
  v_token_parts text;
BEGIN
  v_secret := 'MpeW4RhMCXAsfQV8Eat5Lh8aC1eQR89DP2YJqOmxfE/HhFZdrhUxVG2//popoeGxFJvTOaLQDZIDoWW7kJUiKg==';

  v_header := json_build_object(
    'alg', 'HS256',
    'typ', 'JWT'
  );

  -- Include user_id and email for frontend compatibility
  v_payload := json_build_object(
    'sub', p_user_id::text,
    'user_id', p_user_id::text,
    'email', p_email,
    'role', 'authenticated',
    'app_role', p_app_role,
    'iat', extract(epoch from now())::integer,
    'exp', extract(epoch from now() + (p_exp_hours || ' hours')::interval)::integer
  );

  v_token_parts := auth.base64url_encode(v_header::text::bytea) || '.' || 
                   auth.base64url_encode(v_payload::text::bytea);

  v_signature := auth.base64url_encode(
    hmac(v_token_parts, v_secret, 'sha256')
  );

  RETURN v_token_parts || '.' || v_signature;
END;
$$;

GRANT EXECUTE ON FUNCTION auth.generate_jwt(uuid, text, text, integer) TO anon, authenticated;

-- Update auth.login to pass email to generate_jwt
DROP FUNCTION IF EXISTS auth.login(text, text);

CREATE OR REPLACE FUNCTION auth.login(p_email text, p_password text)
RETURNS json
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
DECLARE
    v_user_id uuid;
    v_password_hash text;
    v_user_role text;
    v_jwt_token text;
BEGIN
    -- Clean email
    p_email := lower(trim(p_email));
    
    -- Check rate limit
    IF NOT auth.check_login_rate_limit(p_email) THEN
        INSERT INTO auth.login_attempts (email, success)
        VALUES (p_email, false);
        
        RETURN json_build_object(
            'success', false,
            'error', 'Too many login attempts. Please try again later.'
        );
    END IF;
    
    -- Get user
    SELECT u.id, u.password_hash, p.role 
    INTO v_user_id, v_password_hash, v_user_role
    FROM auth.users u
    LEFT JOIN public.profiles p ON p.id = u.id
    WHERE u.email = p_email;
    
    -- User not found
    IF v_user_id IS NULL THEN
        INSERT INTO auth.login_attempts (email, success)
        VALUES (p_email, false);
        
        RETURN json_build_object(
            'success', false,
            'error', 'Invalid email or password'
        );
    END IF;
    
    -- Verify password
    IF auth.verify_password(p_password, v_password_hash) THEN
        -- Login success
        INSERT INTO auth.login_attempts (email, success)
        VALUES (p_email, true);
        
        -- Update last access
        UPDATE auth.users
        SET updated_at = now()
        WHERE id = v_user_id;
        
        -- Generate JWT token with email included
        v_jwt_token := auth.generate_jwt(v_user_id, p_email, COALESCE(v_user_role, 'user'), 24);
        
        -- Return success with token
        RETURN json_build_object(
            'success', true,
            'token', v_jwt_token,
            'user_id', v_user_id,
            'role', COALESCE(v_user_role, 'user')
        );
    ELSE
        -- Wrong password
        INSERT INTO auth.login_attempts (email, success)
        VALUES (p_email, false);
        
        RETURN json_build_object(
            'success', false,
            'error', 'Invalid email or password'
        );
    END IF;
END;
$$;

GRANT EXECUTE ON FUNCTION auth.login(text, text) TO anon, authenticated;

-- Test
SELECT auth.login('admin@onlifin.com', 'admin123');
