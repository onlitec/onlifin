-- ===========================================
-- 2026-03-21 - Compatibilizacao de planos em tenants
-- ===========================================
-- Objetivos:
-- 1. Garantir colunas plan_code e plan na tabela public.tenants quando ela existir
-- 2. Normalizar valores legados para basic/medium/full
-- 3. Definir defaults para novos tenants

DO $$
BEGIN
    IF to_regclass('public.tenants') IS NULL THEN
        RAISE NOTICE 'Tabela public.tenants nao encontrada. Migration ignorada.';
        RETURN;
    END IF;

    ALTER TABLE public.tenants
      ADD COLUMN IF NOT EXISTS plan_code text;

    ALTER TABLE public.tenants
      ADD COLUMN IF NOT EXISTS plan text;

    ALTER TABLE public.tenants
      ALTER COLUMN plan_code SET DEFAULT 'basic';

    ALTER TABLE public.tenants
      ALTER COLUMN plan SET DEFAULT 'basic';

    UPDATE public.tenants
    SET plan_code = CASE
        WHEN lower(COALESCE(plan_code, plan, '')) IN ('basic', 'basico') THEN 'basic'
        WHEN lower(COALESCE(plan_code, plan, '')) IN ('medium', 'medio', 'intermediario') THEN 'medium'
        WHEN lower(COALESCE(plan_code, plan, '')) IN ('full', 'completo') THEN 'full'
        WHEN COALESCE(plan_code, plan, '') = '' THEN 'basic'
        ELSE 'basic'
    END;

    UPDATE public.tenants
    SET plan = plan_code
    WHERE plan IS DISTINCT FROM plan_code
       OR plan IS NULL;

    COMMENT ON COLUMN public.tenants.plan_code IS 'Codigo normalizado do plano comercial do tenant: basic, medium ou full';
    COMMENT ON COLUMN public.tenants.plan IS 'Espelho legado do codigo do plano para compatibilidade com clientes antigos';
END;
$$;
