DO $$
DECLARE
    has_profiles_settings boolean;
    has_profiles_tenant_id boolean;
    has_tenants_table boolean;
    has_tenants_plan_code boolean;
    has_tenants_plan boolean;
    plan_expression text;
    updated_rows integer := 0;
BEGIN
    SELECT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = 'public'
          AND table_name = 'profiles'
          AND column_name = 'settings'
    ) INTO has_profiles_settings;

    SELECT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = 'public'
          AND table_name = 'profiles'
          AND column_name = 'tenant_id'
    ) INTO has_profiles_tenant_id;

    SELECT EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = 'public'
          AND table_name = 'tenants'
    ) INTO has_tenants_table;

    IF NOT has_profiles_settings OR NOT has_profiles_tenant_id THEN
        RAISE NOTICE 'Perfis nao possuem settings e tenant_id suficientes para sincronizacao.';
        RETURN;
    END IF;

    IF NOT has_tenants_table THEN
        RAISE NOTICE 'Tabela public.tenants nao encontrada. Nada a sincronizar.';
        RETURN;
    END IF;

    SELECT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = 'public'
          AND table_name = 'tenants'
          AND column_name = 'plan_code'
    ) INTO has_tenants_plan_code;

    SELECT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = 'public'
          AND table_name = 'tenants'
          AND column_name = 'plan'
    ) INTO has_tenants_plan;

    IF has_tenants_plan_code THEN
        plan_expression := 't.plan_code';
    ELSIF has_tenants_plan THEN
        plan_expression := 't.plan';
    ELSE
        RAISE NOTICE 'Tabela public.tenants nao possui plan_code nem plan. Nada a sincronizar.';
        RETURN;
    END IF;

    EXECUTE format(
        $sql$
        WITH tenant_plans AS (
            SELECT
                p.id,
                lower(%1$s) AS resolved_plan
            FROM public.profiles p
            JOIN public.tenants t
              ON t.id = p.tenant_id
            WHERE lower(%1$s) IN ('basic', 'medium', 'full')
              AND COALESCE(lower(p.settings ->> 'plan_code'), '') NOT IN ('basic', 'medium', 'full')
        )
        UPDATE public.profiles p
        SET settings = COALESCE(p.settings, '{}'::jsonb) || jsonb_build_object('plan_code', tenant_plans.resolved_plan)
        FROM tenant_plans
        WHERE p.id = tenant_plans.id
        $sql$,
        plan_expression
    );

    GET DIAGNOSTICS updated_rows = ROW_COUNT;
    RAISE NOTICE 'Perfis sincronizados com plan_code: %', updated_rows;
END $$;
