-- ===========================================
-- 2026-03-21 - Correções do fluxo de gestão de usuários
-- ===========================================
-- Objetivos:
-- 1. Impedir login de usuários inativos/suspensos
-- 2. Atualizar último acesso no login
-- 3. Propagar flag de troca obrigatória de senha
-- 4. Permitir exclusão administrativa real da conta autenticável

ALTER TABLE public.profiles
  ADD COLUMN IF NOT EXISTS status text DEFAULT 'active';

ALTER TABLE public.profiles
  ADD COLUMN IF NOT EXISTS last_login_at timestamptz;

ALTER TABLE public.profiles
  ADD COLUMN IF NOT EXISTS force_password_change boolean DEFAULT false;

UPDATE public.profiles
SET status = 'active'
WHERE status IS NULL;

UPDATE public.profiles
SET force_password_change = false
WHERE force_password_change IS NULL;

CREATE OR REPLACE FUNCTION public.login(p_email text, p_password text)
RETURNS text AS $$
DECLARE
    v_user_id uuid;
    v_real_email text;
    v_password_hash text;
    v_role text;
    v_status text;
    v_force_password_change boolean;
    v_jwt_secret text;
    v_result text;
BEGIN
    SELECT email INTO v_real_email FROM auth.users WHERE email = p_email;

    IF v_real_email IS NULL THEN
        SELECT u.email INTO v_real_email
        FROM public.profiles p
        JOIN auth.users u ON u.id = p.id
        WHERE p.email = p_email OR p.username = p_email;
    END IF;

    IF v_real_email IS NULL AND p_email NOT LIKE '%@%' THEN
        v_real_email := p_email || '@miaoda.com';
    END IF;

    IF v_real_email IS NULL THEN
        v_real_email := p_email;
    END IF;

    SELECT u.id, u.password_hash
    INTO v_user_id, v_password_hash
    FROM auth.users u
    WHERE u.email = v_real_email;

    IF v_user_id IS NULL OR v_password_hash IS NULL THEN
        RAISE EXCEPTION 'Credenciais inválidas';
    END IF;

    IF NOT auth.verify_password(p_password, v_password_hash) THEN
        RAISE EXCEPTION 'Credenciais inválidas';
    END IF;

    SELECT
        p.role::text,
        COALESCE(p.status, 'active'),
        COALESCE(p.force_password_change, false)
    INTO
        v_role,
        v_status,
        v_force_password_change
    FROM public.profiles p
    WHERE p.id = v_user_id;

    IF NOT FOUND THEN
        RAISE EXCEPTION 'Perfil do usuário não encontrado';
    END IF;

    IF v_status = 'suspended' THEN
        RAISE EXCEPTION 'Usuário suspenso. Contate um administrador.';
    ELSIF v_status = 'inactive' THEN
        RAISE EXCEPTION 'Usuário inativo. Contate um administrador.';
    ELSIF v_status <> 'active' THEN
        RAISE EXCEPTION 'Status de usuário inválido.';
    END IF;

    UPDATE public.profiles
    SET last_login_at = now()
    WHERE id = v_user_id;

    IF v_role IS NULL THEN
        v_role := 'user';
    END IF;

    v_jwt_secret := coalesce(
        current_setting('app.settings.jwt_secret', true),
        'super-secret-jwt-token-with-at-least-32-characters-long'
    );

    v_result := sign(
        json_build_object(
            'role', 'authenticated',
            'sub', v_user_id,
            'user_id', v_user_id,
            'email', v_real_email,
            'app_role', v_role,
            'status', v_status,
            'force_password_change', v_force_password_change,
            'exp', extract(epoch from now())::integer + 86400
        ),
        v_jwt_secret
    );

    RETURN v_result;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

CREATE OR REPLACE FUNCTION public.admin_delete_user(p_user_id uuid)
RETURNS boolean AS $$
DECLARE
    v_caller_id uuid;
    v_caller_role text;
BEGIN
    v_caller_id := auth.uid();

    SELECT p.role::text
    INTO v_caller_role
    FROM public.profiles p
    WHERE p.id = v_caller_id;

    IF v_caller_role IS DISTINCT FROM 'admin' THEN
        RAISE EXCEPTION 'Apenas administradores podem excluir usuários';
    END IF;

    IF p_user_id = v_caller_id THEN
        RAISE EXCEPTION 'Não é permitido excluir a própria conta';
    END IF;

    DELETE FROM auth.users
    WHERE id = p_user_id;

    IF NOT FOUND THEN
        DELETE FROM public.profiles
        WHERE id = p_user_id;

        IF NOT FOUND THEN
            RAISE EXCEPTION 'Usuário não encontrado';
        END IF;
    END IF;

    RETURN true;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

CREATE OR REPLACE FUNCTION public.admin_reset_password(p_user_id uuid, p_new_password text)
RETURNS boolean AS $$
DECLARE
    v_caller_id uuid;
    v_caller_role text;
BEGIN
    v_caller_id := auth.uid();

    SELECT p.role::text
    INTO v_caller_role
    FROM public.profiles p
    WHERE p.id = v_caller_id;

    IF v_caller_role IS DISTINCT FROM 'admin' THEN
        RAISE EXCEPTION 'Apenas administradores podem resetar senhas';
    END IF;

    UPDATE auth.users
    SET password_hash = auth.hash_password(p_new_password),
        updated_at = now()
    WHERE id = p_user_id;

    UPDATE public.profiles
    SET force_password_change = true
    WHERE id = p_user_id;

    RETURN true;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

GRANT EXECUTE ON FUNCTION public.login(text, text) TO anon;
GRANT EXECUTE ON FUNCTION public.admin_delete_user(uuid) TO authenticated;
GRANT EXECUTE ON FUNCTION public.admin_reset_password(uuid, text) TO authenticated;

COMMENT ON FUNCTION public.login IS 'Autentica usuário ativo e retorna JWT de sessão com status e obrigatoriedade de troca de senha';
COMMENT ON FUNCTION public.admin_delete_user IS 'Exclui um usuário e sua conta de autenticação; uso restrito a administradores';
COMMENT ON FUNCTION public.admin_reset_password IS 'Reseta a senha do usuário e obriga troca no próximo login; uso restrito a administradores';
