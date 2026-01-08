-- ============================================================================
-- OnliFin - Advanced User Management RPCs
-- ============================================================================

-- 1. Listar Usuários (Dados de Auth + Profile)
CREATE OR REPLACE FUNCTION public.admin_list_users()
RETURNS TABLE (
    id uuid,
    email text,
    username text,
    full_name text,
    role user_role,
    created_at timestamptz
) 
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path TO 'public', 'auth'
AS $$
BEGIN
    IF current_setting('request.jwt.claims', true)::json->>'app_role' != 'admin' THEN
        RAISE EXCEPTION 'Acesso negado.';
    END IF;

    RETURN QUERY
    SELECT 
        u.id,
        u.email::text,
        p.username,
        p.full_name,
        p.role,
        u.created_at
    FROM auth.users u
    JOIN public.profiles p ON u.id = p.id
    ORDER BY u.created_at DESC;
END;
$$;

-- 2. Atualizar Usuário
CREATE OR REPLACE FUNCTION public.admin_update_user(
    p_user_id uuid,
    p_email text,
    p_full_name text,
    p_role text
)
RETURNS json
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path TO 'public', 'auth'
AS $$
BEGIN
    IF current_setting('request.jwt.claims', true)::json->>'app_role' != 'admin' THEN
        RETURN json_build_object('success', false, 'error', 'Acesso negado.');
    END IF;

    -- Atualizar Auth
    UPDATE auth.users SET email = lower(trim(p_email)), updated_at = now() WHERE id = p_user_id;
    
    -- Atualizar Profile
    UPDATE public.profiles SET 
        full_name = p_full_name,
        role = p_role::user_role,
        username = split_part(p_email, '@', 1)
    WHERE id = p_user_id;

    RETURN json_build_object('success', true, 'message', 'Usuário atualizado com sucesso.');
END;
$$;

-- 3. Resetar Senha
CREATE OR REPLACE FUNCTION public.admin_reset_password(
    p_user_id uuid,
    p_new_password text
)
RETURNS json
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path TO 'public', 'auth'
AS $$
BEGIN
    IF current_setting('request.jwt.claims', true)::json->>'app_role' != 'admin' THEN
        RETURN json_build_object('success', false, 'error', 'Acesso negado.');
    END IF;

    IF length(p_new_password) < 6 THEN
        RETURN json_build_object('success', false, 'error', 'Senha muito curta.');
    END IF;

    UPDATE auth.users 
    SET password_hash = auth.hash_password(p_new_password), 
        updated_at = now() 
    WHERE id = p_user_id;

    RETURN json_build_object('success', true, 'message', 'Senha alterada com sucesso.');
END;
$$;

-- 4. Deletar Usuário (Cascata)
CREATE OR REPLACE FUNCTION public.admin_delete_user(p_user_id uuid)
RETURNS json
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path TO 'public', 'auth'
AS $$
BEGIN
    IF current_setting('request.jwt.claims', true)::json->>'app_role' != 'admin' THEN
        RETURN json_build_object('success', false, 'error', 'Acesso negado.');
    END IF;

    DELETE FROM auth.users WHERE id = p_user_id;
    -- Profiles será deletado automaticamente se houver FK com CASCADE, 
    -- caso contrário deletamos manualmente:
    DELETE FROM public.profiles WHERE id = p_user_id;

    RETURN json_build_object('success', true, 'message', 'Usuário removido.');
END;
$$;

-- Grants
GRANT EXECUTE ON FUNCTION public.admin_list_users() TO authenticated;
GRANT EXECUTE ON FUNCTION public.admin_update_user(uuid, text, text, text) TO authenticated;
GRANT EXECUTE ON FUNCTION public.admin_reset_password(uuid, text) TO authenticated;
GRANT EXECUTE ON FUNCTION public.admin_delete_user(uuid) TO authenticated;
