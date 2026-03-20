-- Migration: create recurring_schedules table used by AI financial context

BEGIN;

CREATE TABLE IF NOT EXISTS public.recurring_schedules (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES public.profiles(id) ON DELETE CASCADE,
    company_id UUID NULL REFERENCES public.companies(id) ON DELETE CASCADE,
    person_id UUID NULL REFERENCES public.people(id) ON DELETE SET NULL,
    account_id UUID NULL REFERENCES public.accounts(id) ON DELETE SET NULL,
    category_id UUID NULL REFERENCES public.categories(id) ON DELETE SET NULL,
    description TEXT NOT NULL,
    amount NUMERIC(15,2) NOT NULL DEFAULT 0,
    frequency TEXT NOT NULL DEFAULT 'monthly',
    type TEXT NOT NULL DEFAULT 'expense',
    start_date DATE NULL,
    end_date DATE NULL,
    next_run_date DATE NULL,
    is_active BOOLEAN NOT NULL DEFAULT true,
    notes TEXT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT recurring_schedules_frequency_check CHECK (
        frequency IN ('daily', 'weekly', 'fortnightly', 'monthly', 'yearly', 'custom')
    ),
    CONSTRAINT recurring_schedules_type_check CHECK (
        type IN ('income', 'expense')
    )
);

CREATE INDEX IF NOT EXISTS idx_recurring_schedules_user_id ON public.recurring_schedules(user_id);
CREATE INDEX IF NOT EXISTS idx_recurring_schedules_company_id ON public.recurring_schedules(company_id);
CREATE INDEX IF NOT EXISTS idx_recurring_schedules_person_id ON public.recurring_schedules(person_id);
CREATE INDEX IF NOT EXISTS idx_recurring_schedules_next_run_date ON public.recurring_schedules(next_run_date);

ALTER TABLE public.recurring_schedules ENABLE ROW LEVEL SECURITY;

GRANT SELECT, INSERT, UPDATE, DELETE ON public.recurring_schedules TO authenticated;
REVOKE ALL ON public.recurring_schedules FROM anon;

DROP POLICY IF EXISTS "Users can view their own recurring schedules" ON public.recurring_schedules;
CREATE POLICY "Users can view their own recurring schedules" ON public.recurring_schedules
    FOR SELECT TO authenticated
    USING (user_id = auth.uid());

DROP POLICY IF EXISTS "Users can insert their own recurring schedules" ON public.recurring_schedules;
CREATE POLICY "Users can insert their own recurring schedules" ON public.recurring_schedules
    FOR INSERT TO authenticated
    WITH CHECK (user_id = auth.uid());

DROP POLICY IF EXISTS "Users can update their own recurring schedules" ON public.recurring_schedules;
CREATE POLICY "Users can update their own recurring schedules" ON public.recurring_schedules
    FOR UPDATE TO authenticated
    USING (user_id = auth.uid())
    WITH CHECK (user_id = auth.uid());

DROP POLICY IF EXISTS "Users can delete their own recurring schedules" ON public.recurring_schedules;
CREATE POLICY "Users can delete their own recurring schedules" ON public.recurring_schedules
    FOR DELETE TO authenticated
    USING (user_id = auth.uid());

DROP TRIGGER IF EXISTS update_recurring_schedules_updated_at ON public.recurring_schedules;
CREATE TRIGGER update_recurring_schedules_updated_at
    BEFORE UPDATE ON public.recurring_schedules
    FOR EACH ROW
    EXECUTE FUNCTION public.update_updated_at_column();

COMMIT;
