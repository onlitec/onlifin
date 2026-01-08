-- ===========================================
-- 08 - Reset de Senha e Troca Obrigatória
-- ===========================================
-- Adiciona suporte para reset de senha administrativa e troca obrigatória no primeiro login

-- 1. Adicionar campo force_password_change em profiles
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name='profiles' AND column_name='force_password_change') THEN
        ALTER TABLE profiles ADD COLUMN force_password_change boolean DEFAULT false;
    END IF;
END;
$$;

COMMENT ON COLUMN profiles.force_password_change IS 'Indica se o usuário deve trocar a senha no próximo login';

-- 2. Função administrativa para resetar senha
CREATE OR REPLACE FUNCTION public.admin_reset_password(p_user_id uuid, p_new_password text)
RETURNS boolean AS $$
DECLARE
    v_caller_id uuid;
    v_caller_role text;
BEGIN
    -- Verificar se quem chama é admin
    v_caller_id := auth.uid();
    SELECT role::text INTO v_caller_role FROM public.profiles WHERE id = v_caller_id;
    
    IF v_caller_role != 'admin' THEN
        RAISE EXCEPTION 'Apenas administradores podem resetar senhas';
    END IF;

    -- Atualizar senha em auth.users
    UPDATE auth.users 
    SET password_hash = auth.hash_password(p_new_password),
        updated_at = now()
    WHERE id = p_user_id;

    -- Marcar para troca obrigatória
    UPDATE public.profiles 
    SET force_password_change = true 
    WHERE id = p_user_id;

    RETURN true;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 3. Função para o usuário trocar sua própria senha
CREATE OR REPLACE FUNCTION public.change_own_password(p_current_password text, p_new_password text)
RETURNS boolean AS $$
DECLARE
    v_user_id uuid;
    v_password_hash text;
BEGIN
    v_user_id := auth.uid();
    
    IF v_user_id IS NULL THEN
        RAISE EXCEPTION 'Não autenticado';
    END IF;

    -- Verificar senha atual
    SELECT password_hash INTO v_password_hash 
    FROM auth.users 
    WHERE id = v_user_id;

    IF NOT auth.verify_password(p_current_password, v_password_hash) THEN
        RAISE EXCEPTION 'Senha atual incorreta';
    END IF;

    -- Atualizar para nova senha
    UPDATE auth.users 
    SET password_hash = auth.hash_password(p_new_password),
        updated_at = now()
    WHERE id = v_user_id;

    -- Limpar flag de troca obrigatória
    UPDATE public.profiles 
    SET force_password_change = false 
    WHERE id = v_user_id;

    RETURN true;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 4. Permissões para as novas funções
GRANT EXECUTE ON FUNCTION public.admin_reset_password(uuid, text) TO authenticated;
GRANT EXECUTE ON FUNCTION public.change_own_password(text, text) TO authenticated;
