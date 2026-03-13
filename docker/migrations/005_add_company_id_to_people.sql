-- Migration: Add company_id to people table
-- Description: Adds company_id foreign key to people table for PJ support

-- 1. Add company_id column if not exists
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'people' AND column_name = 'company_id'
    ) THEN
        ALTER TABLE people ADD COLUMN company_id UUID REFERENCES companies(id) ON DELETE SET NULL;
        CREATE INDEX idx_people_company_id ON people(company_id);
        COMMENT ON COLUMN people.company_id IS 'ID da empresa associada (NULL = pessoa física PF)';
    END IF;
END $$;

-- 2. Update RLS policies for people
-- Permite que o usuário veja pessoas que ele criou ou que pertencem a suas empresas
DROP POLICY IF EXISTS "people_multi_tenant_policy" ON people;
CREATE POLICY "people_multi_tenant_policy" ON people
    FOR ALL
    USING (
        user_id = auth.uid() OR
        company_id IN (SELECT id FROM companies WHERE user_id = auth.uid())
    )
    WITH CHECK (
        user_id = auth.uid() OR
        company_id IN (SELECT id FROM companies WHERE user_id = auth.uid())
    );
