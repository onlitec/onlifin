-- ===========================================
-- 2026-03-21 - Deduplicacao e protecao de categorias
-- ===========================================
-- Objetivos:
-- 1. Manter apenas uma categoria por nome/tipo dentro do mesmo escopo
-- 2. Remapear referencias para a categoria preservada
-- 3. Impedir novas duplicacoes no banco

BEGIN;

WITH ranked AS (
    SELECT
        c.id,
        first_value(c.id) OVER (
            PARTITION BY
                lower(btrim(c.name)),
                c.type,
                coalesce(c.user_id, '00000000-0000-0000-0000-000000000000'::uuid),
                coalesce(c.company_id, '00000000-0000-0000-0000-000000000000'::uuid),
                coalesce(c.person_id, '00000000-0000-0000-0000-000000000000'::uuid),
                coalesce(c.tenant_id, '00000000-0000-0000-0000-000000000000'::uuid),
                c.is_system
            ORDER BY c.created_at ASC, c.id ASC
        ) AS keep_id,
        row_number() OVER (
            PARTITION BY
                lower(btrim(c.name)),
                c.type,
                coalesce(c.user_id, '00000000-0000-0000-0000-000000000000'::uuid),
                coalesce(c.company_id, '00000000-0000-0000-0000-000000000000'::uuid),
                coalesce(c.person_id, '00000000-0000-0000-0000-000000000000'::uuid),
                coalesce(c.tenant_id, '00000000-0000-0000-0000-000000000000'::uuid),
                c.is_system
            ORDER BY c.created_at ASC, c.id ASC
        ) AS rn
    FROM public.categories c
),
duplicates AS (
    SELECT id AS duplicate_id, keep_id
    FROM ranked
    WHERE rn > 1
)
UPDATE public.transactions t
SET category_id = d.keep_id
FROM duplicates d
WHERE t.category_id = d.duplicate_id;

WITH ranked AS (
    SELECT
        c.id,
        first_value(c.id) OVER (
            PARTITION BY
                lower(btrim(c.name)),
                c.type,
                coalesce(c.user_id, '00000000-0000-0000-0000-000000000000'::uuid),
                coalesce(c.company_id, '00000000-0000-0000-0000-000000000000'::uuid),
                coalesce(c.person_id, '00000000-0000-0000-0000-000000000000'::uuid),
                coalesce(c.tenant_id, '00000000-0000-0000-0000-000000000000'::uuid),
                c.is_system
            ORDER BY c.created_at ASC, c.id ASC
        ) AS keep_id,
        row_number() OVER (
            PARTITION BY
                lower(btrim(c.name)),
                c.type,
                coalesce(c.user_id, '00000000-0000-0000-0000-000000000000'::uuid),
                coalesce(c.company_id, '00000000-0000-0000-0000-000000000000'::uuid),
                coalesce(c.person_id, '00000000-0000-0000-0000-000000000000'::uuid),
                coalesce(c.tenant_id, '00000000-0000-0000-0000-000000000000'::uuid),
                c.is_system
            ORDER BY c.created_at ASC, c.id ASC
        ) AS rn
    FROM public.categories c
),
duplicates AS (
    SELECT id AS duplicate_id, keep_id
    FROM ranked
    WHERE rn > 1
)
UPDATE public.recurring_schedules rs
SET category_id = d.keep_id
FROM duplicates d
WHERE rs.category_id = d.duplicate_id;

WITH ranked AS (
    SELECT
        c.id,
        first_value(c.id) OVER (
            PARTITION BY
                lower(btrim(c.name)),
                c.type,
                coalesce(c.user_id, '00000000-0000-0000-0000-000000000000'::uuid),
                coalesce(c.company_id, '00000000-0000-0000-0000-000000000000'::uuid),
                coalesce(c.person_id, '00000000-0000-0000-0000-000000000000'::uuid),
                coalesce(c.tenant_id, '00000000-0000-0000-0000-000000000000'::uuid),
                c.is_system
            ORDER BY c.created_at ASC, c.id ASC
        ) AS keep_id,
        row_number() OVER (
            PARTITION BY
                lower(btrim(c.name)),
                c.type,
                coalesce(c.user_id, '00000000-0000-0000-0000-000000000000'::uuid),
                coalesce(c.company_id, '00000000-0000-0000-0000-000000000000'::uuid),
                coalesce(c.person_id, '00000000-0000-0000-0000-000000000000'::uuid),
                coalesce(c.tenant_id, '00000000-0000-0000-0000-000000000000'::uuid),
                c.is_system
            ORDER BY c.created_at ASC, c.id ASC
        ) AS rn
    FROM public.categories c
),
duplicates AS (
    SELECT id AS duplicate_id, keep_id
    FROM ranked
    WHERE rn > 1
)
UPDATE public.category_corrections cc
SET
    original_category_id = CASE WHEN cc.original_category_id = d.duplicate_id THEN d.keep_id ELSE cc.original_category_id END,
    corrected_category_id = CASE WHEN cc.corrected_category_id = d.duplicate_id THEN d.keep_id ELSE cc.corrected_category_id END
FROM duplicates d
WHERE cc.original_category_id = d.duplicate_id
   OR cc.corrected_category_id = d.duplicate_id;

WITH ranked AS (
    SELECT
        c.id,
        row_number() OVER (
            PARTITION BY
                lower(btrim(c.name)),
                c.type,
                coalesce(c.user_id, '00000000-0000-0000-0000-000000000000'::uuid),
                coalesce(c.company_id, '00000000-0000-0000-0000-000000000000'::uuid),
                coalesce(c.person_id, '00000000-0000-0000-0000-000000000000'::uuid),
                coalesce(c.tenant_id, '00000000-0000-0000-0000-000000000000'::uuid),
                c.is_system
            ORDER BY c.created_at ASC, c.id ASC
        ) AS rn
    FROM public.categories c
)
DELETE FROM public.categories c
USING ranked r
WHERE c.id = r.id
  AND r.rn > 1;

CREATE UNIQUE INDEX IF NOT EXISTS idx_categories_unique_scope_name_type
ON public.categories (
    lower(btrim(name)),
    type,
    coalesce(user_id, '00000000-0000-0000-0000-000000000000'::uuid),
    coalesce(company_id, '00000000-0000-0000-0000-000000000000'::uuid),
    coalesce(person_id, '00000000-0000-0000-0000-000000000000'::uuid),
    coalesce(tenant_id, '00000000-0000-0000-0000-000000000000'::uuid),
    is_system
);

COMMIT;
