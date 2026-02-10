-- Migration: Add person_id to missing tables
-- Fix for inconsistency between frontend expectations and database schema

-- Add person_id to financial_forecasts
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'financial_forecasts' AND column_name = 'person_id'
    ) THEN
        ALTER TABLE financial_forecasts ADD COLUMN person_id UUID REFERENCES people(id) ON DELETE CASCADE;
        CREATE INDEX idx_financial_forecasts_person_id ON financial_forecasts(person_id);
    END IF;
END $$;

-- Add person_id to notifications
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'notifications' AND column_name = 'person_id'
    ) THEN
        ALTER TABLE notifications ADD COLUMN person_id UUID REFERENCES people(id) ON DELETE CASCADE;
        CREATE INDEX idx_notifications_person_id ON notifications(person_id);
    END IF;
END $$;

-- Update RLS for financial_forecasts
DROP POLICY IF EXISTS "forecasts_multi_tenant_policy" ON financial_forecasts;
CREATE POLICY "forecasts_multi_tenant_policy" ON financial_forecasts
    FOR ALL
    USING (
        user_id = auth.uid() OR
        (company_id IN (SELECT id FROM companies WHERE user_id = auth.uid())) OR
        (person_id IN (SELECT id FROM people WHERE user_id = auth.uid()))
    )
    WITH CHECK (
        user_id = auth.uid() OR
        (company_id IN (SELECT id FROM companies WHERE user_id = auth.uid())) OR
        (person_id IN (SELECT id FROM people WHERE user_id = auth.uid()))
    );

-- Update RLS for notifications
DROP POLICY IF EXISTS "notifications_multi_tenant_policy" ON notifications;
CREATE POLICY "notifications_multi_tenant_policy" ON notifications
    FOR ALL
    USING (
        user_id = auth.uid() OR
        (person_id IN (SELECT id FROM people WHERE user_id = auth.uid()))
    )
    WITH CHECK (
        user_id = auth.uid() OR
        (person_id IN (SELECT id FROM people WHERE user_id = auth.uid()))
    );

-- Ensure default grants for new columns
GRANT ALL ON ALL TABLES IN SCHEMA public TO authenticated;
