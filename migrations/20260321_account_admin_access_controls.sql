-- ===========================================
-- 2026-03-21 - Separacao entre admin da conta e admin da plataforma
-- ===========================================
-- Objetivos:
-- 1. Marcar o criador do tenant como account admin
-- 2. Backfill do primeiro perfil de cada tenant existente
-- 3. Emitir claims de account admin e tenant_id no JWT de login

BEGIN;

WITH tenant_admin_candidates AS (
    SELECT DISTINCT ON (p.tenant_id)
        p.id,
        p.tenant_id
    FROM public.profiles p
    WHERE p.tenant_id IS NOT NULL
    ORDER BY p.tenant_id, p.created_at ASC, p.id ASC
)
UPDATE public.profiles p
SET settings = coalesce(p.settings, '{}'::jsonb) || jsonb_build_object('account_admin', true)
FROM tenant_admin_candidates candidates
WHERE p.id = candidates.id
  AND coalesce(lower(p.settings ->> 'account_admin'), 'false') <> 'true';

DROP FUNCTION IF EXISTS public.signup_tenant(text, text, text, text, text, text, text);

CREATE OR REPLACE FUNCTION public.signup_tenant(
    p_email text,
    p_password text,
    p_full_name text,
    p_tenant_name text,
    p_slug text,
    p_plan_code text DEFAULT 'basic',
    p_plan text DEFAULT NULL
)
RETURNS jsonb
LANGUAGE plpgsql
SECURITY DEFINER
AS $function$
DECLARE
    v_user_id uuid;
    v_tenant_id uuid;
    v_plan_id uuid;
    v_owner_person_id uuid;
    v_result jsonb;
    v_final_slug text := p_slug;
    v_counter integer := 0;
    v_resolved_plan_code text;
    v_person_name text;
BEGIN
    v_resolved_plan_code := lower(coalesce(nullif(p_plan_code, ''), nullif(p_plan, ''), 'basic'));

    IF v_resolved_plan_code NOT IN ('basic', 'medium', 'full') THEN
        v_resolved_plan_code := 'basic';
    END IF;

    v_person_name := coalesce(nullif(trim(p_full_name), ''), split_part(p_email, '@', 1), 'Titular');

    WHILE v_tenant_id IS NULL AND v_counter < 10 LOOP
        BEGIN
            INSERT INTO public.tenants (name, slug, plan_code, plan)
            VALUES (p_tenant_name, v_final_slug, v_resolved_plan_code, v_resolved_plan_code)
            RETURNING id INTO v_tenant_id;
        EXCEPTION WHEN unique_violation THEN
            v_counter := v_counter + 1;
            v_final_slug := p_slug || '-' || substr(md5(random()::text), 1, 4);
        END;
    END LOOP;

    IF v_tenant_id IS NULL THEN
        RETURN jsonb_build_object(
            'success', false,
            'message', 'Nao foi possivel gerar um identificador unico para sua empresa. Tente outro nome.'
        );
    END IF;

    SELECT id
    INTO v_plan_id
    FROM public.subscription_plans
    WHERE code = v_resolved_plan_code
    LIMIT 1;

    IF v_plan_id IS NULL THEN
        SELECT id
        INTO v_plan_id
        FROM public.subscription_plans
        WHERE code = 'basic'
        LIMIT 1;
    END IF;

    BEGIN
        INSERT INTO auth.users (email, password_hash)
        VALUES (p_email, crypt(p_password, gen_salt('bf')))
        RETURNING id INTO v_user_id;
    EXCEPTION WHEN unique_violation THEN
        RETURN jsonb_build_object('success', false, 'message', 'O e-mail informado ja esta em uso.');
    END;

    UPDATE public.profiles
    SET full_name = p_full_name,
        tenant_id = v_tenant_id,
        subscription_plan_id = v_plan_id
    WHERE id = v_user_id;

    INSERT INTO public.people (user_id, company_id, name, email, is_default, color)
    VALUES (v_user_id, NULL, v_person_name, p_email, true, '#2563eb')
    RETURNING id INTO v_owner_person_id;

    UPDATE public.profiles
    SET settings = coalesce(settings, '{}'::jsonb)
        || jsonb_build_object(
            'plan_code', v_resolved_plan_code,
            'owner_person_id', v_owner_person_id,
            'hide_titular', true,
            'account_admin', true
        )
    WHERE id = v_user_id;

    v_result := jsonb_build_object(
        'success', true,
        'user_id', v_user_id,
        'tenant_id', v_tenant_id,
        'slug', v_final_slug,
        'plan', v_resolved_plan_code,
        'plan_code', v_resolved_plan_code
    );

    RETURN v_result;

EXCEPTION WHEN OTHERS THEN
    RETURN jsonb_build_object('success', false, 'message', 'Erro inesperado: ' || SQLERRM);
END;
$function$;

GRANT EXECUTE ON FUNCTION public.signup_tenant(text, text, text, text, text, text, text) TO anon;

CREATE OR REPLACE FUNCTION public.login(p_email text, p_password text)
RETURNS text AS $$
DECLARE
    v_user_id uuid;
    v_real_email text;
    v_password_hash text;
    v_role text;
    v_status text;
    v_force_password_change boolean;
    v_account_admin boolean;
    v_tenant_id uuid;
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
        COALESCE(p.force_password_change, false),
        p.tenant_id,
        CASE
            WHEN lower(coalesce(p.settings ->> 'account_admin', 'false')) IN ('true', '1', 't', 'yes', 'y', 'on') THEN true
            ELSE false
        END
    INTO
        v_role,
        v_status,
        v_force_password_change,
        v_tenant_id,
        v_account_admin
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
            'account_admin', v_account_admin,
            'tenant_id', v_tenant_id,
            'status', v_status,
            'force_password_change', v_force_password_change,
            'exp', extract(epoch from now())::integer + 86400
        ),
        v_jwt_secret
    );

    RETURN v_result;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

COMMENT ON FUNCTION public.login IS 'Autentica usuário ativo e retorna JWT com role de plataforma, account_admin e tenant_id';

COMMIT;
