-- ===========================================
-- 05 - Wrappers para PostgREST RPC
-- ===========================================
-- Expõe funções do schema auth no schema public
-- para que o PostgREST possa chamá-las via /rpc/

-- ===========================================
-- Wrapper para login (Suporta Username ou Email)
-- ===========================================
CREATE OR REPLACE FUNCTION public.login(p_email text, p_password text)
RETURNS text AS $$
DECLARE
    v_user_id uuid;
    v_real_email text;
    v_role text;
    v_jwt_secret text;
    v_result text;
BEGIN
    -- 1. Tentar encontrar o email correto
    -- Primeiro: Verificamos se p_email é um email em auth.users
    SELECT email INTO v_real_email FROM auth.users WHERE email = p_email;
    
    -- Segundo: Se não encontrou, verificamos se p_email está na tabela profiles (como email alternativo)
    IF v_real_email IS NULL THEN
        SELECT u.email INTO v_real_email 
        FROM profiles p
        JOIN auth.users u ON u.id = p.id
        WHERE p.email = p_email OR p.username = p_email;
    END IF;
    
    -- Terceiro: Se ainda não encontrou e não tem @, tenta o domínio padrão
    IF v_real_email IS NULL AND p_email NOT LIKE '%@%' THEN
        v_real_email := p_email || '@miaoda.com';
    END IF;

    -- Se não resolveu para nada, v_real_email será o que o usuário digitou (e falhará no auth.login)
    IF v_real_email IS NULL THEN
        v_real_email := p_email;
    END IF;

    -- Validar usuário com o email real resolvido
    v_user_id := auth.login(v_real_email, p_password);
    
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
