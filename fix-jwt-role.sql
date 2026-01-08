-- Fix JWT generation to use 'authenticated' role instead of app role

DROP FUNCTION IF EXISTS auth.generate_jwt(uuid, text, integer);

CREATE OR REPLACE FUNCTION auth.generate_jwt(
  p_user_id uuid,
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

  -- IMPORTANT: role must be 'authenticated' for PostgREST
  -- app_role contains the actual user role (admin, user, etc)
  v_payload := json_build_object(
    'sub', p_user_id::text,
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

GRANT EXECUTE ON FUNCTION auth.generate_jwt(uuid, text, integer) TO anon, authenticated;

-- Test
SELECT auth.generate_jwt('fb0c86eb-8692-436e-93bd-cdf169b0a96f'::uuid, 'admin', 24);
