-- ===========================================
-- 05 - Wrappers para PostgREST RPC
-- ===========================================
-- Expõe funções do schema auth no schema public
-- para que o PostgREST possa chamá-las via /rpc/

-- ===========================================
-- Wrapper para login
-- ===========================================
CREATE OR REPLACE FUNCTION public.login(p_email text, p_password text)
RETURNS text AS $$
DECLARE
    v_user_id uuid;
    v_role text;
    v_jwt_secret text;
    v_result text;
BEGIN
    -- Validar usuário
    v_user_id := auth.login(p_email, p_password);
    
    IF v_user_id IS NULL THEN
        RAISE EXCEPTION 'Credenciais inválidas';
    END IF;
    
    -- Obter role
    SELECT role::text INTO v_role FROM profiles WHERE id = v_user_id;
    IF v_role IS NULL THEN
        v_role := 'user';
    END IF;
    
    -- Obter secret
    v_jwt_secret := current_setting('app.settings.jwt_secret', true);
    IF v_jwt_secret IS NULL THEN
        RAISE EXCEPTION 'JWT Secret não configurado no banco de dados';
    END IF;
    
    -- Gerar JWT
    -- Payload: role, user_id, exp (24h), email
    v_result := sign(
        json_build_object(
            'role', 'authenticated', -- PostgREST role
            'user_id', v_user_id,
            'email', p_email,
            'app_role', v_role,      -- Nossa role de app (admin/user)
            'exp', extract(epoch from now())::integer + 86400 -- 24h
        ),
        v_jwt_secret
    );
    
    RETURN v_result;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

GRANT EXECUTE ON FUNCTION public.login(text, text) TO anon;
COMMENT ON FUNCTION public.login IS 'Autentica usuário e retorna JWT de sessão';

-- ===========================================
-- Wrapper para register
-- ===========================================
CREATE OR REPLACE FUNCTION public.register(p_email text, p_password text)
RETURNS uuid AS $$
BEGIN
    RETURN auth.register(p_email, p_password);
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

GRANT EXECUTE ON FUNCTION public.register(text, text) TO anon;
COMMENT ON FUNCTION public.register IS 'Registra novo usuário e retorna user_id';

-- ===========================================
-- Permissões adicionais
-- ===========================================
GRANT USAGE ON SCHEMA public TO anon, authenticated;
GRANT USAGE ON SCHEMA auth TO anon, authenticated;

-- Garantir que anon pode acessar tabelas necessárias
GRANT SELECT ON profiles TO anon;
GRANT SELECT ON categories TO anon;

-- Garantir que authenticated pode acessar tudo
GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO authenticated;
GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO authenticated;

-- Notificar PostgREST para recarregar
NOTIFY pgrst, 'reload schema';
