-- ===========================================
-- 12 - Pessoa titular PF automatica e protegida
-- ===========================================

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

WITH inserted_pf_people AS (
    INSERT INTO public.people (user_id, company_id, name, cpf, email, is_default, color)
    SELECT
        p.id,
        NULL,
        COALESCE(
            NULLIF(TRIM(p.full_name), ''),
            NULLIF(TRIM(p.username), ''),
            split_part(COALESCE(p.email, 'usuario@local'), '@', 1)
        ),
        p.cpf,
        p.email,
        true,
        '#2563eb'
    FROM public.profiles p
    WHERE NOT EXISTS (
        SELECT 1
        FROM public.people pe
        WHERE pe.user_id = p.id
          AND pe.company_id IS NULL
    )
    RETURNING user_id, id
),
owner_candidates AS (
    SELECT DISTINCT ON (pe.user_id)
        pe.user_id,
        pe.id AS person_id
    FROM public.people pe
    WHERE pe.company_id IS NULL
    ORDER BY pe.user_id, pe.is_default DESC, pe.created_at ASC, pe.id ASC
),
promoted_defaults AS (
    UPDATE public.people pe
    SET is_default = true,
        updated_at = now()
    FROM owner_candidates oc
    WHERE pe.id = oc.person_id
      AND NOT EXISTS (
          SELECT 1
          FROM public.people current_default
          WHERE current_default.user_id = oc.user_id
            AND current_default.company_id IS NULL
            AND current_default.is_default = true
      )
    RETURNING pe.user_id, pe.id
)
UPDATE public.profiles p
SET settings = coalesce(p.settings, '{}'::jsonb)
    || jsonb_build_object(
        'owner_person_id',
        coalesce(
            (SELECT ip.id FROM inserted_pf_people ip WHERE ip.user_id = p.id),
            (SELECT pd.id FROM promoted_defaults pd WHERE pd.user_id = p.id),
            (SELECT oc.person_id FROM owner_candidates oc WHERE oc.user_id = p.id)
        ),
        'hide_titular',
        true
    )
WHERE EXISTS (
    SELECT 1
    FROM owner_candidates oc
    WHERE oc.user_id = p.id
)
AND (
    p.settings IS NULL
    OR p.settings->>'owner_person_id' IS DISTINCT FROM coalesce(
        (SELECT ip.id::text FROM inserted_pf_people ip WHERE ip.user_id = p.id),
        (SELECT pd.id::text FROM promoted_defaults pd WHERE pd.user_id = p.id),
        (SELECT oc.person_id::text FROM owner_candidates oc WHERE oc.user_id = p.id)
    )
    OR coalesce(p.settings->>'hide_titular', 'false') <> 'true'
);

UPDATE public.profiles p
SET settings = coalesce(p.settings, '{}'::jsonb)
    || jsonb_build_object(
        'owner_person_id',
        oc.person_id,
        'hide_titular',
        true
    )
FROM (
    SELECT DISTINCT ON (pe.user_id)
        pe.user_id,
        pe.id AS person_id
    FROM public.people pe
    WHERE pe.company_id IS NULL
    ORDER BY pe.user_id, pe.is_default DESC, pe.created_at ASC, pe.id ASC
) oc
WHERE p.id = oc.user_id
AND (
    p.settings IS NULL
    OR p.settings->>'owner_person_id' IS DISTINCT FROM oc.person_id::text
    OR coalesce(p.settings->>'hide_titular', 'false') <> 'true'
);
