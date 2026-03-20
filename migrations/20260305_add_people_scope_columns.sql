-- Migration: Add people scope support to the main migration chain
-- Description: Ensure person_id exists on PF-scoped tables before dashboard RPCs run

DO $$
DECLARE
    v_people_kind "char";
BEGIN
    SELECT c.relkind
      INTO v_people_kind
      FROM pg_class c
      JOIN pg_namespace n ON n.oid = c.relnamespace
     WHERE n.nspname = 'public'
       AND c.relname = 'people';

    IF v_people_kind IS NULL THEN
        CREATE TABLE public.people (
            id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
            user_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
            name TEXT NOT NULL,
            cpf TEXT,
            email TEXT,
            is_default BOOLEAN DEFAULT false,
            created_at TIMESTAMPTZ DEFAULT NOW(),
            updated_at TIMESTAMPTZ DEFAULT NOW()
        );

        ALTER TABLE public.people ENABLE ROW LEVEL SECURITY;

        DROP POLICY IF EXISTS "Users can view their own people" ON public.people;
        CREATE POLICY "Users can view their own people" ON public.people
            FOR SELECT TO authenticated USING (user_id = auth.uid());

        DROP POLICY IF EXISTS "Users can insert their own people" ON public.people;
        CREATE POLICY "Users can insert their own people" ON public.people
            FOR INSERT TO authenticated WITH CHECK (user_id = auth.uid());

        DROP POLICY IF EXISTS "Users can update their own people" ON public.people;
        CREATE POLICY "Users can update their own people" ON public.people
            FOR UPDATE TO authenticated USING (user_id = auth.uid()) WITH CHECK (user_id = auth.uid());

        DROP POLICY IF EXISTS "Users can delete their own people" ON public.people;
        CREATE POLICY "Users can delete their own people" ON public.people
            FOR DELETE TO authenticated USING (user_id = auth.uid());
    END IF;
END $$;

ALTER TABLE categories ADD COLUMN IF NOT EXISTS is_system BOOLEAN DEFAULT false;

ALTER TABLE accounts ADD COLUMN IF NOT EXISTS person_id UUID;
ALTER TABLE transactions ADD COLUMN IF NOT EXISTS person_id UUID;
ALTER TABLE cards ADD COLUMN IF NOT EXISTS person_id UUID;
ALTER TABLE categories ADD COLUMN IF NOT EXISTS person_id UUID;
ALTER TABLE bills_to_pay ADD COLUMN IF NOT EXISTS person_id UUID;
ALTER TABLE bills_to_receive ADD COLUMN IF NOT EXISTS person_id UUID;
ALTER TABLE financial_forecasts ADD COLUMN IF NOT EXISTS person_id UUID;
ALTER TABLE notifications ADD COLUMN IF NOT EXISTS person_id UUID;

CREATE INDEX IF NOT EXISTS idx_accounts_person_id ON accounts(person_id);
CREATE INDEX IF NOT EXISTS idx_transactions_person_id ON transactions(person_id);
CREATE INDEX IF NOT EXISTS idx_cards_person_id ON cards(person_id);
CREATE INDEX IF NOT EXISTS idx_categories_person_id ON categories(person_id);
CREATE INDEX IF NOT EXISTS idx_bills_to_pay_person_id ON bills_to_pay(person_id);
CREATE INDEX IF NOT EXISTS idx_bills_to_receive_person_id ON bills_to_receive(person_id);
CREATE INDEX IF NOT EXISTS idx_financial_forecasts_person_id ON financial_forecasts(person_id);
CREATE INDEX IF NOT EXISTS idx_notifications_person_id ON notifications(person_id);
