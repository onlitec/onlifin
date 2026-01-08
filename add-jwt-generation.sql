-- ============================================================================
-- OnliFin - JWT Token Generation Functions
-- ============================================================================
-- This script adds JWT token generation capability to the authentication system
-- Uses pgcrypto extension for HMAC-SHA256 signing
-- ============================================================================

BEGIN;

-- ============================================================================
-- JWT Helper Functions
-- ============================================================================

-- Function to encode data to base64url (JWT compatible)
CREATE OR REPLACE FUNCTION auth.base64url_encode(data bytea)
RETURNS text
LANGUAGE sql
IMMUTABLE
AS $$
  SELECT translate(encode(data, 'base64'), E'+/=\n', '-_');
$$;

-- Function to generate JWT token
CREATE OR REPLACE FUNCTION auth.generate_jwt(
  p_user_id uuid,
  p_role text DEFAULT 'user',
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
  -- Get JWT secret from settings (fallback to hardcoded for now)
  -- In production, this should come from environment or secure config
  v_secret := current_setting('app.settings.jwt_secret', true);
  
  IF v_secret IS NULL OR v_secret = '' THEN
    -- Fallback: use the JWT_SECRET from docker-compose env
    v_secret := 'MpeW4RhMCXAsfQV8Eat5Lh8aC1eQR89DP2YJqOmxfE/HhFZdrhUxVG2//popoeGxFJvTOaLQDZIDoWW7kJUiKg==';
  END IF;

  -- Create JWT header
  v_header := json_build_object(
    'alg', 'HS256',
    'typ', 'JWT'
  );

  -- Create JWT payload
  v_payload := json_build_object(
    'sub', p_user_id::text,
    'role', p_role,
    'iat', extract(epoch from now())::integer,
    'exp', extract(epoch from now() + (p_exp_hours || ' hours')::interval)::integer
  );

  -- Create token parts (header.payload)
  v_token_parts := auth.base64url_encode(v_header::text::bytea) || '.' || 
                   auth.base64url_encode(v_payload::text::bytea);

  -- Generate HMAC-SHA256 signature
  v_signature := auth.base64url_encode(
    hmac(v_token_parts, v_secret, 'sha256')
  );

  -- Return complete JWT token
  RETURN v_token_parts || '.' || v_signature;
END;
$$;

-- ============================================================================
-- Update auth.login to return JWT token
-- ============================================================================

-- Drop old login function
DROP FUNCTION IF EXISTS auth.login(text, text);

-- Create new login function that returns JWT token
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
        -- Log blocked attempt
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
        
        -- Generate JWT token
        v_jwt_token := auth.generate_jwt(v_user_id, COALESCE(v_user_role, 'user'), 24);
        
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

-- Grant execute permission to anon role
GRANT EXECUTE ON FUNCTION auth.generate_jwt(uuid, text, integer) TO anon, authenticated;
GRANT EXECUTE ON FUNCTION auth.base64url_encode(bytea) TO anon, authenticated;
GRANT EXECUTE ON FUNCTION auth.login(text, text) TO anon, authenticated;

-- ============================================================================
-- Verification
-- ============================================================================

-- Test JWT generation
SELECT auth.generate_jwt(
  'fb0c86eb-8692-436e-93bd-cdf169b0a96f'::uuid,
  'admin',
  24
) AS sample_jwt_token;

COMMIT;
