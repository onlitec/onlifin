-- ===========================================
-- 2026-03-21 - Compatibilizacao do signup_tenant com planos
-- ===========================================
-- Objetivos:
-- 1. Aceitar p_plan como parametro opcional para compatibilidade com o marketing
-- 2. Persistir plan_code e plan diretamente em public.tenants
-- 3. Manter fallback seguro para basic quando o plano informado for invalido

DROP FUNCTION IF EXISTS public.signup_tenant(text, text, text, text, text, text);

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
    v_result jsonb;
    v_final_slug text := p_slug;
    v_counter integer := 0;
    v_resolved_plan_code text;
BEGIN
    v_resolved_plan_code := lower(coalesce(nullif(p_plan_code, ''), nullif(p_plan, ''), 'basic'));

    IF v_resolved_plan_code NOT IN ('basic', 'medium', 'full') THEN
        v_resolved_plan_code := 'basic';
    END IF;

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
        subscription_plan_id = v_plan_id,
        settings = coalesce(settings, '{}'::jsonb) || jsonb_build_object('plan_code', v_resolved_plan_code)
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
