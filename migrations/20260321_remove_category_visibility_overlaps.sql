-- ===========================================
-- 2026-03-21 - Remover categorias visivelmente duplicadas
-- ===========================================
-- Objetivos:
-- 1. Remover categorias personalizadas legadas que duplicam categorias globais
-- 2. Remapear referencias para a categoria global correspondente
-- 3. Preservar apenas uma categoria visivel por nome/tipo no escopo base

BEGIN;

WITH category_overlap_map AS (
    SELECT
        c.id AS duplicate_id,
        s.id AS keep_id
    FROM public.categories c
    JOIN public.categories s
      ON s.user_id IS NULL
     AND s.company_id IS NULL
     AND s.person_id IS NULL
     AND lower(btrim(s.name)) = lower(btrim(c.name))
     AND s.type = c.type
     AND coalesce(s.tenant_id, '00000000-0000-0000-0000-000000000000'::uuid)
         = coalesce(c.tenant_id, '00000000-0000-0000-0000-000000000000'::uuid)
    WHERE c.user_id IS NOT NULL
      AND c.company_id IS NULL
      AND c.person_id IS NULL
)
UPDATE public.transactions t
SET category_id = o.keep_id
FROM category_overlap_map o
WHERE t.category_id = o.duplicate_id;

WITH category_overlap_map AS (
    SELECT
        c.id AS duplicate_id,
        s.id AS keep_id
    FROM public.categories c
    JOIN public.categories s
      ON s.user_id IS NULL
     AND s.company_id IS NULL
     AND s.person_id IS NULL
     AND lower(btrim(s.name)) = lower(btrim(c.name))
     AND s.type = c.type
     AND coalesce(s.tenant_id, '00000000-0000-0000-0000-000000000000'::uuid)
         = coalesce(c.tenant_id, '00000000-0000-0000-0000-000000000000'::uuid)
    WHERE c.user_id IS NOT NULL
      AND c.company_id IS NULL
      AND c.person_id IS NULL
)
UPDATE public.recurring_schedules rs
SET category_id = o.keep_id
FROM category_overlap_map o
WHERE rs.category_id = o.duplicate_id;

WITH category_overlap_map AS (
    SELECT
        c.id AS duplicate_id,
        s.id AS keep_id
    FROM public.categories c
    JOIN public.categories s
      ON s.user_id IS NULL
     AND s.company_id IS NULL
     AND s.person_id IS NULL
     AND lower(btrim(s.name)) = lower(btrim(c.name))
     AND s.type = c.type
     AND coalesce(s.tenant_id, '00000000-0000-0000-0000-000000000000'::uuid)
         = coalesce(c.tenant_id, '00000000-0000-0000-0000-000000000000'::uuid)
    WHERE c.user_id IS NOT NULL
      AND c.company_id IS NULL
      AND c.person_id IS NULL
)
UPDATE public.category_corrections cc
SET
    original_category_id = CASE WHEN cc.original_category_id = o.duplicate_id THEN o.keep_id ELSE cc.original_category_id END,
    corrected_category_id = CASE WHEN cc.corrected_category_id = o.duplicate_id THEN o.keep_id ELSE cc.corrected_category_id END
FROM category_overlap_map o
WHERE cc.original_category_id = o.duplicate_id
   OR cc.corrected_category_id = o.duplicate_id;

WITH category_overlap_map AS (
    SELECT c.id
    FROM public.categories c
    JOIN public.categories s
      ON s.user_id IS NULL
     AND s.company_id IS NULL
     AND s.person_id IS NULL
     AND lower(btrim(s.name)) = lower(btrim(c.name))
     AND s.type = c.type
     AND coalesce(s.tenant_id, '00000000-0000-0000-0000-000000000000'::uuid)
         = coalesce(c.tenant_id, '00000000-0000-0000-0000-000000000000'::uuid)
    WHERE c.user_id IS NOT NULL
      AND c.company_id IS NULL
      AND c.person_id IS NULL
)
DELETE FROM public.categories c
USING category_overlap_map o
WHERE c.id = o.id;

COMMIT;
