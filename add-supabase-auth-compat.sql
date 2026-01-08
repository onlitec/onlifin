-- ============================================================================
-- OnliFin - Supabase Auth Compatible Functions
-- ============================================================================
-- These functions provide Supabase-compatible auth endpoints via PostgREST
-- ============================================================================

BEGIN;

-- ============================================================================
-- Supabase Auth Token endpoint (for signInWithPassword)
-- ============================================================================

CREATE OR REPLACE FUNCTION public.supabase_auth_token(
    p_email text DEFAULT NULL,
    p_password text DEFAULT NULL,
    p_grant_type text DEFAULT 'password'
)
RETURNS json
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
DECLARE
    v_result json;
    v_user_id uuid;
    v_user_role text;
    v_jwt_token text;
BEGIN
    -- Handle password grant type
    IF p_grant_type = 'password' THEN
        -- Call existing login function
        v_result := auth.login(p_email, p_password);
        
        IF v_result->>'success' = 'true' THEN
            v_user_id := (v_result->>'user_id')::uuid;
            v_jwt_token := v_result->>'token';
            v_user_role := v_result->>'role';
            
            -- Return Supabase-compatible response
            RETURN json_build_object(
                'access_token', v_jwt_token,
                'token_type', 'bearer',
                'expires_in', 86400,
                'refresh_token', v_jwt_token,
                'user', json_build_object(
                    'id', v_user_id,
                    'aud', 'authenticated',
                    'role', v_user_role,
                    'email', p_email,
                    'email_confirmed_at', now(),
                    'created_at', now(),
                    'updated_at', now(),
                    'app_metadata', json_build_object('provider', 'email'),
                    'user_metadata', '{}'::json
                )
            );
        ELSE
            -- Return error in Supabase format
            RETURN json_build_object(
                'error', 'invalid_grant',
                'error_description', v_result->>'error'
            );
        END IF;
    ELSE
        RETURN json_build_object(
            'error', 'unsupported_grant_type',
            'error_description', 'Only password grant type is supported'
        );
    END IF;
END;
$$;

-- ============================================================================
-- Supabase Auth User endpoint (for getUser)
-- ============================================================================

CREATE OR REPLACE FUNCTION public.supabase_auth_user()
RETURNS json
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
DECLARE
    v_user_id uuid;
    v_user_record RECORD;
    v_profile_record RECORD;
BEGIN
    -- Get current user from JWT
    v_user_id := auth.user_id();
    
    IF v_user_id IS NULL THEN
        RETURN json_build_object(
            'error', 'unauthorized',
            'message', 'Not authenticated'
        );
    END IF;
    
    -- Get user data
    SELECT id, email, created_at, updated_at
    INTO v_user_record
    FROM auth.users
    WHERE id = v_user_id;
    
    -- Get profile data
    SELECT id, username, full_name, role
    INTO v_profile_record
    FROM profiles
    WHERE id = v_user_id;
    
    IF v_user_record IS NULL THEN
        RETURN json_build_object(
            'error', 'not_found',
            'message', 'User not found'
        );
    END IF;
    
    RETURN json_build_object(
        'id', v_user_record.id,
        'aud', 'authenticated',
        'role', COALESCE(v_profile_record.role, 'user'),
        'email', v_user_record.email,
        'email_confirmed_at', v_user_record.created_at,
        'created_at', v_user_record.created_at,
        'updated_at', v_user_record.updated_at,
        'app_metadata', json_build_object(
            'provider', 'email',
            'providers', ARRAY['email']
        ),
        'user_metadata', json_build_object(
            'username', v_profile_record.username,
            'full_name', v_profile_record.full_name
        )
    );
END;
$$;

-- Grant permissions
GRANT EXECUTE ON FUNCTION public.supabase_auth_token(text, text, text) TO anon, authenticated;
GRANT EXECUTE ON FUNCTION public.supabase_auth_user() TO anon, authenticated;

COMMIT;

-- Test
SELECT public.supabase_auth_token('admin@onlifin.com', 'test');
