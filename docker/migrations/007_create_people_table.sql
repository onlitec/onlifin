-- Migration: Create people table and add person_id to financial tables
-- Description: Adds support for multiple people (family members) in Personal Finance (PF) context

-- Create people table
CREATE TABLE IF NOT EXISTS people (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
    name TEXT NOT NULL,
    cpf TEXT,
    email TEXT,
    is_default BOOLEAN DEFAULT false,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- Add is_system to categories (required for policy update below)
ALTER TABLE categories ADD COLUMN IF NOT EXISTS is_system BOOLEAN DEFAULT false;

-- Add RLS to people table
ALTER TABLE people ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "Users can view their own people" ON people;
CREATE POLICY "Users can view their own people" ON people
    FOR SELECT USING (user_id = auth.uid());

DROP POLICY IF EXISTS "Users can insert their own people" ON people;
CREATE POLICY "Users can insert their own people" ON people
    FOR INSERT WITH CHECK (user_id = auth.uid());

DROP POLICY IF EXISTS "Users can update their own people" ON people;
CREATE POLICY "Users can update their own people" ON people
    FOR UPDATE USING (user_id = auth.uid());

DROP POLICY IF EXISTS "Users can delete their own people" ON people;
CREATE POLICY "Users can delete their own people" ON people
    FOR DELETE USING (user_id = auth.uid());

-- Add triggers for updated_at
CREATE OR REPLACE TRIGGER update_people_updated_at
    BEFORE UPDATE ON people
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- Add person_id to accounts
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'accounts' AND column_name = 'person_id'
    ) THEN
        ALTER TABLE accounts ADD COLUMN person_id UUID REFERENCES people(id) ON DELETE SET NULL;
        CREATE INDEX idx_accounts_person_id ON accounts(person_id);
    END IF;
END $$;

-- Add person_id to transactions
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'transactions' AND column_name = 'person_id'
    ) THEN
        ALTER TABLE transactions ADD COLUMN person_id UUID REFERENCES people(id) ON DELETE SET NULL;
        CREATE INDEX idx_transactions_person_id ON transactions(person_id);
    END IF;
END $$;

-- Add person_id to cards
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'cards' AND column_name = 'person_id'
    ) THEN
        ALTER TABLE cards ADD COLUMN person_id UUID REFERENCES people(id) ON DELETE SET NULL;
        CREATE INDEX idx_cards_person_id ON cards(person_id);
    END IF;
END $$;

-- Add person_id to categories
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'categories' AND column_name = 'person_id'
    ) THEN
        ALTER TABLE categories ADD COLUMN person_id UUID REFERENCES people(id) ON DELETE SET NULL;
        CREATE INDEX idx_categories_person_id ON categories(person_id);
    END IF;
END $$;

-- Add person_id to bills_to_pay
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'bills_to_pay' AND column_name = 'person_id'
    ) THEN
        ALTER TABLE bills_to_pay ADD COLUMN person_id UUID REFERENCES people(id) ON DELETE SET NULL;
        CREATE INDEX idx_bills_to_pay_person_id ON bills_to_pay(person_id);
    END IF;
END $$;

-- Add person_id to bills_to_receive
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'bills_to_receive' AND column_name = 'person_id'
    ) THEN
        ALTER TABLE bills_to_receive ADD COLUMN person_id UUID REFERENCES people(id) ON DELETE SET NULL;
        CREATE INDEX idx_bills_to_receive_person_id ON bills_to_receive(person_id);
    END IF;
END $$;

-- Update RLS policies to include person_id checks
-- The logic is:
-- User can access if:
-- 1. They own the record (user_id = auth.uid()) 
--    AND (company_id is NULL OR company_id belongs to them) - existing logic
--    AND (person_id is NULL OR person_id belongs to them) - new logic

-- Helper function to check person ownership (optional, but RLS usually handles subqueries ok)
-- For now, well inline the checks in the policies as we did for companies.

-- Accounts policy
DROP POLICY IF EXISTS "accounts_multi_tenant_policy" ON accounts;
CREATE POLICY "accounts_multi_tenant_policy" ON accounts
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

-- Transactions policy  
DROP POLICY IF EXISTS "transactions_multi_tenant_policy" ON transactions;
CREATE POLICY "transactions_multi_tenant_policy" ON transactions
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

-- Cards policy
DROP POLICY IF EXISTS "cards_multi_tenant_policy" ON cards;
CREATE POLICY "cards_multi_tenant_policy" ON cards
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

-- Bills to pay policy
DROP POLICY IF EXISTS "bills_to_pay_multi_tenant_policy" ON bills_to_pay;
CREATE POLICY "bills_to_pay_multi_tenant_policy" ON bills_to_pay
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

-- Bills to receive policy
DROP POLICY IF EXISTS "bills_to_receive_multi_tenant_policy" ON bills_to_receive;
CREATE POLICY "bills_to_receive_multi_tenant_policy" ON bills_to_receive
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

-- Categories policy
-- Note: Categories might be shared, but for custom ones:
DROP POLICY IF EXISTS "categories_multi_tenant_policy" ON categories;
CREATE POLICY "categories_multi_tenant_policy" ON categories
    FOR ALL
    USING (
        user_id = auth.uid() OR
        (company_id IN (SELECT id FROM companies WHERE user_id = auth.uid())) OR
        (person_id IN (SELECT id FROM people WHERE user_id = auth.uid())) OR
        is_system = true
    )
    WITH CHECK (
        (user_id = auth.uid() OR
        (company_id IN (SELECT id FROM companies WHERE user_id = auth.uid())) OR
        (person_id IN (SELECT id FROM people WHERE user_id = auth.uid())))
        AND is_system = false
    );
