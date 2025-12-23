-- ===========================================
-- 05 - Wrappers para PostgREST RPC
-- ===========================================
-- Expõe funções do schema auth no schema public
-- para que o PostgREST possa chamá-las via /rpc/

-- ===========================================
-- Wrapper para login
-- ===========================================
CREATE OR REPLACE FUNCTION public.login(p_email text, p_password text)
RETURNS uuid AS $$
BEGIN
    RETURN auth.login(p_email, p_password);
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

GRANT EXECUTE ON FUNCTION public.login(text, text) TO anon;
COMMENT ON FUNCTION public.login IS 'Autentica usuário e retorna user_id';

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
