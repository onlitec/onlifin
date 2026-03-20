-- Migration: Replace legacy people view with a real table used by PF/PJ scopes

BEGIN;

DROP POLICY IF EXISTS debts_owner_policy ON public.debts;

DO $$
BEGIN
    IF EXISTS (
        SELECT 1
        FROM pg_class c
        JOIN pg_namespace n ON n.oid = c.relnamespace
        WHERE n.nspname = 'public'
          AND c.relname = 'people'
          AND c.relkind = 'v'
    ) THEN
        EXECUTE 'DROP VIEW public.people';
    END IF;
END $$;

CREATE TABLE IF NOT EXISTS public.people (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES public.profiles(id) ON DELETE CASCADE,
    company_id UUID NULL REFERENCES public.companies(id) ON DELETE CASCADE,
    name TEXT NOT NULL,
    cpf VARCHAR(14),
    email TEXT,
    is_default BOOLEAN NOT NULL DEFAULT false,
    color VARCHAR(50),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_people_user_id ON public.people(user_id);
CREATE INDEX IF NOT EXISTS idx_people_company_id ON public.people(company_id);
CREATE INDEX IF NOT EXISTS idx_people_user_company ON public.people(user_id, company_id);
CREATE UNIQUE INDEX IF NOT EXISTS idx_people_one_default_pf_per_user
    ON public.people(user_id)
    WHERE company_id IS NULL AND is_default = true;
CREATE UNIQUE INDEX IF NOT EXISTS idx_people_one_default_pj_per_user_company
    ON public.people(user_id, company_id)
    WHERE company_id IS NOT NULL AND is_default = true;

ALTER TABLE public.people ENABLE ROW LEVEL SECURITY;

GRANT SELECT, INSERT, UPDATE, DELETE ON public.people TO authenticated;
REVOKE ALL ON public.people FROM anon;

DROP POLICY IF EXISTS "Users can view their own people" ON public.people;
CREATE POLICY "Users can view their own people" ON public.people
    FOR SELECT TO authenticated
    USING (user_id = auth.uid());

DROP POLICY IF EXISTS "Users can insert their own people" ON public.people;
CREATE POLICY "Users can insert their own people" ON public.people
    FOR INSERT TO authenticated
    WITH CHECK (user_id = auth.uid());

DROP POLICY IF EXISTS "Users can update their own people" ON public.people;
CREATE POLICY "Users can update their own people" ON public.people
    FOR UPDATE TO authenticated
    USING (user_id = auth.uid())
    WITH CHECK (user_id = auth.uid());

DROP POLICY IF EXISTS "Users can delete their own people" ON public.people;
CREATE POLICY "Users can delete their own people" ON public.people
    FOR DELETE TO authenticated
    USING (user_id = auth.uid());

DROP TRIGGER IF EXISTS update_people_updated_at ON public.people;
CREATE TRIGGER update_people_updated_at
    BEFORE UPDATE ON public.people
    FOR EACH ROW
    EXECUTE FUNCTION public.update_updated_at_column();

INSERT INTO public.people (user_id, company_id, name, cpf, email, is_default, color)
SELECT
    p.id,
    p.company_id,
    COALESCE(
        NULLIF(TRIM(p.full_name), ''),
        NULLIF(TRIM(p.username), ''),
        split_part(COALESCE(p.email, 'usuario@local'), '@', 1)
    ),
    p.cpf,
    p.email,
    true,
    CASE WHEN p.is_default THEN '#10b981' ELSE '#3b82f6' END
FROM public.profiles p
WHERE NOT EXISTS (
    SELECT 1
    FROM public.people pe
    WHERE pe.user_id = p.id
      AND pe.company_id IS NOT DISTINCT FROM p.company_id
);

CREATE POLICY debts_owner_policy ON public.debts
    FOR ALL TO authenticated
    USING (
        user_id = auth.uid()
        AND (company_id IS NULL OR company_id IN (SELECT id FROM public.companies WHERE user_id = auth.uid()))
        AND (person_id IS NULL OR person_id IN (SELECT id FROM public.people WHERE user_id = auth.uid()))
    )
    WITH CHECK (
        user_id = auth.uid()
        AND (company_id IS NULL OR company_id IN (SELECT id FROM public.companies WHERE user_id = auth.uid()))
        AND (person_id IS NULL OR person_id IN (SELECT id FROM public.people WHERE user_id = auth.uid()))
    );

COMMIT;
